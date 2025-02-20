<?php

use MediaWiki\Block\DatabaseBlock;
use MediaWiki\MainConfigNames;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Title\Title;

/**
 * @group API
 * @group Database
 * @group medium
 *
 * @covers ApiMove
 */
class ApiMoveTest extends ApiTestCase {

	protected function setUp(): void {
		parent::setUp();

		$this->tablesUsed = array_merge(
			$this->tablesUsed,
			[ 'watchlist', 'watchlist_expiry' ]
		);

		$this->overrideConfigValue( MainConfigNames::WatchlistExpiry, true );
	}

	/**
	 * @param string $from Prefixed name of source
	 * @param string $to Prefixed name of destination
	 * @param string $id Page id of the page to move
	 * @param array|string|null $opts Options: 'noredirect' to expect no redirect
	 */
	protected function assertMoved( $from, $to, $id, $opts = null ) {
		$opts = (array)$opts;

		Title::clearCaches();
		$fromTitle = Title::newFromText( $from );
		$toTitle = Title::newFromText( $to );

		$this->assertTrue( $toTitle->exists(),
			"Destination {$toTitle->getPrefixedText()} does not exist" );

		if ( in_array( 'noredirect', $opts ) ) {
			$this->assertFalse( $fromTitle->exists(),
				"Source {$fromTitle->getPrefixedText()} exists" );
		} else {
			$this->assertTrue( $fromTitle->exists(),
				"Source {$fromTitle->getPrefixedText()} does not exist" );
			$this->assertTrue( $fromTitle->isRedirect(),
				"Source {$fromTitle->getPrefixedText()} is not a redirect" );

			$target = $this->getServiceContainer()
				->getRevisionLookup()
				->getRevisionByTitle( $fromTitle )
				->getContent( SlotRecord::MAIN )
				->getRedirectTarget();
			$this->assertSame( $toTitle->getPrefixedText(), $target->getPrefixedText() );
		}

		$this->assertSame( $id, $toTitle->getArticleID() );
	}

	/**
	 * Shortcut function to create a page and return its id.
	 *
	 * @param string $name Page to create
	 * @return int ID of created page
	 */
	protected function createPage( $name ) {
		return $this->editPage( $name, 'Content' )->getNewRevision()->getPageId();
	}

	public function testFromWithFromid() {
		$this->expectApiErrorCode( 'invalidparammix' );

		$this->doApiRequestWithToken( [
			'action' => 'move',
			'from' => 'Some page',
			'fromid' => 123,
			'to' => 'Some other page',
		] );
	}

	public function testMove() {
		$name = ucfirst( __FUNCTION__ );

		$id = $this->createPage( $name );

		$res = $this->doApiRequestWithToken( [
			'action' => 'move',
			'from' => $name,
			'to' => "$name 2",
		] );

		$this->assertMoved( $name, "$name 2", $id );
		$this->assertArrayNotHasKey( 'warnings', $res[0] );
	}

	public function testMoveById() {
		$name = ucfirst( __FUNCTION__ );

		$id = $this->createPage( $name );

		$res = $this->doApiRequestWithToken( [
			'action' => 'move',
			'fromid' => $id,
			'to' => "$name 2",
		] );

		$this->assertMoved( $name, "$name 2", $id );
		$this->assertArrayNotHasKey( 'warnings', $res[0] );
	}

	public function testMoveAndWatch(): void {
		$name = ucfirst( __FUNCTION__ );
		$this->createPage( $name );

		$this->doApiRequestWithToken( [
			'action' => 'move',
			'from' => $name,
			'to' => "$name 2",
			'watchlist' => 'watch',
			'watchlistexpiry' => '99990123000000',
		] );

		$title = Title::newFromText( $name );
		$title2 = Title::newFromText( "$name 2" );
		$user = $this->getTestSysop()->getUser();
		$watchlistManager = $this->getServiceContainer()->getWatchlistManager();
		$this->assertTrue( $watchlistManager->isTempWatched( $user, $title ) );
		$this->assertTrue( $watchlistManager->isTempWatched( $user, $title2 ) );
	}

