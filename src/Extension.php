<?php

/**
 * Title: WordPress pay WPMU DEV Membership extension
 * Description:
 * Copyright: Copyright (c) 2005 - 2015
 * Company: Pronamic
 * @author Remco Tolsma
 * @version 1.0.0
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
				add_filter( 'ms_model_gateway_register', array ( __CLASS__, 'register_gateway' ) );
			}

			add_action( 'pronamic_payment_status_update_' . self::SLUG, array( __CLASS__, 'status_update' ), 10, 2 );
			add_filter( 'pronamic_payment_source_text_' . self::SLUG,   array( __CLASS__, 'source_text' ), 10, 2 );

			if ( is_admin() ) {
				$admin = new Pronamic_WP_Pay_Extensions_WPMUDEV_Membership_Admin();
			}
		}
	}

	//////////////////////////////////////////////////

	public static function is_membership2() {
		return class_exists( 'MS_Gateway' ) && ( ! function_exists( 'membership2_use_old') || ! membership2_use_old() );
	}

	//////////////////////////////////////////////////

	/**
	 * Register gateway
	 */
	public static function register_gateway( $gateways ) {
		$gateways['pronamic_ideal'] = 'Pronamic_WP_Pay_Extensions_WPMUDEV_Membership_IDealGateway';

		return $gateways;
	}

	//////////////////////////////////////////////////

	/**
	 * Update lead status of the specified payment
	 *
	 * @param Pronamic_Pay_Payment $payment
	 */
	public static function status_update( Pronamic_Pay_Payment $payment, $can_redirect = false ) {
		$status = $payment->get_status();

		if ( Pronamic_WP_Pay_Extensions_WPMUDEV_Membership_Extension::is_membership2() ) {
			$invoice_id = get_post_meta( $payment->get_id(), '_pronamic_payment_membership_invoice_id', true );

			$invoice = MS_Factory::load( 'MS_Model_Invoice', $invoice_id );

			$subscription = $invoice->get_subscription();

			$membership = $subscription->get_membership();

			$data = new Pronamic_WP_Pay_Extensions_WPMUDEV_Membership_PaymentData( $subscription, $membership);

			$gateway = new Pronamic_WP_Pay_Extensions_WPMUDEV_Membership_IDealGateway();

			$url = $data->get_normal_return_url();
		} elseif ( function_exists( 'M_get_returnurl_permalink' ) ) {
			$url = M_get_returnurl_permalink();
		}

		switch ( $status ) {
			case Pronamic_WP_Pay_Statuses::SUCCESS:
				if ( Pronamic_WP_Pay_Extensions_WPMUDEV_Membership_Extension::is_membership2() ) {
					if ( ! $invoice->is_paid() ) {
						$invoice->pay_it( $gateway->gateway, $payment->get_id() );
					}

					$url = $data->get_success_url();
				} elseif ( function_exists( 'M_get_registrationcompleted_permalink' ) ) {
					$url = M_get_registrationcompleted_permalink();
				}

				break;
		}

		if ( $url && $can_redirect ) {
			wp_redirect( $url );

			exit;
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
		$text  = '';

		$text .= __( 'Membership', 'pronamic_ideal' ) . '<br />';

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
