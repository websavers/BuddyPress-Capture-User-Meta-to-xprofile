<?php
/*
Plugin Name: BuddyPress Capture User Meta
Plugin URI: https://github.com/?
Description: Captures standard update_user_meta() calls, detects when the key starts with bp_, and inserts the data to the xprofile field instead of the usermeta table
Version: 1.0
Author: Websavers Inc
Author URI: https://websavers.ca
Contributors: websavers, jas8522
Text Domain: bp_um2xp
Domain Path: languages
*/

class bp_um2xp {

	/*--------------------------------------------*
	 * Constructor
	 *--------------------------------------------*/

	/**
	 * Initializes the plugin by setting localization, filters, and administration functions.
	 */
	function __construct() {
		
		// User Meta Interceptor!
		// https://codex.wordpress.org/Plugin_API/Filter_Reference/update_(meta_type)_metadata
		add_filter( 'update_user_metadata', array( $this, 'intercept_update_user_meta'), 10, 5 );

	} // end constructor

	/** 
	** This filter intervenes on update_user_meta calls so we can save the user 
	** meta data to the BuddyPress xprofile field instead.
	** Meta field requests MUST use syntax bp_<xprofile_field_name>
	**/
	private function intercept_update_user_meta( $null, $object_id, $meta_key, $meta_value, $prev_value ) {

		if ( $this->startsWith($meta_key, 'bp_') && !empty( $meta_value ) ) {
			
			$field_id = BP_XProfile_Field::get_id_from_name( str_replace('bp_', '', $meta_key) );
			
			$bp_xprofile_field = new BP_XProfile_Field($field_id);
			$bp_xprofile_field->data = $meta_value;
			$bp_xprofile_field->save();
			
			return true; // Do not save the value into the WP meta database because we're saving it to the BuddyPress profile instead.
			
		}

		return null; // this means: go on with the normal execution in meta.php

	}
	
	private function startsWith ($string, $startString) { 
    $len = strlen($startString); 
    return (substr($string, 0, $len) === $startString); 
	} 

}

// instantiate plugin's class only if BuddyPress is active.
if ( is_plugin_active( 'buddypress' ) ){
	$GLOBALS['bp_um2xp'] = new bp_um2xp(); //create
}