	public function testMoveWithWatchUnchanged(): void {
		$name = ucfirst( __FUNCTION__ );
		$this->createPage( $name );
		$title = Title::newFromText( $name );
		$title2 = Title::newFromText( "$name 2" );
		$user = $this->getTestSysop()->getUser();

		// Temporarily watch the page.
		$this->doApiRequestWithToken( [
			'action' => 'watch',
			'titles' => $name,
			'expiry' => '99990123000000',
		] );

		// Fetched stored expiry (maximum duration may override '99990123000000').
		$store = $this->getServiceContainer()->getWatchedItemStore();
		$expiry = $store->getWatchedItem( $user, $title )->getExpiry();

		// Move to new location, without changing the watched state.
		$this->doApiRequestWithToken( [
			'action' => 'move',
			'from' => $title->getDBkey(),
			'to' => $title2->getDBkey(),
		] );

		// New page should have the same expiry.
		$expiry2 = $store->getWatchedItem( $user, $title2 )->getExpiry();
		$this->assertSame( wfTimestamp( TS_MW, $expiry ), $expiry2 );
	}

	public function testMoveNonexistent() {
		$this->expectApiErrorCode( 'missingtitle' );

		$this->doApiRequestWithToken( [
			'action' => 'move',
			'from' => 'Nonexistent page',
			'to' => 'Different page'
		] );
	}

	public function testMoveNonexistentId() {
		$this->expectApiErrorCode( 'nosuchpageid' );

		$this->doApiRequestWithToken( [
			'action' => 'move',
			'fromid' => pow( 2, 31 ) - 1,
			'to' => 'Different page',
		] );
	}

	public function testMoveToInvalidPageName() {
		$this->expectApiErrorCode( 'invalidtitle' );

		$name = ucfirst( __FUNCTION__ );
		$id = $this->createPage( $name );

		try {
			$this->doApiRequestWithToken( [
				'action' => 'move',
				'from' => $name,
				'to' => '[',
			] );
		} finally {
			$this->assertSame( $id, Title::newFromText( $name )->getArticleID() );
		}
	}

	public function testMoveWhileBlocked() {
		$this->assertNull( DatabaseBlock::newFromTarget( '127.0.0.1' ) );

		$user = $this->getTestSysop()->getUser();
		$blockStore = $this->getServiceContainer()->getDatabaseBlockStore();
		$block = new DatabaseBlock( [
			'address' => $user->getName(),
			'by' => $user,
			'reason' => 'Capriciousness',
			'timestamp' => '19370101000000',
			'expiry' => 'infinity',
			'enableAutoblock' => true,
		] );
		$blockStore->insertBlock( $block );

		$name = ucfirst( __FUNCTION__ );
		$id = $this->createPage( $name );

		try {
			$this->doApiRequestWithToken( [
				'action' => 'move',
				'from' => $name,
				'to' => "$name 2",
			] );
			$this->fail( 'Expected exception not thrown' );
		} catch ( ApiUsageException $ex ) {
			$this->assertApiErrorCode( 'blocked', $ex );
			$this->assertNotNull( DatabaseBlock::newFromTarget( '127.0.0.1' ), 'Autoblock spread' );
		} finally {
			$blockStore->deleteBlock( $block );
			$user->clearInstanceCache();
			$this->assertSame( $id, Title::newFromText( $name )->getArticleID() );
		}
	}

	// @todo File moving

