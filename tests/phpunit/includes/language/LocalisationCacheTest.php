<?php

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Tests\Unit\DummyServicesTrait;
use Psr\Log\NullLogger;
use Wikimedia\TestingAccessWrapper;

/**
 * @group Database
 * @group Cache
 * @covers LocalisationCache
 * @author Niklas Laxström
 */
class LocalisationCacheTest extends MediaWikiIntegrationTestCase {
	use DummyServicesTrait;

	/**
	 * @param array $hooks Hook overrides
	 * @param array $options Service options (see {@link LocalisationCache::CONSTRUCTOR_OPTIONS})
	 * @return LocalisationCache
	 */
	protected function getMockLocalisationCache( $hooks = [], $options = [] ) {
		global $IP;

		$hookContainer = $this->createHookContainer( $hooks );

		// in case any of the LanguageNameUtils hooks are being used
		$langNameUtils = $this->getDummyLanguageNameUtils(
			[ 'hookContainer' => $hookContainer ]
		);

		$options += [
			'forceRecache' => false,
			'manualRecache' => false,
			'ExtensionMessagesFiles' => [],
			'MessagesDirs' => [],
		];

		$lc = $this->getMockBuilder( LocalisationCache::class )
			->setConstructorArgs( [
				new ServiceOptions( LocalisationCache::CONSTRUCTOR_OPTIONS, $options ),
				new LCStoreDB( [] ),
				new NullLogger,
				[],
				$langNameUtils,
				$hookContainer
			] )
			->onlyMethods( [ 'getMessagesDirs' ] )
			->getMock();
		$lc->method( 'getMessagesDirs' )
			->willReturn( [ "$IP/tests/phpunit/data/localisationcache" ] );

		return $lc;
	}

	public function testPluralRulesFallback() {
		$cache = $this->getMockLocalisationCache();

		$this->assertEquals(
			$cache->getItem( 'ar', 'pluralRules' ),
			$cache->getItem( 'arz', 'pluralRules' ),
			'arz plural rules (undefined) fallback to ar (defined)'
		);

		$this->assertEquals(
			$cache->getItem( 'ar', 'compiledPluralRules' ),
			$cache->getItem( 'arz', 'compiledPluralRules' ),
			'arz compiled plural rules (undefined) fallback to ar (defined)'
		);

		$this->assertNotEquals(
			$cache->getItem( 'ksh', 'pluralRules' ),
			$cache->getItem( 'de', 'pluralRules' ),
			'ksh plural rules (defined) dont fallback to de (defined)'
		);

		$this->assertNotEquals(
			$cache->getItem( 'ksh', 'compiledPluralRules' ),
			$cache->getItem( 'de', 'compiledPluralRules' ),
			'ksh compiled plural rules (defined) dont fallback to de (defined)'
		);
	}

	public function testRecacheFallbacks() {
		$lc = $this->getMockLocalisationCache();
		$lc->recache( 'ba' );
		$this->assertEquals(
			[
				'present-ba' => 'ba',
				'present-ru' => 'ru',
				'present-en' => 'en',
			],
			$lc->getItem( 'ba', 'messages' ),
			'Fallbacks are only used to fill missing data'
		);
	}

	public function testRecacheFallbacksWithHooks() {
		// Use hook to provide updates for messages. This is what the
		// LocalisationUpdate extension does. See T70781.

		$lc = $this->getMockLocalisationCache( [
			'LocalisationCacheRecacheFallback' =>
				static function (
					LocalisationCache $lc,
					$code,
					array &$cache
				) {
					if ( $code === 'ru' ) {
						$cache['messages']['present-ba'] = 'ru-override';
						$cache['messages']['present-ru'] = 'ru-override';
						$cache['messages']['present-en'] = 'ru-override';
					}
				}
		] );
		$lc->recache( 'ba' );
		$this->assertEquals(
			[
				'present-ba' => 'ba',
				'present-ru' => 'ru-override',
				'present-en' => 'ru-override',
			],
			$lc->getItem( 'ba', 'messages' ),
			'Updates provided by hooks follow the normal fallback order.'
		);
	}

	public function testRecacheExtensionMessagesFiles(): void {
		global $IP;

		$lc = $this->getMockLocalisationCache( [], [
			'ExtensionMessagesFiles' => [
				__METHOD__ => "$IP/tests/phpunit/data/localisationcache/ExtensionMessagesFiles.php",
			]
		] );
		$lc->recache( 'de' );
		$specialPageAliases = $lc->getItem( 'de', 'specialPageAliases' );
		$this->assertSame(
			[ 'LokalisierungsPufferTest' ],
			$specialPageAliases['LocalisationCacheTest'],
			'specialPageAliases can be set in ExtensionMessagesFiles'
		);
		$this->assertSame(
			[ 'Aktive_Benutzer*innen', 'Aktive_Benutzer', 'ActiveFolx', 'ActiveUsers' ],
			$specialPageAliases['Activeusers'],
			'specialPageAliases from extension/core files and fallback languages are merged'
		);
		$this->assertFalse(
			$lc->getItem( 'de', 'rtl' ),
			'rtl cannot be set in ExtensionMessagesFiles'
		);
	}

	public function testLoadCoreDataAvoidsInitLanguage(): void {
		$lc = $this->getMockLocalisationCache();

		$lc->getItem( 'de', 'fallback' );
		$lc->getItem( 'de', 'rtl' );
		$lc->getItem( 'de', 'fallbackSequence' );
		$lc->getItem( 'de', 'originalFallbackSequence' );

		$this->assertArrayNotHasKey( 'de',
			TestingAccessWrapper::newFromObject( $lc )->initialisedLangs );
	}

	public function testShallowFallbackForInvalidCode(): void {
		$lc = $this->getMockLocalisationCache();
		$invalidCode = '!invalid!';

		$this->assertSame( false, $lc->getItem( $invalidCode, 'rtl' ) );
		$this->assertSame( 'windows-1252', $lc->getItem( $invalidCode, 'fallback8bitEncoding' ) );
	}
}
