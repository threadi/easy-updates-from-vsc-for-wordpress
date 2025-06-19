<?php
/**
 * File which handle base-methods for all type objects.
 *
 * @package easy-updates-from-vcs-for-wordpress
 */

namespace easyUpdatesFromVcsForWordPress;

// prevent direct access.
use Dallgoot\Yaml\Types\YamlObject;

defined( 'ABSPATH' ) || exit;

/**
 * Object which handle base-methods for all type objects.
 */
class Types_Base {
	/**
	 * The configuration.
	 *
	 * @var YamlObject
	 */
	protected YamlObject $config;

	/**
	 * The configuration.
	 *
	 * @var VCS_Base
	 */
	protected VCS_Base $vcs_handler;

	/**
	 * The name.
	 *
	 * @var string
	 */
	protected string $name = '';

	/**
	 * Constructor, not used as this a Singleton object.
	 *
	 * @param YamlObject $config The configuration.
	 * @param VCS_Base            $vcs_handler The VCS handler to use.
	 */
	public function __construct( YamlObject $config, VCS_Base $vcs_handler ) {
		$this->config      = $config;
		$this->vcs_handler = $vcs_handler;
	}

	/**
	 * Return the slug.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Initialize this object.
	 *
	 * @return void
	 */
	public function init(): void {}
}