	public function testPingLimiter() {
		$this->expectApiErrorCode( 'ratelimited' );

		$name = ucfirst( __FUNCTION__ );

		$this->overrideConfigValue( MainConfigNames::RateLimits,
			[ 'move' => [ '&can-bypass' => false, 'user' => [ 1, 60 ] ] ]
		);

		$id = $this->createPage( $name );

		$res = $this->doApiRequestWithToken( [
			'action' => 'move',
			'from' => $name,
			'to' => "$name 2",
		] );

		$this->assertMoved( $name, "$name 2", $id );
		$this->assertArrayNotHasKey( 'warnings', $res[0] );

		try {
			$this->doApiRequestWithToken( [
				'action' => 'move',
				'from' => "$name 2",
				'to' => "$name 3",
			] );
		} finally {
			$this->assertSame( $id, Title::newFromText( "$name 2" )->getArticleID() );
			$this->assertFalse( Title::newFromText( "$name 3" )->exists(),
				"\"$name 3\" should not exist" );
		}
	}

	public function testTagsNoPermission() {
		$this->expectApiErrorCode( 'tags-apply-no-permission' );

		$name = ucfirst( __FUNCTION__ );

		$this->getServiceContainer()->getChangeTagsStore()->defineTag( 'custom tag' );

		$this->setGroupPermissions( 'user', 'applychangetags', false );

		$id = $this->createPage( $name );

		try {
			$this->doApiRequestWithToken( [
				'action' => 'move',
				'from' => $name,
				'to' => "$name 2",
				'tags' => 'custom tag',
			] );
		} finally {
			$this->assertSame( $id, Title::newFromText( $name )->getArticleID() );
			$this->assertFalse( Title::newFromText( "$name 2" )->exists(),
				"\"$name 2\" should not exist" );
		}
	}

	public function testSelfMove() {
		$this->expectApiErrorCode( 'selfmove' );

		$name = ucfirst( __FUNCTION__ );
		$this->createPage( $name );

		$this->doApiRequestWithToken( [
			'action' => 'move',
			'from' => $name,
			'to' => $name,
		] );
	}

	public function testMoveTalk() {
		$name = ucfirst( __FUNCTION__ );

		$id = $this->createPage( $name );
		$talkId = $this->createPage( "Talk:$name" );

		$res = $this->doApiRequestWithToken( [
			'action' => 'move',
			'from' => $name,
			'to' => "$name 2",
			'movetalk' => '',
		] );

		$this->assertMoved( $name, "$name 2", $id );
		$this->assertMoved( "Talk:$name", "Talk:$name 2", $talkId );

		$this->assertArrayNotHasKey( 'warnings', $res[0] );
	}

	public function testMoveTalkFailed() {
		$name = ucfirst( __FUNCTION__ );

		$id = $this->createPage( $name );
		$talkId = $this->createPage( "Talk:$name" );
		$talkDestinationId = $this->createPage( "Talk:$name 2" );

		$res = $this->doApiRequestWithToken( [
			'action' => 'move',
			'from' => $name,
			'to' => "$name 2",
			'movetalk' => '',
		] );

		$this->assertMoved( $name, "$name 2", $id );
		$this->assertSame( $talkId, Title::newFromText( "Talk:$name" )->getArticleID() );
		$this->assertSame( $talkDestinationId,
			Title::newFromText( "Talk:$name 2" )->getArticleID() );
		$this->assertSame( [ [
			'message' => 'articleexists',
			'params' => [ "Talk:$name 2" ],
			'code' => 'articleexists',
			'type' => 'error',
		] ], $res[0]['move']['talkmove-errors'] );

		$this->assertArrayNotHasKey( 'warnings', $res[0] );
	}

