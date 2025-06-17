<?php
/**
 * File which handle base-methods for all VCS objects.
 *
 * @package easy-updates-from-vcs-for-wordpress
 */

namespace easyUpdatesFromVcsForWordPress;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Object which handle base-methods for all VCS objects.
 */
class VCS_Base {
	/**
	 * The configuration.
	 *
	 * @var array<string,mixed>
	 */
	protected array $config;

	/**
	 * The name.
	 *
	 * @var string
	 */
	protected string $name = '';

	/**
	 * Constructor, not used as this a Singleton object.
	 *
	 * @param array<string,mixed> $config The configuration.
	 */
	public function __construct( array $config ) {
		$this->config = $config;
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

	/**
	 * Run the check for new version on this VCS.
	 *
	 * @return object|bool
	 */
	public function run(): object|bool {
		return false;
	}
}
