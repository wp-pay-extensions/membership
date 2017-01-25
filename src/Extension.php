<?php

/**
 * Title: WordPress pay WPMU DEV Membership extension
 * Description:
 * Copyright: Copyright (c) 2005 - 2017
 * Company: Pronamic
 *
 * @author Remco Tolsma
 * @version 1.0.7
 * @since 1.0.0
 */
class Pronamic_WP_Pay_Extensions_WPMUDEV_Membership_Extension {
	/**
	 * The slug of this addon
	 *
	 * @var string
	 */
	const SLUG = 'membership';

	/**
	 * Indiactor for the config id options
	 *
	 * @var string
	 */
	const OPTION_CONFIG_ID = 'pronamic_pay_membership_config_id';

	/**
	 * Gateways.
	 *
	 * @var array
	 */
	static $gateways = array(
		'pronamic'       => 'Pronamic_WP_Pay_Extensions_WPMUDEV_Membership_Gateway',
		'pronamic_ideal' => 'Pronamic_WP_Pay_Extensions_WPMUDEV_Membership_IDealGateway',
	);
	//////////////////////////////////////////////////

	/**
	 * Bootstrap
	 */
	public static function bootstrap() {
		add_action( 'plugins_loaded', array( __CLASS__, 'plugins_loaded' ) );

		// The gateways are loaded directly when the Membership plugin file is included
		// @see https://github.com/pronamic-wpmudev/membership-premium/blob/3.5.1.3/membershippremium.php#L234
		// @see https://github.com/WordPress/WordPress/blob/3.8.2/wp-includes/option.php#L91
		add_filter( 'option_membership_activated_gateways', array( __CLASS__, 'option_membership_activated_gateways' ) );

		if ( Pronamic_WP_Pay_Extensions_WPMUDEV_Membership_Extension::is_membership2() ) {
			class_alias( 'MS_Gateway', 'Membership_Gateway' );
		}
	}

	//////////////////////////////////////////////////

	/**
	 * Plugins loaded
	 */
	public static function plugins_loaded() {
		if ( Pronamic_WP_Pay_Extensions_WPMUDEV_Membership_Membership::is_active() ) {
			// Backwards compatibility Membership <= 3.4
			$class_aliases = array(
				'M_Gateway'      => 'Membership_Gateway',
				'M_Subscription' => 'Membership_Model_Subscription',
				'M_Membership'   => 'Membership_Model_Member',
			);

			if ( Pronamic_WP_Pay_Extensions_WPMUDEV_Membership_Extension::is_membership2() ) {
				$m2_class_aliases = array(
					'Pronamic_WP_Pay_Extensions_WPMUDEV_Membership_ViewSettings'      => 'MS_Gateway_Pronamic_View_Settings',
					'Pronamic_WP_Pay_Extensions_WPMUDEV_Membership_IDealViewSettings' => 'MS_Gateway_Pronamic_ideal_View_Settings',
					'Pronamic_WP_Pay_Extensions_WPMUDEV_Membership_ViewButton'        => 'Pronamic_WP_Pay_Extensions_WPMUDEV_Membership_Gateway_View_Button',
					'Pronamic_WP_Pay_Extensions_WPMUDEV_Membership_IDealViewButton'   => 'Pronamic_WP_Pay_Extensions_WPMUDEV_Membership_IDealGateway_View_Button',
				);

				$class_aliases = array_merge( $class_aliases, $m2_class_aliases );
			}

			foreach ( $class_aliases as $orignal => $alias ) {
				if ( class_exists( $orignal ) && ! class_exists( $alias ) ) {
					// http://www.php.net/manual/en/function.class-alias.php
					class_alias( $orignal, $alias );
				}
			}

			// Register the Membership iDEAL gateway
			// Membership < 3.5
			if ( function_exists( 'M_register_gateway' ) ) {
				M_register_gateway( 'pronamic_ideal', 'Pronamic_WP_Pay_Extensions_WPMUDEV_Membership_IDealGateway' );
			}

			// Membership >= 3.5
			if ( method_exists( 'Membership_Gateway', 'register_gateway' ) ) {
				Membership_Gateway::register_gateway( 'pronamic_ideal', 'Pronamic_WP_Pay_Extensions_WPMUDEV_Membership_IDealGateway' );
			}

			// Membership2
			if ( Pronamic_WP_Pay_Extensions_WPMUDEV_Membership_Extension::is_membership2() ) {
				add_filter( 'ms_model_gateway_register', array( __CLASS__, 'register_gateway' ) );
			}

			add_filter( 'pronamic_payment_redirect_url_' . self::SLUG, array( __CLASS__, 'redirect_url' ), 10, 2 );
			add_action( 'pronamic_payment_status_update_' . self::SLUG, array( __CLASS__, 'status_update' ), 10, 1 );
			add_filter( 'pronamic_payment_source_text_' . self::SLUG,   array( __CLASS__, 'source_text' ), 10, 2 );
			add_filter( 'pronamic_payment_source_description_' . self::SLUG,   array( __CLASS__, 'source_description' ), 10, 2 );
			add_filter( 'pronamic_payment_source_url_' . self::SLUG,   array( __CLASS__, 'source_url' ), 10, 2 );

			if ( is_admin() ) {
				$admin = new Pronamic_WP_Pay_Extensions_WPMUDEV_Membership_Admin();
			}
		}
	}

