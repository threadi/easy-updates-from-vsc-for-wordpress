<?php
/**
 * File for handling the update of a plugin.
 *
 * @package easy-updates-from-vcs-for-wordpress
 */

namespace easyUpdatesFromVcsForWordPress\Type;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use easyUpdatesFromVcsForWordPress\Types_Base;
use stdClass;
use WP_Upgrader;

/**
 * Object to handle updates of a plugin.
 */
class Plugin extends Types_Base {
	/**
	 * Set name of this object.
	 *
	 * @var string
	 */
	protected string $name = 'Plugin';

	/**
	 * Initialize this object.
	 *
	 * @return void
	 */
	public function init(): void {
		// initialize the VCS-object.
		$this->vcs_handler->init();

		// use hooks.
		add_filter( 'plugins_api', array( $this, 'get_info' ), 20, 3 );
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check' ), 100, 1 );
		//add_filter( 'site_transient_update_plugins', array( $this, 'run_update' ) );
		//add_action( 'upgrader_process_complete', array( $this, 'purge_transient_after_update' ), 10, 2 );
		add_filter( 'all_plugins', array( $this, 'add_slug_to_plugin_in_list' ), 9999 );
		add_filter( $this->config->type[1]->slug . '_update_transient', array( $this, 'enable_auto_update_support' ) );
	}

	/**
	 * Check for update.
	 *
	 * @param object $data
	 *
	 * @return object
	 */
	public function check( object $data ): object {
		// bail if we are in development mode.
		if ( wp_is_development_mode( 'plugin' ) ) {
			//return $data;
		}

		// send request for update info.
		$response = $this->vcs_handler->run();

		// format the theme-data from GitHub to WordPress object.
		if ( $response ) {
			// bail if no asset is available.
			if ( empty( $response->assets ) ) {
				return $data;
			}

			// embed the plugin data function.
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$plugin_data = \get_plugin_data( WP_PLUGIN_DIR . '/' . $this->config->type[1]->slug . '/' . $this->config->type[2]->file, false, false );

			// get version from VCS.
			$update = preg_replace( '/[^0-9.]/', '', $response->tag_name );

			// only return a response if the new version number is higher than the current version.
			if ( version_compare( $update, $plugin_data['Version'], '>' ) ) {
				$res              = new stdClass();
				$res->slug        = $this->config->type[1]->slug; // @phpstan-ignore property.notFound
				$res->plugin      = plugin_basename( $this->config->type[1]->slug . '/' . $this->config->type[2]->file );
				$res->new_version = $response->tag_name; // @phpstan-ignore property.notFound
				$res->package     = $response->assets[0]->url; // @phpstan-ignore property.notFound
				$data->response[ $this->config->type[1]->slug . '/' . $this->config->type[2]->file ] = $res;
			}
		}

		return $data;
	}

	/**
	 * Get info about the version.
	 *
	 * @param mixed  $res The resource information.
	 * @param string $action The action taken.
	 * @param object $args The arguments used.
	 * @return false|object
	 * @noinspection PhpUnused
	 */
	public function get_info( mixed $res, string $action, object $args ): mixed {
		// do nothing if you're not getting plugin information right now.
		if ( 'plugin_information' !== $action ) {
			return $res;
		}

		// do nothing if it is not our plugin.
		if ( basename( $this->config->type[1]->slug ) !== $args->slug ) { // @phpstan-ignore property.notFound
			return $res;
		}

		// get update-info.
		$response = $this->vcs_handler->run();

		// bail if no update info available.
		if ( is_bool( $response ) ) {
			return $res;
		}

		// embed the plugin data function.
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$plugin_data = \get_plugin_data( WP_PLUGIN_DIR . '/' . $this->config->type[1]->slug . '/' . $this->config->type[2]->file, false, false );

		$res = new stdClass();
		$res->name           = $plugin_data['Name']; // @phpstan-ignore property.notFound
		$res->slug           = $this->config->type[1]->slug; // @phpstan-ignore property.notFound
		$res->version        = $response->tag_name; // @phpstan-ignore property.notFound
		$res->author         = $response->author->login; // @phpstan-ignore property.notFound
		$res->author_profile = $response->author->url; // @phpstan-ignore property.notFound
		$res->download_link  = $response->assets[0]->browser_download_url; // @phpstan-ignore property.notFound
		$res->trunk          = $response->assets[0]->browser_download_url; // @phpstan-ignore property.notFound
		$res->requires_php   = $plugin_data['RequiresWP']; // @phpstan-ignore property.notFound
		$res->last_updated   = $response->assets[0]->created_at; // @phpstan-ignore property.notFound

		$res->sections = array(
			'description'  => nl2br( $plugin_data['Description'] ), // @phpstan-ignore property.notFound
		);

		// return resulting resource object with the plugin data.
		return $res;
	}

