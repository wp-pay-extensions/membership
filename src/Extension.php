<?php

namespace Pronamic\WordPress\Pay\Extensions\Membership;

use M_Membership;
use Membership_Gateway;
use MS_Factory;
use MS_Model_Pages;
use Pronamic\WordPress\Pay\Core\Statuses;
use Pronamic\WordPress\Pay\Core\Util as Core_Util;
use Pronamic\WordPress\Pay\Payments\Payment;

/**
 * Title: WordPress pay WPMU DEV Membership extension
 * Description:
 * Copyright: 2005-2019 Pronamic
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 2.0.0
 * @since   1.0.0
 */
class Extension {
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
	public static $gateways = array(
		'pronamic'       => 'Pronamic\WordPress\Pay\Extensions\Membership\Gateway',
		'pronamic_ideal' => 'Pronamic\WordPress\Pay\Extensions\Membership\IDealGateway',
	);

	/**
	 * Bootstrap
	 */
	public static function bootstrap() {
		add_action( 'plugins_loaded', array( __CLASS__, 'plugins_loaded' ) );

		/*
		 * The gateways are loaded directly when the Membership plugin file is included
		 *
		 * @link https://github.com/pronamic-wpmudev/membership-premium/blob/3.5.1.3/membershippremium.php#L234
		 * @link https://github.com/WordPress/WordPress/blob/3.8.2/wp-includes/option.php#L91
		 */
		add_filter( 'option_membership_activated_gateways', array( __CLASS__, 'option_membership_activated_gateways' ) );

		if ( self::is_membership2() ) {
			class_alias( 'MS_Gateway', 'Membership_Gateway' );
		}
	}

	/**
	 * Plugins loaded
	 */
	public static function plugins_loaded() {
		if ( ! Membership::is_active() ) {
			return;
		}

		// Backwards compatibility Membership <= 3.4.
		$class_aliases = array(
			'M_Gateway'      => 'Membership_Gateway',
			'M_Subscription' => 'Membership_Model_Subscription',
			'M_Membership'   => 'Membership_Model_Member',
		);

		if ( self::is_membership2() ) {
			$m2_class_aliases = array(
				'Pronamic\WordPress\Pay\Extensions\Membership\ViewSettings'      => 'MS_Gateway_Pronamic_View_Settings',
				'Pronamic\WordPress\Pay\Extensions\Membership\IDealViewSettings' => 'MS_Gateway_Pronamic_ideal_View_Settings',
				'Pronamic\WordPress\Pay\Extensions\Membership\ViewButton'        => 'Pronamic\WordPress\Pay\Extensions\Membership\Gateway_View_Button',
				'Pronamic\WordPress\Pay\Extensions\Membership\IDealViewButton'   => 'Pronamic\WordPress\Pay\Extensions\Membership\IDealGateway_View_Button',
			);

			$class_aliases = array_merge( $class_aliases, $m2_class_aliases );
		}

		foreach ( $class_aliases as $orignal => $alias ) {
			if ( class_exists( $orignal ) && ! class_exists( $alias ) ) {
				// @link http://www.php.net/manual/en/function.class-alias.php
				class_alias( $orignal, $alias );
			}
		}

		// Register the Membership iDEAL gateway
		// Membership < 3.5.
		if ( function_exists( 'M_register_gateway' ) ) {
			M_register_gateway( 'pronamic_ideal', 'Pronamic\WordPress\Pay\Extensions\Membership\IDealGateway' );
		}

		// Membership >= 3.5.
		if ( method_exists( 'Membership_Gateway', 'register_gateway' ) ) {
			Membership_Gateway::register_gateway( 'pronamic_ideal', 'Pronamic\WordPress\Pay\Extensions\Membership\IDealGateway' );
		}

		// Membership2.
		if ( self::is_membership2() ) {
			add_filter( 'ms_model_gateway_register', array( __CLASS__, 'register_gateway' ) );
		}

		add_filter( 'pronamic_payment_redirect_url_' . self::SLUG, array( __CLASS__, 'redirect_url' ), 10, 2 );
		add_action( 'pronamic_payment_status_update_' . self::SLUG, array( __CLASS__, 'status_update' ), 10, 1 );
		add_filter( 'pronamic_payment_source_text_' . self::SLUG, array( __CLASS__, 'source_text' ), 10, 2 );
		add_filter( 'pronamic_payment_source_description_' . self::SLUG, array( __CLASS__, 'source_description' ), 10, 2 );
		add_filter( 'pronamic_payment_source_url_' . self::SLUG, array( __CLASS__, 'source_url' ), 10, 2 );

		if ( is_admin() ) {
			$admin = new Admin();
		}
	}

