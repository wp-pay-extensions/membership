<?php

namespace Pronamic\WordPress\Pay\Extensions\Membership;

use M_Subscription;
use Membership_Gateway;
use MS_Factory;
use Pronamic\WordPress\Pay\Core\PaymentMethods;
use Pronamic\WordPress\Pay\Plugin;
use Pronamic\WordPress\Pay\Util as Pay_Util;

/**
 * Title: WordPress pay WPMU DEV Membership gateway
 * Description:
 * Copyright: 2005-2019 Pronamic
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 2.0.1
 * @since   1.0.2
 */
class Gateway extends Membership_Gateway {
	/**
	 * Unique identifier for this gateway.
	 *
	 * @var string
	 */
	const ID = 'pronamic';

	/**
	 * Gateway singleton instance.
	 *
	 * @since 1.0.0
	 * @var string $instance
	 */
	public static $instance;

	/**
	 * Gateway name/slug
	 *
	 * @link https://plugins.trac.wordpress.org/browser/membership/tags/3.4.4.1/membershipincludes/classes/class.gateway.php#L10
	 * @var string
	 */
	public $gateway = 'pronamic';

	/**
	 * Gateway title
	 *
	 * @link https://plugins.trac.wordpress.org/browser/membership/tags/3.4.4.1/membershipincludes/classes/class.gateway.php#L11
	 * @var string
	 */
	public $title = 'Pronamic';

	/**
	 * Payment method
	 *
	 * @var string
	 */
	public $payment_method = null;

	/**
	 * Configuration ID
	 *
	 * @var bool $config_id
	 */
	protected $config_id;

	/**
	 * Button image URL
	 *
	 * @var bool $button_image_url
	 */
	protected $button_image_url;

	/**
	 * Button description
	 *
	 * @var bool $button_description
	 */
	protected $button_description;

	/**
	 * Constructs and initialize an Membership iDEAL gateway
	 */
	public function __construct() {
		parent::__construct();

		$this->id      = static::ID;
		$this->gateway = static::ID;

		if ( $this->payment_method ) {
			$this->name = PaymentMethods::get_name( $this->payment_method );
		} else {
			$this->name = __( 'Pronamic', 'pronamic_ideal' );
		}

		// Set title.
		$this->title = $this->name;

		// @link https://plugins.trac.wordpress.org/browser/membership/tags/3.4.4.1/membershipincludes/gateways/gateway.freesubscriptions.php#L30
		// @link https://plugins.trac.wordpress.org/browser/membership/tags/3.4.4.1/membershipincludes/classes/class.gateway.php#L97
		if ( ! Membership::is_active() ) {
			return;
		}

		add_action( 'init', array( $this, 'maybe_pay' ) );

		// @link https://plugins.trac.wordpress.org/browser/membership/tags/3.4.4.1/membershipincludes/includes/payment.form.php#L78
		add_action( 'membership_purchase_button', array( $this, 'purchase_button' ), 1, 3 );

		add_action( 'ms_gateway_changed_' . $this->id, array( $this, 'update_settings' ) );
	}

	/**
	 * Hook to add custom transaction status.
	 * This is called by the MS_Factory
	 *
	 * @since 1.0.0
	 */
	public function after_load() {
		parent::after_load();

		$this->group          = 'Pronamic';
		$this->manual_payment = true;
		$this->pro_rate       = true;
		$this->mode           = 'live';
	}

	/**
	 * Record transaction helper function
	 *
	 * @link https://plugins.trac.wordpress.org/browser/membership/tags/3.4.4.1/membershipincludes/classes/class.gateway.php#L176
	 */
	public function pronamic_record_transaction( $user_id, $sub_id, $amount, $currency, $timestamp, $paypal_id, $status, $note ) {
		/*
		 * Membership <= 3.4
		 *
		 * @link https://plugins.trac.wordpress.org/browser/membership/tags/3.4.4.1/membershipincludes/classes/class.gateway.php#L176
		 */
		if ( method_exists( $this, 'record_transaction' ) ) {
			$this->record_transaction( $user_id, $sub_id, $amount, $currency, $timestamp, $paypal_id, $status, $note );
		}

		/*
		 * Membership >= 3.5
		 *
		 * @link https://github.com/pronamic-wpmudev/membership-premium/blob/3.5.1.2/classes/Membership/Gateway.php#L256
		 */
		if ( method_exists( $this, '_record_transaction' ) ) {
			$this->_record_transaction( $user_id, $sub_id, $amount, $currency, $timestamp, $paypal_id, $status, $note );
		}
	}

