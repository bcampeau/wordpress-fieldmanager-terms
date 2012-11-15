<?php
/**
 * @package Fieldmanager
 * @subpackage Terms
 * @version 0.1
 */
/*
Plugin Name: Fieldmanager Terms
Plugin URI: http://github.com/bcampeau/fieldmanager-terms
Description: Adds automatic term extraction from post content for Fieldmanager taxonomy-based fields
Author: Bradford Campeau-Laurion
Version: 0.1
Author URI: http://www.alleyinteractive.com/
*/

require_once( dirname( __FILE__ ) . '/php/class-fieldmanager-terms.php' );
require_once( dirname( __FILE__ ) . '/php/class-plugin-dependency.php' );

function fieldmanager_terms_dependency() {
	$fieldmanager_dependency = new Plugin_Dependency( 'Fieldmanager Terms', 'Fieldmanager', 'https://github.com/netaustin/wordpress-fieldmanager' );
	if( !$fieldmanager_dependency->verify() ) {
		// Cease activation
	 	die( $fieldmanager_dependency->message() );
	}
}
register_activation_hook( __FILE__, 'fieldmanager_terms_dependency' );

/**
 * Get the base URL for this plugin.
 * @return string URL pointing to Fieldmanager Terms top directory.
 */
function fieldmanager_terms_get_baseurl() {
	return plugin_dir_url( __FILE__ );
}