	/**
	 * Is Membership 2?
	 *
	 * @return bool
	 */
	public static function is_membership2() {
		return class_exists( 'MS_Gateway' ) && ( ! function_exists( 'membership2_use_old' ) || ! membership2_use_old() );
	}

	/**
	 * Register gateway
	 *
	 * @param array $gateways Gateways.
	 *
	 * @return array
	 */
	public static function register_gateway( $gateways ) {
		$gateways = array_merge( $gateways, self::$gateways );

		return $gateways;
	}

	/**
	 * Payment redirect URL filter.
	 *
	 * @param string  $url     Redirect URL.
	 * @param Payment $payment Payment.
	 *
	 * @return string
	 */
	public static function redirect_url( $url, $payment ) {
		// @link https://github.com/wp-plugins/membership/blob/4.0.0.2/app/model/class-ms-model-pages.php#L492-L530
		if ( Core_Util::class_method_exists( 'MS_Model_Pages', 'get_page_url' ) ) {
			// @link https://github.com/wp-plugins/membership/blob/4.0.0.2/app/model/class-ms-model-pages.php#L44-L55
			$url = MS_Model_Pages::get_page_url( MS_Model_Pages::MS_PAGE_REGISTER );
		} elseif ( function_exists( 'M_get_returnurl_permalink' ) ) {
			// @link https://github.com/wp-plugins/membership/blob/3.4.4.3/membershipincludes/includes/functions.php#L598-L622
			$url = M_get_returnurl_permalink();
		}

		switch ( $payment->get_status() ) {
			case Statuses::SUCCESS:
				// @link https://github.com/wp-plugins/membership/blob/4.0.0.2/app/model/class-ms-model-pages.php#L492-L530
				if ( Core_Util::class_method_exists( 'MS_Model_Pages', 'get_page_url' ) ) {
					$invoice_id = get_post_meta( $payment->get_id(), '_pronamic_payment_membership_invoice_id', true );

					$invoice = MS_Factory::load( 'MS_Model_Invoice', $invoice_id );

					$subscription = $invoice->get_subscription();

					// @link https://github.com/wp-plugins/membership/blob/4.0.0.2/app/model/class-ms-model-pages.php#L44-L55
					$url = add_query_arg(
						'ms_relationship_id',
						$subscription->id,
						MS_Model_Pages::get_page_url( MS_Model_Pages::MS_PAGE_REG_COMPLETE )
					);
				} elseif ( function_exists( 'M_get_registrationcompleted_permalink' ) ) {
					// @link https://github.com/wp-plugins/membership/blob/3.4.4.3/membershipincludes/includes/functions.php#L576-L598
					$url = M_get_registrationcompleted_permalink();
				}

				break;
		}

		return $url;
	}

