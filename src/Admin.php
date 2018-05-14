<?php

namespace Pronamic\WordPress\Pay\Extensions\Membership;

/**
 * Title: WordPress pay WPMU DEV Membership admin
 * Description:
 * Copyright: Copyright (c) 2005 - 2018
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 2.0.0
 * @since   1.0.0
 */
class Admin {
	/**
	 * Bootstrap
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'admin_init' ) );

		add_action( 'membership_add_menu_items_after_gateways', array( $this, 'add_menu_items' ) );
	}

	/**
	 * Admin initialize
	 */
	public function admin_init() {
		$this->settings_init();
	}

	/**
	 * Settings initialize
	 */
	public function settings_init() {
		// Settings - General
		add_settings_section(
			'pronamic_pay_membership_general', // id
			__( 'General', 'pronamic_ideal' ), // title
			'__return_false', // callback
			'pronamic_pay_membership' // page
		);

		add_settings_field(
			Extension::OPTION_CONFIG_ID, // id
			__( 'Configuration', 'pronamic_ideal' ), // title
			array( 'Pronamic\WordPress\Pay\Admin\AdminModule', 'dropdown_configs' ), // callback
			'pronamic_pay_membership', // page
			'pronamic_pay_membership_general', // section
			array( // args
				'name'      => Extension::OPTION_CONFIG_ID,
				'label_for' => Extension::OPTION_CONFIG_ID,
			)
		);

		register_setting( 'pronamic_pay_membership', 'pronamic_pay_membership_config_id' );
	}

	/**
	 * Add menu items
	 */
	public function add_menu_items() {
		add_submenu_page(
			'membership',
			__( 'Pronamic iDEAL Options', 'pronamic_ideal' ),
			__( 'iDEAL Options', 'pronamic_ideal' ),
			'manage_options',
			'pronamic_pay_membership_settings',
			array( $this, 'page_settings' )
		);
	}

	/**
	 * Page settings
	 *
	 * @return void
	 */
	public function page_settings() {
		include dirname( __FILE__ ) . '/../views/html-admin-page-settings.php';
	}
}
