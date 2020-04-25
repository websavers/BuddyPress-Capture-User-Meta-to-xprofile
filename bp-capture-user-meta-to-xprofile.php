<?php
/*
Plugin Name: BuddyPress Capture User Meta
Plugin URI: https://github.com/websavers/BuddyPress-Capture-User-Meta-to-xprofile
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
		// Meta field requests MUST use syntax bp_<xprofile_field_name>
		// https://core.trac.wordpress.org/browser/tags/5.4/src/wp-includes/meta.php
		add_filter( 'update_user_metadata', array( $this, 'intercept_update_user_meta'), 10, 5 );
		add_filter( 'get_user_metadata', array( $this, 'intercept_get_user_meta'), 10, 4 );
		
		// Admin Warnings / Alerts
		add_action( 'admin_notices', array( $this, 'show_warning_if_no_buddypress' ) );

	} // end constructor

	/** 
	** This filter intervenes on update_user_meta calls so we can save the user 
	** meta data to the BuddyPress xprofile field instead.
	**/
	public function intercept_update_user_meta( $null, $object_id, $meta_key, $meta_value, $prev_value ) {
		
		$retval = null;
		
		if ( $this->startsWith($meta_key, 'bp_') && !empty( $meta_value ) ) {
			
			//error_log("Meta Key=Value: $meta_key=$meta_value", 0); ///DEBUG
						
			$field_id = (int) str_replace('bp_field_', '', $meta_key);
			
			//error_log("xprofile field ID: $field_id", 0); ///DEBUG
			
			if ( is_int($field_id) ){
				
				$bp_xprofile_field = new BP_XProfile_ProfileData($field_id, get_current_user_id());
				$bp_xprofile_field->value = $meta_value;
				$bp_xprofile_field->save();
				unset($bp_xprofile_field);
				
				//delete_user_meta( get_current_user_id(), $meta_key );
				
				$retval = true;
				// Returning true is supposed to prevent saving to the usermeta table 
				// However our data still is being added to usermeta. 
				
			}
			
		}

		return $retval; // this means: go on with the normal execution in meta.php

	}
	
	/** 
	** This filter intervenes on get_user_meta calls so we can retrieve the user 
	** meta data from the BuddyPress xprofile field instead.
	**/
	public function intercept_get_user_meta( $null, $object_id, $meta_key, $single ) {
		
		if ( $this->startsWith($meta_key, 'bp_') ) {
			
			//error_log("Meta Key=Value: $meta_key=$meta_value", 0); ///DEBUG
						
			$field_id = (int) str_replace('bp_field_', '', $meta_key);
			
			//error_log("xprofile field ID: $field_id", 0); ///DEBUG
			
			if ( is_int($field_id) ){
				
				$bp_xprofile_field = new BP_XProfile_ProfileData($field_id, get_current_user_id());
				$meta_value = maybe_unserialize($bp_xprofile_field->value);
				unset($bp_xprofile_field);
				
				if ( is_array($meta_value) ){
					$meta_value = implode(', ', $meta_value); //csv
				}
				
				return $meta_value;
												
			}
			
		}

		return null; // this means: go on with the normal return value and execution in meta.php

	}
	
	public static function show_warning_if_no_buddypress(){
		// instantiate plugin's class only if BuddyPress is active.
		if ( ! is_plugin_active( 'buddypress/bp-loader.php' ) ){
			echo __("<div class='notice notice-warning'><p>The BuddyPress capture user meta plugin requires BuddyPress. Install BuddyPress for its functionality to work.</p></div>", 'bp_um2xp');
		}
	}
	
	private function startsWith ($string, $startString) { 
    $len = strlen($startString); 
    return (substr($string, 0, $len) === $startString); 
	} 

}

// instantiate plugin's class only if BuddyPress is active.
if ( is_plugin_active( 'buddypress/bp-loader.php' ) ) $GLOBALS['bp_um2xp'] = new bp_um2xp(); //create