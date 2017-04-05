<?php

class Groups_Login_Redirect_Settings {

    // groups-login-redirect class
    private $groups_login_redirect;
    
	// options page slug
	private $options_page_name = 'groups-login-redirect';

	// options page settings group name
	private $settings_field_group = 'groups-login-redirect-settings';
    
	/**
	 * 
	 */
	function __construct() {
        
	}

	/**
	 * @return \Groups_Login_Redirect_Settings
	 */
	static public function init( $groups_login_redirect ){
		$settings_page = new self();
        
        $settings_page->groups_login_redirect = $groups_login_redirect;

		// add our options page the the admin menu
		add_action( 'admin_menu', array( $settings_page, 'admin_menu' ), 11 );
        
        // add settings link in plugins menu
		add_filter( 'plugin_action_links_groups_login_redirect', array( $settings_page, 'admin_settings_link' ) );
        
		// register our settings
        add_action( 'admin_init', array( $settings_page, 'admin_init' ) );
		
		return $settings_page;
	}


	/**
	 * Add the Settings > Groups 404 section.
	 */
	public function admin_menu() {
		add_submenu_page(
			'groups-admin',
			__( 'Groups Login Redirect', GROUPS_PLUGIN_DOMAIN ),
			__( 'Groups Login', GROUPS_PLUGIN_DOMAIN ),
			GROUPS_ADMINISTER_OPTIONS,
			'groups-login-redirect',
			array( $this, 'settings_page' )
		);
	}
    
    /*
	 * Adds plugin links.
	 *
	 * @param array $links
	 * @param array $links with additional links
	 */
	public function admin_settings_link( $links ) {
		$links[] = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=' . $this->options_page_name ) ),
			esc_html( __( 'Settings', GROUPS_LOGIN_REDIRECT_PLUGIN_DOMAIN ) )
		);
		return $links;
	}

	/**
	 * Implements hook admin_init to register our settings
	 */
	public function admin_init() {
        add_settings_section(
            'glr_page_redirects_section', 
            __( 'Redirect Groups to Pages', 'groups-login-redirect' ), 
            array( $this, 'page_redirects_section_description' ), 
            $this->options_page_name
        );
    
        $groups = $this->groups_login_redirect->get_all_groups();
        foreach( $groups as $group ) {
            $setting_id = 'glr_page_redirect_' . $group->group_id;
            add_settings_field( 
                $setting_id, 
                __( $group->name, 'groups-login-redirect' ), 
                array( $this, 'glr_page_redirect_select_field_render' ), 
                $this->options_page_name, 
                'glr_page_redirects_section',
                array( 'group' => $group, 'setting_id' => $setting_id )
            );
            
            register_setting( $this->settings_field_group, 'glr_page_redirect_' . $group->group_id );
        }
	}

    /**
     *
     */
    public function page_redirects_section_description() {
        echo
            '<p>Select a page to which a user should be redirected after logging in, based on the group to which they are a member.</p>
            <p>If a user is part of multiple groups, they will be <strong>redirected to the page with the first priority number</strong>
            (e.g. a page with a "1" priority will take precedence over a page with a priority of "3").</p>';
    }
    
    public function glr_page_redirect_select_field_render( $args ) {
        $group = $args['group'];
        $setting_id = $args['setting_id'];
        $existing_values = get_option( $setting_id );
        
        // Page setting
        $dropdown_args = array(
            'selected' =>  $existing_values['page'],
            'id' => $setting_id . '_page',
            'name' => $setting_id . '[page]',
            'show_option_none' => ' - Select a Page - ',
        );
        wp_dropdown_pages( $dropdown_args );
        
        // Priority setting
        echo '<label for="' . $setting_id . '_priority">Priority:&nbsp;</label>';
        echo '<input name="' . $setting_id . '[priority]" id="' . $setting_id . '_priority" type="text" value="' . $existing_values['priority'] . '">';
    }
    
	/**
	 * Output the options/settings page
	 */
	public function settings_page() {
		?>
		<div class="wrap">
			<h2><?php print esc_html( get_admin_page_title() ); ?></h2>

			<form method="post" action="options.php">
				<?php
				settings_fields( $this->settings_field_group );
				do_settings_sections( $this->options_page_name );
				submit_button();
				
				// simple debug to view settings array
                /*
				echo '<pre>';
                $groups = $this->groups_login_redirect->get_all_groups();
                foreach( $groups as $group ) {
                    $setting_id = 'glr_page_redirect_' . $group->group_id;
                    echo $setting_id . ': ' . var_export( get_option( $setting_id ), true );
                }
                echo '</pre>';
                */
				?>
			</form>

		</div>
		<?php
	}
}