	/**
	 * Update lead status of the specified payment
	 *
	 * @param Payment $payment Payment.
	 */
	public static function status_update( Payment $payment ) {
		$invoice_id = get_post_meta( $payment->get_id(), '_pronamic_payment_membership_invoice_id', true );
		$user_id    = get_post_meta( $payment->get_id(), '_pronamic_payment_membership_user_id', true );
		$sub_id     = get_post_meta( $payment->get_id(), '_pronamic_payment_membership_subscription_id', true );
		$amount     = $payment->get_total_amount()->get_value();
		$currency   = $payment->get_total_amount()->get_currency()->get_alphabetic_code();
		$status     = $payment->get_status();
		$note       = '';

		if ( Core_Util::class_method_exists( 'MS_Factory', 'load' ) && class_exists( 'MS_Model_Invoice' ) ) {
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

			/*
			 * Membership record transaction
			 * @link https://plugins.trac.wordpress.org/browser/membership/tags/3.4.4.1/membershipincludes/classes/class.gateway.php#L176
			 */
			$gateway->pronamic_record_transaction( $user_id, $sub_id, $amount, $currency, time(), $payment->get_id(), $status, $note );
		}

		switch ( $payment->get_status() ) {
			case Statuses::OPEN:
				// @link https://plugins.trac.wordpress.org/browser/membership/tags/3.4.4.1/membershipincludes/gateways/gateway.paypalexpress.php#L871
				do_action( 'membership_payment_pending', $user_id, $sub_id, $amount, $currency, $payment->get_id() );

				break;
			case Statuses::SUCCESS:
				// @link https://github.com/wp-plugins/membership/blob/4.0.0.2/app/class-ms-factory.php#L116-L184
				// @link https://github.com/wp-plugins/membership/blob/4.0.0.2/app/model/class-ms-model-invoice.php
				if ( isset( $gateway, $invoice ) && ! $invoice->is_paid() ) {
					$invoice->pay_it( $gateway->gateway, $payment->get_id() );
				}

				if ( class_exists( 'M_Membership' ) ) {
					$member = new M_Membership( $user_id );

					if ( $member ) {
						$member->create_subscription( $sub_id, $gateway->gateway );
					}
				}

				/*
				 * Added for affiliate system link
				 * @link https://plugins.trac.wordpress.org/browser/membership/tags/3.4.4.1/membershipincludes/gateways/gateway.paypalexpress.php#L790
				 */
				do_action( 'membership_payment_processed', $user_id, $sub_id, $amount, $currency, $payment->get_id() );

				// @link https://plugins.trac.wordpress.org/browser/membership/tags/3.4.4.1/membershipincludes/gateways/gateway.paypalexpress.php#L901
				do_action( 'membership_payment_subscr_signup', $user_id, $sub_id );

				break;
		}
	}

	/**
	 * Source text.
	 *
	 * @param string  $text    Source text.
	 * @param Payment $payment Payment.
	 *
	 * @return string
	 */
	public static function source_text( $text, Payment $payment ) {
		$text = __( 'Membership', 'pronamic_ideal' ) . '<br />';

		$text .= sprintf(
			'<a href="%s">%s</a>',
			add_query_arg(
				array(
					'page'    => 'membershipgateways',
					'action'  => 'transactions',
					'gateway' => 'pronamic_ideal',
				),
				admin_url( 'admin.php' )
			),
			/* translators: %s: payment id */
			sprintf( __( 'Transaction #%s', 'pronamic_ideal' ), $payment->get_id() )
		);

		return $text;
	}

	/**
	 * Source description.
	 *
	 * @param string  $description Source description.
	 * @param Payment $payment     Payment.
	 *
	 * @return string
	 */
	public function source_description( $description, Payment $payment ) {
		return __( 'Membership Transaction', 'pronamic_ideal' );
	}

	/**
	 * Source URL.
	 *
	 * @param string  $url     Source URL.
	 * @param Payment $payment Payment.
	 *
	 * @return string
	 */
	public function source_url( $url, Payment $payment ) {
		$url = add_query_arg(
			array(
				'page'    => 'membershipgateways',
				'action'  => 'transactions',
				'gateway' => 'pronamic_ideal',
			),
			admin_url( 'admin.php' )
		);

		return $url;
	}

	/**
	 * Add the gateway to the activated gateways array if the config option is not empty
	 *
	 * @param array $gateways Activated gateways.
	 *
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