	/**
	 * Maybe pay
	 */
	public function maybe_pay() {
		$pay_membership = sprintf( 'pronamic_pay_membership_%s', $this->gateway );

		if ( ! filter_has_var( INPUT_POST, $pay_membership ) ) {
			return;
		}

		// Data.
		$subscription_id = filter_input( INPUT_POST, 'subscription_id', FILTER_SANITIZE_STRING );
		$user_id         = filter_input( INPUT_POST, 'user_id', FILTER_SANITIZE_STRING );

		if ( Extension::is_membership2() ) {
			$subscription = MS_Factory::load( 'MS_Model_Relationship', $subscription_id );

			$membership = $subscription->get_membership();

			$config_id = $this->config_id;
		} else {
			$subscription = Membership::get_subscription( $subscription_id );

			$membership = Membership::get_membership( $user_id );

			$config_id = get_option( Extension::OPTION_CONFIG_ID );
		}

		if ( ! $subscription || ! $membership ) {
			return;
		}

		$gateway = Plugin::get_gateway( $config_id );

		$data = new PaymentData( $subscription, $membership );

		// Start.
		$payment = Plugin::start( $config_id, $gateway, $data, $this->payment_method );

		// Meta.
		update_post_meta( $payment->get_id(), '_pronamic_payment_membership_user_id', $user_id );
		update_post_meta( $payment->get_id(), '_pronamic_payment_membership_subscription_id', Membership::get_subscription_id( $subscription ) );

		if ( Extension::is_membership2() ) {
			$invoice = $subscription->get_current_invoice();

			$invoice->gateway_id = $this->id;

			$invoice->save();

			update_post_meta( $payment->get_id(), '_pronamic_payment_membership_invoice_id', $invoice->id );
		}

		/*
		 * Membership record transaction
		 *
		 * @link https://plugins.trac.wordpress.org/browser/membership/tags/3.4.4.1/membershipincludes/classes/class.gateway.php#L176
		 */
		$this->pronamic_record_transaction(
			$user_id, // User ID.
			Membership::get_subscription_id( $subscription ), // Sub ID.
			$data->get_amount()->get_value(), // Amount.
			$data->get_currency(), // Currency.
			time(), // Timestamp.
			$payment->get_id(), // PayPal ID.
			'', // Status.
			'' // Note.
		);

		// Error.
		$error = $gateway->get_error();

		if ( is_wp_error( $error ) ) {
			$this->error = $error;
		} else {
			// Redirect.
			$gateway->redirect( $payment );
		}
	}

	/**
	 * Purchase button
	 *
	 * @link https://plugins.trac.wordpress.org/browser/membership/tags/3.4.4.1/membershipincludes/includes/payment.form.php#L78
	 *
	 * @param M_Subscription $subscription Subscription.
	 *
	 * @link https://plugins.trac.wordpress.org/browser/membership/tags/3.4.4.1/membershipincludes/classes/class.subscription.php
	 *
	 * @param array          $pricing Pricing.
	 *
	 * @link https://plugins.trac.wordpress.org/browser/membership/tags/3.4.4.1/membershipincludes/classes/class.subscription.php#L110
	 *
	 *     array(
	 *         array(
	 *             'period' => '1',
	 *             'amount' => '50.00',
	 *             'type'   => 'indefinite',
	 *             'unit'   => 'm'
	 *         )
	 *     )
	 *
	 * @param int            $user_id WordPress user/member ID.
	 */
	public function purchase_button( $subscription, $pricing, $user_id ) {
		if ( Membership::is_pricing_free( $pricing ) ) {
			// @todo what todo?
			return;
		}

		$membership = Membership::get_membership( $user_id );

		$config_id = get_option( Extension::OPTION_CONFIG_ID );

		$data = new PaymentData( $subscription, $membership );

		$gateway = Plugin::get_gateway( $config_id );

		if ( ! $gateway ) {
			return;
		}

		$gateway->set_payment_method( $this->payment_method );

		// @link https://plugins.trac.wordpress.org/browser/membership/tags/3.4.4.1/membershipincludes/classes/membershipadmin.php#K2908
		if ( 'new' === strtolower( Membership::get_option( 'formtype' ) ) ) {
			$action = add_query_arg(
				array(
					'action'       => 'buynow',
					'subscription' => Membership::get_subscription_id( $subscription ),
				),
				admin_url( 'admin-ajax.php' )
			);
		} else {
			$action = '#pronamic-pay-form';
		}

		printf(
			'<form id="pronamic-pay-form" method="post" action="%s">',
			esc_attr( $action )
		);

		printf(
			'<img src="%s" alt="%s" />',
			esc_attr( plugins_url( 'images/ideal-logo-pay-off-2-lines.png', Plugin::$file ) ),
			esc_attr__( 'iDEAL - Online payment through your own bank', 'pronamic_ideal' )
		);

		echo '<div style="margin-top: 1em;">';

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $gateway->get_input_html();

		// Data.
		$fields = array(
			'subscription_id' => Membership::get_subscription_id( $subscription ),
			'user_id'         => $user_id,
		);

		// Coupon.
		if ( function_exists( 'membership_get_current_coupon' ) ) {
			$coupon = membership_get_current_coupon();

			if ( $coupon ) {
				$fields['coupon_code'] = $coupon->get_coupon_code();
			}
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo Pay_Util::html_hidden_fields( $fields );

		// Submit button.
		printf(
			'<input type="submit" name="pronamic_pay_membership_%s" value="%s" />',
			esc_attr( $this->gateway ),
			esc_attr__( 'Pay', 'pronamic_ideal' )
		);

		echo '</div>';

		if ( isset( $this->error ) && is_wp_error( $this->error ) ) {
			foreach ( $this->error->get_error_messages() as $message ) {
				echo esc_html( $message ), '<br />';
			}
		}

		printf( '</form>' );
	}

	/**
	 * Update
	 *
	 * @return boolean
	 */
	public function update() {
		// Default action is to return true.
		return true;
	}

	/**
	 * Update gateway configuration
	 *
	 * @param Membership_Gateway $gateway Gateway.
	 */
	public function update_settings( $gateway ) {
		$update = array(
			Extension::OPTION_CONFIG_ID => 'config_id',
		);

		foreach ( $update as $option => $field ) {
			update_option( $option, $this->$field );
		}
	}

	/**
	 * Verify required fields.
	 *
	 * @return boolean
	 */
	public function is_configured() {
		$required = array( 'config_id' );

		foreach ( $required as $field ) {
			$value = $this->$field;

			if ( empty( $value ) ) {
				return false;
			}
		}

		return true;
	}
}
