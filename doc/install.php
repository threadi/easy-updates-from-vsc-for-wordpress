<?php
/**
 * Example how to integration this package in your project.
 *
 * @package easy-updates-from-vcs-for-wordpress
 */

add_action(
	'init',
	function () {
		\easyUpdatesFromVcsForWordPress\Updates::get_instance()->init();
	}
);
