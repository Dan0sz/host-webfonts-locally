<?php
/**
 * OMGF - Cache
 */

namespace OMGF\Tests\Integration;

use OMGF\Cache;
use OMGF\Compatibility\CachingPlugins;
use OMGF\Tests\TestCase;

class CacheTest extends TestCase {
	/** @var string $dir A folder inside OMGF's cache folder, for this test's files. */
	private $dir;

	/** @var callable $keep_others Limits flushes to this test's folder. */
	private $keep_others;

	public function set_up() {
		parent::set_up();

		$this->dir = OMGF_UPLOAD_DIR . '/cache-test';

		/**
		 * Other entries in OMGF's cache folder (and its database entries) are kept, so other tests aren't affected.
		 */
		$this->keep_others = function ( $instructions ) {
			$instructions['exclude'] = array_values(
				array_diff( array_filter( (array) glob( OMGF_UPLOAD_DIR . '/*' ) ), [ $this->dir ] )
			);
			$instructions['queue']   = [];

			return $instructions;
		};

		add_filter( 'omgf_clean_up_instructions', $this->keep_others );
		add_filter( 'omgf_cache_keys', [ $this, 'keepOtherFolders' ] );
	}

	public function tear_down() {
		remove_filter( 'omgf_clean_up_instructions', $this->keep_others );
		remove_filter( 'omgf_cache_keys', [ $this, 'keepOtherFolders' ] );

		if ( is_dir( $this->dir ) ) {
			\OMGF\Helper::delete( $this->dir );
		}

		parent::tear_down();
	}

	/**
	 * Every folder in OMGF's cache folder is considered a known cache key, except this test's folder, so flushing
	 * 3rd party stylesheets only removes this test's folder.
	 *
	 * @param array $cache_keys
	 *
	 * @return array
	 */
	public function keepOtherFolders( $cache_keys ) {
		$folders = array_map( 'basename', array_filter( (array) glob( OMGF_UPLOAD_DIR . '/*', GLOB_ONLYDIR ) ) );

		return array_merge( $cache_keys, array_diff( $folders, [ basename( $this->dir ) ] ) );
	}

	/**
	 * When files are deleted from OMGF's cache folder (e.g. by Empty Cache), pages cached by a page cache still refer
	 * to them, so page caches are flushed. If there was nothing to delete, they aren't.
	 *
	 * @see Cache::flush()
	 * @see CachingPlugins::flush_page_caches()
	 */
	public function testFlushFlushesPageCaches() {
		$purges = 0;
		$purge  = function () use ( &$purges ) {
			$purges++;
		};

		// FlyingPress' purge action, used to count page cache flushes.
		add_action( 'flying_press_purge_all', $purge );

		/**
		 * Only this instance should flush, so the one OMGF registered (if any) is set aside. No capabilities are
		 * checked, because this also happens during e.g. WordPress' auto-updates.
		 */
		global $wp_filter;

		$registered = $wp_filter['omgf_cache_files_deleted'] ?? null;

		unset( $wp_filter['omgf_cache_files_deleted'] );
		wp_set_current_user( 0 );

		$caching_plugins = new CachingPlugins();

		try {
			wp_mkdir_p( $this->dir );
			file_put_contents( "$this->dir/style.css", 'x' );

			( new Cache() )->flush( 'test' );

			$this->assertDirectoryDoesNotExist( $this->dir );
			$this->assertSame( 1, $purges );

			// Nothing to delete.
			( new Cache() )->flush( 'test' );

			$this->assertSame( 1, $purges );
		} finally {
			remove_action( 'flying_press_purge_all', $purge );
			remove_action( 'omgf_optimize_succeeded', [ $caching_plugins, 'maybe_flush_cache' ] );

			unset( $wp_filter['omgf_cache_files_deleted'] );

			if ( $registered ) {
				$wp_filter['omgf_cache_files_deleted'] = $registered;
			}
		}
	}

	/**
	 * When 3rd party stylesheets are deleted from OMGF's cache folder (e.g. after updating a theme or plugin), page
	 * caches are flushed. If there was nothing to delete, they aren't.
	 *
	 * @see Cache::flush_third_party()
	 * @see CachingPlugins::flush_page_caches()
	 */
	public function testFlushThirdPartyFlushesPageCaches() {
		$deleted = did_action( 'omgf_cache_files_deleted' );

		// Nothing to delete.
		( new Cache() )->flush_third_party();

		$this->assertSame( $deleted, did_action( 'omgf_cache_files_deleted' ) );

		wp_mkdir_p( $this->dir );
		file_put_contents( "$this->dir/style.css", 'x' );

		( new Cache() )->flush_third_party();

		$this->assertDirectoryDoesNotExist( $this->dir );
		$this->assertSame( $deleted + 1, did_action( 'omgf_cache_files_deleted' ) );
	}
}
