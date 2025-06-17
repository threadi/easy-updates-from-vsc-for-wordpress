<?php
/**
 * File to handle updates from GitHub for this theme.
 *
 * @package easy-updates-from-vcs-for-wordpress
 */

namespace easyUpdatesFromVcsForWordPress\VCS;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use easyUpdatesFromVcsForWordPress\VCS_Base;
use JsonException;

/**
 * Object to handle updates from GitHub for this theme.
 */
class GitHub extends VCS_Base {
	/**
	 * The name.
	 *
	 * @var string
	 */
	protected string $name = 'GitHub';

	/**
	 * Initialize this object.
	 *
	 * @return void
	 */
	public function init(): void {
		global $wp_version;
		if ( version_compare( $wp_version, '5.1.0', '>' ) ) {
			add_filter( 'http_request_reject_unsafe_urls', array( $this, 'allow_own_safe_domain' ), 10, 2 );
		} else {
			add_filter( 'http_request_reject_unsafe_urls', '__return_false' );
		}

		// misc.
		add_filter( 'http_request_args', array( $this, 'allow_own_domain' ), 10, 2 );
	}

	/**
	 * Allow the GitHub-URL for requests during update.
	 *
	 * @param array<string,mixed> $parsed_args The arguments for the request.
	 * @param string              $url The URL to be requested.
	 *
	 * @return array<string,mixed>
	 */
	public function allow_own_domain( array $parsed_args, string $url ): array {
		if ( strpos( $url, wp_parse_url( 'https://api.github.com', PHP_URL_HOST ) ) ) {
			$parsed_args['reject_unsafe_urls'] = false;
		}
		return $parsed_args;
	}

	/**
	 * Allow the GitHub-URL for requests during update.
	 *
	 * @param bool   $return_value True if the domain in the URL is safe.
	 * @param string $url The requested URL.
	 *
	 * @return bool
	 */
	public function allow_own_safe_domain( bool $return_value, string $url ): bool {
		if ( strpos( $url, wp_parse_url( 'https://api.github.com', PHP_URL_HOST ) ) ) {
			return true;
		}
		return $return_value;
	}

	/**
	 * Run the check for new version on this VCS.
	 *
	 * @return object|bool
	 */
	public function run(): object|bool {
		// get the actual cached data from update server.
		$remote = get_transient( md5( wp_json_encode( $this->config ) ) );

		if ( false === $remote ) {
			// create URL for request.
			$url = 'https://api.github.com/repos/' . $this->config[1]->user . '/' . $this->config[1]->repository . '/releases/latest';

			// create HTTP header.
			$args     = array(
				'method'      => 'GET',
				'httpversion' => '1.1',
				'timeout'     => 30,
				'redirection' => 0,
				'headers'     => array(
					'User-Agent: ' . $this->config[1]->user,
					'Authorization: token ' . $this->config[1]->key,
					'Accept: application/json',
				),
				'body'        => array(),
			);
			$response = wp_remote_get( $url, $args );

			if (
				is_wp_error( $response )
				|| 200 !== wp_remote_retrieve_response_code( $response )
				|| empty( wp_remote_retrieve_body( $response ) )
			) {
				// set cache key with 1 hour to prevent loop on error.
				set_transient( md5( wp_json_encode( $this->config ) ), $response, HOUR_IN_SECONDS );

				// return false as we got no usable data.
				return false;
			}
			set_transient( md5( wp_json_encode( $this->config ) ), $response, DAY_IN_SECONDS );
		}

		// return boolean direct.
		if ( is_bool( $remote ) ) {
			return $remote;
		}

		// return false if result is a wp-error as we got no usable data.
		if ( is_wp_error( $remote ) ) {
			return false;
		}

		// return the cached results from update-server.
		try {
			return json_decode( wp_remote_retrieve_body( $remote ), false, 512, JSON_THROW_ON_ERROR );
		} catch ( JsonException $e ) {
			return false;
		}
	}
}
