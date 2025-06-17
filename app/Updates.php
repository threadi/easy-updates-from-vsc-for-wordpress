<?php
/**
 * File to handle updates from VCS for plugins and themes.
 *
 * @package easy-updates-from-vsc-for-wordpress
 */

namespace easyUpdatesFromVcsForWordPress;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use Dallgoot\Yaml\Types\YamlObject;
use Dallgoot\Yaml\Yaml;

/**
 * Object to handle updates for this theme.
 */
class Updates {
	/**
	 * The configuration.
	 *
	 * @var array<string,mixed>
	 */
	private array $config = array();

	/**
	 * Instance of actual object.
	 *
	 * @var Updates|null
	 */
	private static ?Updates $instance = null;

	/**
	 * Constructor, not used as this a Singleton object.
	 */
	private function __construct() {}

	/**
	 * Prevent cloning of this object.
	 *
	 * @return void
	 */
	private function __clone() { }

	/**
	 * Return instance of this object as singleton.
	 *
	 * @return Updates
	 */
	public static function get_instance(): Updates {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize the configured update handler.
	 *
	 * @return void
	 */
	public function init(): void {
		// get the root-path of the plugin or theme where this composer package resides.
		$plugin_path = __DIR__ . '/../../../../';

		// get path to our config file.
		$config_path = $plugin_path . 'eufvfw.yml';

		// bail if config file does not exist.
		if ( ! file_exists( $config_path ) ) {
			return;
		}

		// read the file.
		try {
			$yaml = Yaml::parseFile( $config_path, 0, 0 );
		} catch ( \Exception $e ) {
			return;
		}

		// bail if object could not be loaded.
		if ( ! $yaml instanceof YamlObject ) {
			return;
		}

		// get the configuration.
		/** @noinspection PhpUndefinedFieldInspection */
		$this->config = $yaml->config;

		// bail if no config is set.
		if ( empty( $this->config ) ) {
			return;
		}

		// get the active update handler.
		$update_handler = false;
		foreach ( $this->get_update_handler() as $obj_name ) {
			// get the object.
			$obj = new $obj_name( $this->config );

			// bail if this handler is not our type.
			if ( ! $obj instanceof VCS_Base ) {
				continue;
			}

			// bail if the object does not match the configured name.
			if ( $this->config[1]->source !== $obj->get_name() ) {
				continue;
			}

			// assign this handler.
			$update_handler = $obj;
		}

		// bail if no update handler could be found.
		if ( ! $update_handler instanceof VCS_Base ) {
			return;
		}

		// get the active type handler.
		foreach ( $this->get_type_handler() as $obj_name ) {
			// get the object.
			$obj = new $obj_name( $this->config, $update_handler );

			// bail if this handler is not our type.
			if ( ! $obj instanceof Types_Base ) {
				continue;
			}

			// bail if the object does not match the configured name.
			if ( $this->config[0]->type !== $obj->get_name() ) {
				continue;
			}

			// initialize this update handler.
			$obj->init();

			// prevent any further initialization.
			return;
		}
	}

	/**
	 * Return list of supported update handlers.
	 *
	 * @return array<int,string>
	 */
	private function get_update_handler(): array {
		return array(
			'\easyUpdatesFromVcsForWordPress\VCS\GitHub',
			'\easyUpdatesFromVcsForWordPress\VCS\GitLab',
		);
	}

	/**
	 * Return list of supported type handlers.
	 *
	 * @return array<int,string>
	 */
	private function get_type_handler(): array {
		return array(
			'\easyUpdatesFromVcsForWordPress\Type\Plugin',
			'\easyUpdatesFromVcsForWordPress\Type\Theme',
		);
	}
}
