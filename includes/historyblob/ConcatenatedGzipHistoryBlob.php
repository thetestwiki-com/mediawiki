<?php
/**
 * Efficient concatenated text storage.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 */

/**
 * Concatenated gzip (CGZ) storage
 * Improves compression ratio by concatenating like objects before gzipping
 */
class ConcatenatedGzipHistoryBlob implements HistoryBlob {
	public $mVersion = 0;
	public $mCompressed = false;
	/**
	 * @var array|string
	 * @fixme Why are some methods treating it as an array, and others as a string, unconditionally?
	 */
	public $mItems = [];
	public $mDefaultHash = '';
	public $mSize = 0;
	public $mMaxSize = 10000000;
	public $mMaxCount = 100;

	public function __construct() {
		if ( !function_exists( 'gzdeflate' ) ) {
			throw new MWException( "Need zlib support to read or write this "
				. "kind of history object (ConcatenatedGzipHistoryBlob)\n" );
		}
	}

	/**
	 * @param string $text
	 * @return string
	 */
	public function addItem( $text ) {
		$this->uncompress();
		$hash = md5( $text );
		if ( !isset( $this->mItems[$hash] ) ) {
			$this->mItems[$hash] = $text;
			$this->mSize += strlen( $text );
		}
		return $hash;
	}

	/**
	 * @param string $hash
	 * @return array|bool
	 */
	public function getItem( $hash ) {
		$this->uncompress();
		if ( array_key_exists( $hash, $this->mItems ) ) {
			return $this->mItems[$hash];
		} else {
			return false;
		}
	}

	/**
	 * @param string $text
	 * @return void
	 */
	public function setText( $text ) {
		$this->uncompress();
		$this->mDefaultHash = $this->addItem( $text );
	}

	/**
	 * @return array|bool
	 */
	public function getText() {
		$this->uncompress();
		return $this->getItem( $this->mDefaultHash );
	}

	/**
	 * Remove an item
	 *
	 * @param string $hash
	 */
	public function removeItem( $hash ) {
		$this->mSize -= strlen( $this->mItems[$hash] );
		unset( $this->mItems[$hash] );
	}

	/**
	 * Compress the bulk data in the object
	 */
	public function compress() {
		if ( !$this->mCompressed ) {
			$this->mItems = gzdeflate( serialize( $this->mItems ) );
			$this->mCompressed = true;
		}
	}

	/**
	 * Uncompress bulk data
	 */
	public function uncompress() {
		if ( $this->mCompressed ) {
			$this->mItems = unserialize( gzinflate( $this->mItems ) );
			$this->mCompressed = false;
		}
	}

	/**
	 * @return array
	 */
	public function __sleep() {
		$this->compress();
		return [ 'mVersion', 'mCompressed', 'mItems', 'mDefaultHash' ];
	}

	public function __wakeup() {
		$this->uncompress();
	}

	/**
	 * Helper function for compression jobs
	 * Returns true until the object is "full" and ready to be committed
	 *
	 * @return bool
	 */
	public function isHappy() {
		return $this->mSize < $this->mMaxSize
			&& count( $this->mItems ) < $this->mMaxCount;
	}
}

// Blobs generated by MediaWiki < 1.5 on PHP 4 were serialized with the
// class name coerced to lowercase. We can improve efficiency by adding
// autoload entries for the lowercase variants of these classes (T166759).
// The code below is never executed, but it is picked up by the AutoloadGenerator
// parser, which scans for class_alias() calls.
/*
class_alias( ConcatenatedGzipHistoryBlob::class, 'concatenatedgziphistoryblob' );
*/
