<?php
/**
 * File for handling the update of a theme.
 *
 * @package easy-updates-from-vcs-for-wordpress
 */

namespace easyUpdatesFromVcsForWordPress\Type;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use easyUpdatesFromVcsForWordPress\Types_Base;

/**
 * Object to handle updates of a theme.
 */
class Theme extends Types_Base {
	/**
	 * Initialize this object.
	 *
	 * @return void
	 */
	public function init(): void {
		// initialize the VCS-object.
		$this->vcs_handler->init();

		// use hooks.
		add_filter( 'pre_set_site_transient_update_themes', array( $this, 'check' ), 100, 1 );
	}

	/**
	 * Get update infos external repository.
	 *
	 * @param object $data The object with the theme-data.
	 * @return object
	 */
	public function check( object $data ): object {
		// bail if we are in development mode.
		if ( wp_is_development_mode( 'theme' ) ) {
			return $data;
		}

		// send request for update info.
		$response = $this->vcs_handler->run();

		// define our own theme information.
		$theme_dtls = array(
			'theme_name' => get_option( 'stylesheet' ),
			'theme_slug' => 'fraunhofer-fokus',
		);

		// get the version of the current theme.
		$current = wp_get_theme()->get( 'Version' );

		// get contents as array.
		$file = json_decode( $response );

		// format the theme-data from GitHub to WordPress object.
		if ( $file ) {
			// bail if no asset is available.
			if ( empty( $file->assets ) ) {
				return $data;
			}

			// get version from GitHub.
			$update = preg_replace( '/[^0-9.]/', '', $file->tag_name );

			// only return a response if the new version number is higher than the current version.
			if ( version_compare( $update, $current, '>' ) ) {
				$data->response[ $theme_dtls['theme_slug'] ] = array(
					'theme'       => $theme_dtls['theme_name'],
					'new_version' => $update,
					'url'         => 'https://gitlab.fokus.fraunhofer.de/api/v4/projects/12046/releases/permalink/latest',
					'package'     => $file->assets->links['0']->url,
					'slug'        => $theme_dtls['theme_slug'],
				);
			}
		}

		// rename the theme folder.
		if ( $theme_dtls['theme_slug'] !== $theme_dtls['theme_name'] ) {
			// get WP Filesystem-handler.
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
			global $wp_filesystem;

			// set new directory name.
			$new_dir = $theme_dtls['theme_dir'] . '/' . $theme_dtls['theme_slug'];

			// rename the folder.
			$wp_filesystem->move( get_stylesheet_directory(), $new_dir );
		}

		// return resulting data.
		return $data;
	}
}