	/**
	 * Run the update of this plugin.
	 *
	 * @param stdClass|false $transient The requested transient.
	 *
	 * @return stdClass|false
	 */
	public function run_update( stdClass|false $transient ): stdClass|false {
		// bail if transient is false.
		if ( false === $transient ) {
			return false;
		}

		// bail if transient is not checked.
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		// do nothing in development mode.
		if ( function_exists( 'wp_is_development_mode' ) && false !== wp_is_development_mode( 'plugin' ) ) {
			//return $transient;
		}

		// get the request.
		$remote = $this->vcs_handler->run();

		// bail if request responses with bool.
		if ( is_bool( $remote ) ) {
			return $transient;
		}

		// bail if status is not 200.
		if ( 200 !== absint( $remote->status ) ) {
			return $transient;
		}

		// embed the plugin data function.
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$plugin_data = \get_plugin_data( WP_PLUGIN_DIR . '/' . $this->config[0]->slug . '/' . $this->config[0]->file, false, false );

		if (
			version_compare( $plugin_data['Version'], $remote->version, '<' ) // @phpstan-ignore property.notFound
			&& version_compare( $remote->requires_php, PHP_VERSION, '<' ) // @phpstan-ignore property.notFound
			&& version_compare( $remote->requires, get_bloginfo( 'version' ), '<=' ) // @phpstan-ignore property.notFound
		) {
			$res              = new stdClass();
			$res->slug        = $remote->slug; // @phpstan-ignore property.notFound
			$res->plugin      = plugin_basename( $this->config[0]->slug );
			$res->new_version = $remote->version; // @phpstan-ignore property.notFound
			$res->tested      = $remote->tested; // @phpstan-ignore property.notFound
			$res->package     = $remote->download_url; // @phpstan-ignore property.notFound

			$transient->response[ $res->plugin ]  = $res;
			$transient->no_update[ $res->plugin ] = $res;
		}

		// return the resulting transient value.
		return $transient;
	}

	/**
	 * Reset transients for plugin-specific updating.
	 *
	 * @param WP_Upgrader          $upgrader The WP_Upgrader object.
	 * @param array<string,string> $options List of options.
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function purge_transient_after_update( WP_Upgrader $upgrader, array $options ): void {
		if (
			$this->config[2]->cache
			&& 'update' === $options['action']
			&& 'plugin' === $options['type']
		) {
			// just clean the cache when new plugin version is installed.
			delete_transient( md5( wp_json_encode( $this->config ) ) );
		}
	}

	/**
	 * Add our own slug to our plugin in the plugin-list.
	 * This is necessary to show "view details"-link.
	 *
	 * @param array<string,mixed> $plugins List of plugins.
	 *
	 * @return array<string,mixed>
	 */
	public function add_slug_to_plugin_in_list( array $plugins ): array {
		$plugin_name = plugin_basename( $this->config->type[1]->slug . '/' . $this->config->type[2]->file );
		if ( ! empty( $plugins[ $plugin_name ] ) ) {
			$plugins[ $plugin_name ]['slug'] = basename( $plugin_name );
		}
		return $plugins;
	}

	/**
	 * Update the transient for our plugin to enabled auto update.
	 *
	 * @param stdClass $transient The transient with all plugin configurations.
	 *
	 * @return stdClass
	 */
	public function enable_auto_update_support( stdClass $transient ): stdClass {
		// add entry if our plugin is not in list.
		if ( empty( $transient->no_update[ plugin_basename( $this->config->type[1]->slug . '/' . $this->config->type[2]->file ) ] ) ) {
			$transient->no_update[ plugin_basename( $this->config->type[1]->slug . '/' . $this->config->type[2]->file ) ] = new stdClass();
		}

		// add the setting.
		$transient->no_update[ plugin_basename( $this->config->type[1]->slug . '/' . $this->config->type[2]->file ) ]->{'enable-support'} = true;

		// return the resulting list.
		return $transient;
	}
}