	public function testMoveSubpages() {
		$name = ucfirst( __FUNCTION__ );

		$this->mergeMwGlobalArrayValue( 'wgNamespacesWithSubpages', [ NS_MAIN => true ] );

		$pages = [ $name, "$name/1", "$name/2", "Talk:$name", "Talk:$name/1", "Talk:$name/3" ];
		$ids = [];
		foreach ( array_merge( $pages, [ "$name/error", "$name 2/error" ] ) as $page ) {
			$ids[$page] = $this->createPage( $page );
		}

		$res = $this->doApiRequestWithToken( [
			'action' => 'move',
			'from' => $name,
			'to' => "$name 2",
			'movetalk' => '',
			'movesubpages' => '',
		] );

		foreach ( $pages as $page ) {
			$this->assertMoved( $page, str_replace( $name, "$name 2", $page ), $ids[$page] );
		}

		$this->assertSame( $ids["$name/error"],
			Title::newFromText( "$name/error" )->getArticleID() );
		$this->assertSame( $ids["$name 2/error"],
			Title::newFromText( "$name 2/error" )->getArticleID() );

		$results = array_merge( $res[0]['move']['subpages'], $res[0]['move']['subpages-talk'] );
		foreach ( $results as $arr ) {
			if ( $arr['from'] === "$name/error" ) {
				$this->assertSame( [ [
					'message' => 'articleexists',
					'params' => [ "$name 2/error" ],
					'code' => 'articleexists',
					'type' => 'error'
				] ], $arr['errors'] );
			} else {
				$this->assertSame( str_replace( $name, "$name 2", $arr['from'] ), $arr['to'] );
			}
			$this->assertCount( 2, $arr );
		}

		$this->assertArrayNotHasKey( 'warnings', $res[0] );
	}

	public function testMoveNoPermission() {
		$name = ucfirst( __FUNCTION__ );

		$id = $this->createPage( $name );

		$user = new User();

		try {
			$this->doApiRequestWithToken( [
				'action' => 'move',
				'from' => $name,
				'to' => "$name 2",
			], null, $user );
		} catch ( ApiUsageException $ex ) {
			// This one has two errors! So weird
			$this->assertTrue( ApiTestCase::apiExceptionHasCode( $ex, 'cantmove-anon' ) );
			$this->assertTrue( ApiTestCase::apiExceptionHasCode( $ex, 'cantmove' ) );
		} finally {
			$this->assertSame( $id, Title::newFromText( "$name" )->getArticleID() );
			$this->assertFalse( Title::newFromText( "$name 2" )->exists(),
				"\"$name 2\" should not exist" );
		}
	}

	public function testSuppressRedirect() {
		$name = ucfirst( __FUNCTION__ );

		$id = $this->createPage( $name );

		$res = $this->doApiRequestWithToken( [
			'action' => 'move',
			'from' => $name,
			'to' => "$name 2",
			'noredirect' => '',
		] );

		$this->assertMoved( $name, "$name 2", $id, 'noredirect' );
		$this->assertArrayNotHasKey( 'warnings', $res[0] );
	}

	public function testSuppressRedirectNoPermission() {
		$name = ucfirst( __FUNCTION__ );

		$this->setGroupPermissions( 'sysop', 'suppressredirect', false );
		$id = $this->createPage( $name );

		$res = $this->doApiRequestWithToken( [
			'action' => 'move',
			'from' => $name,
			'to' => "$name 2",
			'noredirect' => '',
		] );

		$this->assertMoved( $name, "$name 2", $id );
		$this->assertArrayNotHasKey( 'warnings', $res[0] );
	}

	public function testMoveSubpagesError() {
		$name = ucfirst( __FUNCTION__ );

		// Subpages are allowed in talk but not main
		$idBase = $this->createPage( "Talk:$name" );
		$idSub = $this->createPage( "Talk:$name/1" );

		$res = $this->doApiRequestWithToken( [
			'action' => 'move',
			'from' => "Talk:$name",
			'to' => $name,
			'movesubpages' => '',
		] );

		$this->assertMoved( "Talk:$name", $name, $idBase );
		$this->assertSame( $idSub, Title::newFromText( "Talk:$name/1" )->getArticleID() );
		$this->assertFalse( Title::newFromText( "$name/1" )->exists(),
			"\"$name/1\" should not exist" );

		$this->assertSame( [ 'errors' => [ [
			'message' => 'namespace-nosubpages',
			'params' => [ '' ],
			'code' => 'namespace-nosubpages',
			'type' => 'error',
		] ] ], $res[0]['move']['subpages'] );

		$this->assertArrayNotHasKey( 'warnings', $res[0] );
	}
}
