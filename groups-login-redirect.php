<?php
/*
Plugin Name: Groups Login Redirect
Description: Redirect users after login based on their group
Version: 1.0
Author: Robert Staddon
Author URI: http://abundantdesigns.com
License: GPLv2 or later
Text Domain: groups-login-redirect
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

define( 'GROUPS_LOGIN_REDIRECT_PLUGIN_DOMAIN', 'groups-404-redirect' );

class Groups_Login_Redirect {
	
	private $settings_page;
	
	function __construct() {
		
	}

	public static function init() {
		$groups_login_redirect = new self();
		
        require_once 'groups-login-redirect-settings.php';
		
		// Require Groups
		add_action( 'admin_init', array( $groups_login_redirect, 'child_plugin_has_parent_plugin' ) );

		// Add Redirect
		add_filter( 'login_redirect', array( $groups_login_redirect, 'groups_login_redirect' ), 10, 3 );
		
		// Add Filters
		if ( is_admin() ) {
			$groups_login_redirect->settings_page = Groups_Login_Redirect_Settings::init(  $groups_login_redirect );
		}
				
		return $groups_login_redirect;
	}
	
	/**
	 * Require Groups
	 * http://wordpress.stackexchange.com/questions/127818/how-to-make-a-plugin-require-another-plugin
	 */
	public function child_plugin_has_parent_plugin() {
		if ( is_admin() && current_user_can( 'activate_plugins' ) ) {
			if( !is_plugin_active( 'groups/groups.php' ) ) {
				add_action( 'admin_notices', array( $this, 'child_plugin_notice' ) );
				deactivate_plugins( plugin_basename( __FILE__ ) ); 
				if ( isset( $_GET['activate'] ) ) {
					unset( $_GET['activate'] );
				}
			}
		}
	}
	public function child_plugin_notice() {
		echo '<div class="error"><p>';
		echo __('Sorry, but <strong>Groups</strong> is required for the Groups Login Redirect extension plugin to be installed and activated.', GROUPS_LOGIN_REDIRECT_PLUGIN_DOMAIN);
		echo '</p></div>';
	}

 
	/*
	 * Redirect to appropriate page based on the user's group 
	 */
	public function groups_login_redirect( $redirect_to, $request, $user ) {
        
        if ( ! is_wp_error($user) ) {
            
            // Get user's groups
            $groups_user = new Groups_User( $user->ID );
            $user_group_ids = $groups_user->group_ids_deep;
            
            // Get redirect options for user's groups
            $page_redirects = array();
            foreach( $user_group_ids as $group_id ) {
                $option = get_option( 'glr_page_redirect_' . $group_id );
                if( !empty( $option['page'] ) )
                    $page_redirects[] = $option;
            }
            
            if ( !empty( $page_redirects ) ) {
                
                // Sort array by priority
                usort( $page_redirects, function ( $a, $b ) {
                    return $a['priority'] - $b['priority'];
                });
                
                // Redirect to the first page specified in the array
                $page_redirect = current( $page_redirects );
                return get_permalink( $page_redirect['page'] );
            
            }
        }
        
        return $redirect_to;
	}
	
	
	/**
	 * Get all groups
	 */
	function get_all_groups() {
		global $wpdb;
		$groups_table = _groups_get_tablename( 'group' );
		return $wpdb->get_results( "SELECT * FROM $groups_table ORDER BY name" );
	}

}
Groups_Login_Redirect::init();