	//////////////////////////////////////////////////

	public static function is_membership2() {
		return class_exists( 'MS_Gateway' ) && ( ! function_exists( 'membership2_use_old' ) || ! membership2_use_old() );
	}

	//////////////////////////////////////////////////

	/**
	 * Register gateway
	 */
	public static function register_gateway( $gateways ) {
		$gateways = array_merge( $gateways, self::$gateways );

		return $gateways;
	}

	/**
	 * Payment redirect URL filter.
	 *
	 * @param string                  $url
	 * @param Pronamic_WP_Pay_Payment $payment
	 * @return string
	 */
	public static function redirect_url( $url, $payment ) {
		// @see https://github.com/wp-plugins/membership/blob/4.0.0.2/app/model/class-ms-model-pages.php#L492-L530
		if ( Pronamic_WP_Pay_Class::method_exists( 'MS_Model_Pages', 'get_page_url' ) ) {

			// @see https://github.com/wp-plugins/membership/blob/4.0.0.2/app/model/class-ms-model-pages.php#L44-L55
			$url = MS_Model_Pages::get_page_url( MS_Model_Pages::MS_PAGE_REGISTER );

		} elseif ( function_exists( 'M_get_returnurl_permalink' ) ) {

			// @see https://github.com/wp-plugins/membership/blob/3.4.4.3/membershipincludes/includes/functions.php#L598-L622
			$url = M_get_returnurl_permalink();

		}

		switch ( $payment->get_status() ) {
			case Pronamic_WP_Pay_Statuses::SUCCESS :

				// @see https://github.com/wp-plugins/membership/blob/4.0.0.2/app/model/class-ms-model-pages.php#L492-L530
				if ( Pronamic_WP_Pay_Class::method_exists( 'MS_Model_Pages', 'get_page_url' ) ) {
					$invoice_id = get_post_meta( $payment->get_id(), '_pronamic_payment_membership_invoice_id', true );

					$invoice = MS_Factory::load( 'MS_Model_Invoice', $invoice_id );

					$subscription = $invoice->get_subscription();

					// @see https://github.com/wp-plugins/membership/blob/4.0.0.2/app/model/class-ms-model-pages.php#L44-L55
					$url = add_query_arg(
						'ms_relationship_id',
						$subscription->id,
						MS_Model_Pages::get_page_url( MS_Model_Pages::MS_PAGE_REG_COMPLETE )
					);

				} elseif ( function_exists( 'M_get_registrationcompleted_permalink' ) ) {

					// @see https://github.com/wp-plugins/membership/blob/3.4.4.3/membershipincludes/includes/functions.php#L576-L598
					$url = M_get_registrationcompleted_permalink();

				}

				break;
		}

		return $url;
	}

