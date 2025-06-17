<?php
/**
 * File to handle updates from GitLab for this theme.
 *
 * @package easy-updates-from-vcs-for-wordpress
 */

namespace easyUpdatesFromVcsForWordPress\VCS;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use easyUpdatesFromVcsForWordPress\VCS_Base;
use stdClass;

/**
 * Object to handle updates from GitLab for this theme.
 */
class GitLab extends VCS_Base {
	/**
	 * The name.
	 *
	 * @var string
	 */
	protected string $name = 'GitLab';

	/**
	 * Initialize this object.
	 *
	 * @return void
	 */
	public function init(): void {
		add_filter( 'http_request_args', array( $this, 'set_domain_capability' ), 10, 2 );
	}

	/**
	 * Run the check for new version on this VCS.
	 *
	 * @link https://github.com/settings/personal-access-tokens
	 *
	 * @return object|bool
	 */
	public function run(): object|bool {
		// get the 3 necessary values.
		$gitlab_server     = $this->config[1]->server;
		$gitlab_token      = $this->config[1]->token;
		$github_project_id = $this->config[1]->project_id;

		// bail if one value is missing.
		if ( empty( $gitlab_server ) || empty( $gitlab_token ) || empty( $github_project_id ) ) {
			return false;
		}

		// create URL for request.
		$url = $gitlab_server . '/api/v4/projects/' . $github_project_id . '/releases/permalink/latest';

		// create HTTP header.
		$args     = array(
			'method'      => 'GET',
			'httpversion' => '1.1',
			'timeout'     => 30,
			'redirection' => 10,
			'headers'     => array(
				'PRIVATE-TOKEN' => $gitlab_token,
			),
			'body'        => array(),
		);
		$response = wp_remote_get( $url, $args );

		// bail on error.
		if ( is_wp_error( $response ) ) {
			return false;
		}

		// bail if http status is not 200.
		if ( 200 !== absint( wp_remote_retrieve_response_code( $response ) ) ) {
			return false;
		}

		// create the return object.
		$obj               = new stdClass();
		$obj->status       = absint( wp_remote_retrieve_response_code( $response ) );
		$obj->requires_php = 'xy'; // TODO.
		$obj->requires     = 'xy';  // TODO.
		$obj->tested       = 'xy'; // TODO.
		$obj->download_url = 'xy'; // TODO.

		// return the object.
		return $obj;
	}

	/**
	 * Set GitHub credentials for running update from GitHub.
	 *
	 * @param array  $parsed_args List of arguments.
	 * @param string $url The requested URL.
	 * @return mixed
	 */
	public function set_domain_capability( array $parsed_args, string $url ): mixed {
		// bail if the filter has already been applied to the current request.
		if ( isset( $parsed_args['fraunhofer_fokus_http_request_args_modified'] ) ) {
			return $parsed_args;
		}

		// get the 3 necessary values.
		$gitlab_server     = get_option( 'fraunhofer_fokus_gitlab_server' );
		$gitlab_token      = get_option( 'fraunhofer_fokus_gitlab_token' );
		$github_project_id = get_option( 'fraunhofer_fokus_gitlab_project_id' );

		// bail if one value is missing.
		if ( empty( $gitlab_server ) || empty( $gitlab_token ) || empty( $github_project_id ) ) {
			return $parsed_args;
		}

		// mark the parsed_args to indicate that the filter has been applied.
		$parsed_args['fraunhofer_fokus_http_request_args_modified'] = true;

		// if the repo is requested, add the credentials to the request.
		if ( false !== str_contains( $url, $gitlab_server ) ) {
			$headers                           = array(
				'PRIVATE-TOKEN' => $gitlab_token,
			);
			$parsed_args['headers']            = $headers;
			$parsed_args['reject_unsafe_urls'] = false;
		}

		// if the download is requested, set the header for this task.
		if ( false !== str_contains( $url, $gitlab_server . '/api/v4/projects/12046/packages/generic/fraunhofer-fokus/' ) ) {
			$parsed_args['headers']['Accept'] = 'application/octet-stream';
		}

		// return resulting arguments.
		return $parsed_args;
	}
}
