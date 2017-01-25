<?php

/**
 * Title: WordPress pay WPMU DEV Membership payment data
 * Description:
 * Copyright: Copyright (c) 2005 - 2017
 * Company: Pronamic
 *
 * @author Remco Tolsma
 * @version 1.0.5
 * @since 1.0.0
 */
class Pronamic_WP_Pay_Extensions_WPMUDEV_Membership_PaymentData extends Pronamic_WP_Pay_PaymentData {
	/**
	 * Subscription
	 *
	 * @var Membership_Model_Subscription
	 */
	public $subscription;

	/**
	 * Membership
	 *
	 * @var Membership_Model_Member
	 */
	public $membership;

	//////////////////////////////////////////////////

	/**
	 * Constructs and initialize payment data object
	 *
	 * @param mixed $subscription
	 *      Membership         v3.4.4.1 = M_Subscription
	 *      Membership Premium v3.5.1.2 = Membership_Model_Subscription
	 *      @see https://github.com/pronamic-wpmudev/membership-premium/blob/3.5.1.2/classes/Membership/Model/Subscription.php#L21
	 * @param mixed $membership
	 *      Membership         v3.4.4.1 = M_Membership
	 *      Membership Premium v3.5.1.2 = Membership_Model_Member
	 *      @ee https://github.com/pronamic-wpmudev/membership-premium/blob/3.5.1.2/classes/Membership/Model/Member.php#L21
	 */
	public function __construct( $subscription, $membership ) {
		parent::__construct();

		if ( ! is_object( $subscription ) || ! is_object( $membership ) ) {
			trigger_error( 'Subscription or membership is not an object.', E_USER_ERROR );
		}

		switch ( get_class( $subscription ) ) {
			case 'M_Subscription' :
			case 'Membership_Model_Subscription' :
				$this->subscription = $subscription;
				$this->membership   = $membership;

				break;
			case 'MS_Model_Relationship' :
			default :
				global $current_user;

				$this->membership   = $subscription->get_membership();
				$this->subscription = $subscription->get_subscription( $current_user->ID, $this->membership->id );
		}
	}

	//////////////////////////////////////////////////
	// WPMU DEV Membership specific data
	//////////////////////////////////////////////////

	/**
	 * Get subscription ID
	 *
	 * @see https://github.com/pronamic-wpmudev/membership-premium/blob/3.5.1.2/classes/Membership/Model/Subscription.php#L57
	 * @return string
	 */
	public function get_subscription_id() {
		if ( Pronamic_WP_Pay_Extensions_WPMUDEV_Membership_Extension::is_membership2() ) {
			return $this->subscription->id;
		}

		// @see https://github.com/pronamic-wpmudev/membership-premium/blob/3.5.1.2/classes/Membership/Model/Subscription.php#L32
		return $this->subscription->sub_id();
	}

	//////////////////////////////////////////////////

	public function get_source() {
		return 'membership';
	}

	public function get_order_id() {
		// @todo temporary solution
		return $this->payment_post_id;
	}

	public function get_description() {
		if ( Pronamic_WP_Pay_Extensions_WPMUDEV_Membership_Extension::is_membership2() ) {
			return $this->subscription->name;
		}

		return $this->subscription->sub_name();
	}

	public function get_items() {
		if ( Pronamic_WP_Pay_Extensions_WPMUDEV_Membership_Extension::is_membership2() ) {
			$invoice = $this->subscription->get_current_invoice();

			$pricing_array = array(
				array(
					'amount' => $invoice->total,
				),
			);
		} else {
			$pricing_array = $this->subscription->get_pricingarray();

			// Coupon
			if ( function_exists( 'membership_get_current_coupon' ) ) {
				$coupon = membership_get_current_coupon();

				if ( ! empty( $pricing_array ) && ! empty( $coupon ) ) {
					$pricing_array = $coupon->apply_coupon_pricing( $pricing_array );
				}
			}
		}

		$items = new Pronamic_IDeal_Items();

		$item = new Pronamic_IDeal_Item();
		$item->setNumber( $this->get_order_id() );
		$item->setDescription( $this->get_description() );
		$item->setPrice( $pricing_array[0]['amount'] );
		$item->setQuantity( 1 );

		$items->addItem( $item );

		return $items;
	}

	//////////////////////////////////////////////////
	// Currency
	//////////////////////////////////////////////////

	public function get_currency_alphabetic_code() {
		$currency = Pronamic_WP_Pay_Extensions_WPMUDEV_Membership_Membership::get_option( 'paymentcurrency' );

		if ( empty( $currency ) ) {
			$currency = 'EUR';
		}

		return $currency;
	}

	//////////////////////////////////////////////////
	// Customer
	//////////////////////////////////////////////////

	public function get_email() {
		return $this->membership->user_email;
	}

	public function get_customer_name() {
		return $this->membership->first_name . ' ' . $this->membership->last_name;
	}

	public function get_address() {
		return '';
	}

	public function get_city() {
		return '';
	}

	public function get_zip() {
		return '';
	}

	//////////////////

	public function get_normal_return_url() {
		if ( Pronamic_WP_Pay_Extensions_WPMUDEV_Membership_Extension::is_membership2() ) {
			return esc_url_raw(
				add_query_arg(
					array( 'ms_relationship_id' => $this->subscription->id ),
					MS_Model_Pages::get_page_url( MS_Model_Pages::MS_PAGE_REGISTER, false )
				)
			);
		}

		return M_get_returnurl_permalink();
	}

	public function get_cancel_url() {
		if ( Pronamic_WP_Pay_Extensions_WPMUDEV_Membership_Extension::is_membership2() ) {
			return esc_url_raw(
				add_query_arg(
					array( 'ms_relationship_id' => $this->subscription->id ),
					MS_Model_Pages::get_page_url( MS_Model_Pages::MS_PAGE_REGISTER, false )
				)
			);
		}
	}

	public function get_success_url() {
		if ( Pronamic_WP_Pay_Extensions_WPMUDEV_Membership_Extension::is_membership2() ) {
			return esc_url_raw(
				add_query_arg(
					array( 'ms_relationship_id' => $this->subscription->id ),
					MS_Model_Pages::get_page_url( MS_Model_Pages::MS_PAGE_REG_COMPLETE, false )
				)
			);
		}

		return M_get_registrationcompleted_permalink();
	}

	public function get_error_url() {

	}
}