	/**
	 * Update lead status of the specified payment
	 *
	 * @param Pronamic_Pay_Payment $payment
	 */
	public static function status_update( Pronamic_Pay_Payment $payment ) {
		$invoice_id = get_post_meta( $payment->get_id(), '_pronamic_payment_membership_invoice_id', true );
		$user_id    = get_post_meta( $payment->get_id(), '_pronamic_payment_membership_user_id', true );
		$sub_id     = get_post_meta( $payment->get_id(), '_pronamic_payment_membership_subscription_id', true );
		$amount     = $payment->get_amount();
		$currency   = $payment->get_currency();
		$status     = $payment->get_status();
		$note       = '';

		if ( Pronamic_WP_Pay_Class::method_exists( 'MS_Factory', 'load' ) && class_exists( 'MS_Model_Invoice' ) ) {
			$invoice = MS_Factory::load( 'MS_Model_Invoice', $invoice_id );

			$gateway_id = $invoice->gateway_id;
		} else {
			// Versions prior to Membership 2 only supported the iDEAL gateway.
			$gateway_id = 'pronamic_ideal';
		}

		if ( isset( self::$gateways[ $gateway_id ] ) ) {
			$gateway_class = self::$gateways[ $gateway_id ];

			if ( class_exists( $gateway_class ) ) {
				$gateway = new $gateway_class();
			}

			// Membership record transaction
			// @see http://plugins.trac.wordpress.org/browser/membership/tags/3.4.4.1/membershipincludes/classes/class.gateway.php#L176
			$gateway->pronamic_record_transaction( $user_id, $sub_id, $amount, $currency, time(), $payment->get_id(), $status, $note );
		}

		switch ( $payment->get_status() ) {
			case Pronamic_WP_Pay_Statuses::OPEN:
				// @see http://plugins.trac.wordpress.org/browser/membership/tags/3.4.4.1/membershipincludes/gateways/gateway.paypalexpress.php#L871
				do_action( 'membership_payment_pending', $user_id, $sub_id, $amount, $currency, $payment->get_id() );

				break;
			case Pronamic_WP_Pay_Statuses::SUCCESS :
				// @see https://github.com/wp-plugins/membership/blob/4.0.0.2/app/class-ms-factory.php#L116-L184
				// @see https://github.com/wp-plugins/membership/blob/4.0.0.2/app/model/class-ms-model-invoice.php
				if ( isset( $gateway, $invoice ) && ! $invoice->is_paid() ) {
					$invoice->pay_it( $gateway->gateway, $payment->get_id() );
				}

				if ( class_exists( 'M_Membership' ) ) {
					$member = new M_Membership( $user_id );

					if ( $member ) {
						$member->create_subscription( $sub_id, $gateway->gateway );
					}
				}

				// Added for affiliate system link
				// @see http://plugins.trac.wordpress.org/browser/membership/tags/3.4.4.1/membershipincludes/gateways/gateway.paypalexpress.php#L790
				do_action( 'membership_payment_processed', $user_id, $sub_id, $amount, $currency, $payment->get_id() );

				// @see http://plugins.trac.wordpress.org/browser/membership/tags/3.4.4.1/membershipincludes/gateways/gateway.paypalexpress.php#L901
				do_action( 'membership_payment_subscr_signup', $user_id, $sub_id );

				break;
		}
	}

	//////////////////////////////////////////////////

	/**
	 * Source text
	 *
	 * @param text $text
	 * @param Pronamic_Pay_Payment $payment
	 * @return string
	 */
	public static function source_text( $text, Pronamic_Pay_Payment $payment ) {
		$text = __( 'Membership', 'pronamic_ideal' ) . '<br />';

		$text .= sprintf(
			'<a href="%s">%s</a>',
			add_query_arg( array(
				'page'    => 'membershipgateways',
				'action'  => 'transactions',
				'gateway' => 'pronamic_ideal',
			), admin_url( 'admin.php' ) ),
			sprintf( __( 'Transaction #%s', 'pronamic_ideal' ), $payment->get_id() )
		);

		return $text;
	}

	/**
	 * Source description.
	 */
	public function source_description( $description, Pronamic_Pay_Payment $payment ) {
		$description = __( 'Membership Transaction', 'pronamic_ideal' );

		return $description;
	}

	/**
	 * Source URL.
	 */
	public function source_url( $url, Pronamic_Pay_Payment $payment ) {
		$url = add_query_arg( array(
			'page'    => 'membershipgateways',
			'action'  => 'transactions',
			'gateway' => 'pronamic_ideal',
		), admin_url( 'admin.php' ) );

		return $url;
	}

	//////////////////////////////////////////////////

	/**
	 * Add the Pronamic iDEAL gateway to the activated gateways array if the
	 * config option is not empty
	 *
	 * @param array $gateways
	 * @return array
	 */
	public static function option_membership_activated_gateways( $gateways ) {
		if ( is_array( $gateways ) ) {
			$config_id = get_option( self::OPTION_CONFIG_ID );

			if ( ! empty( $config_id ) ) {
				$gateways[] = 'pronamic_ideal';
			}
		}

		return $gateways;
	}
}
