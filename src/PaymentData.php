<?php

namespace Pronamic\WordPress\Pay\Extensions\Membership;

use Membership_Model_Member;
use Membership_Model_Subscription;
use MS_Model_Pages;
use Pronamic\WordPress\Pay\Payments\PaymentData as Pay_PaymentData;
use Pronamic\WordPress\Pay\Payments\Item;
use Pronamic\WordPress\Pay\Payments\Items;

/**
 * Title: WordPress pay WPMU DEV Membership payment data
 * Description:
 * Copyright: 2005-2019 Pronamic
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 2.0.2
 * @since   1.0.0
 */
class PaymentData extends Pay_PaymentData {
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

	/**
	 * Payment post id.
	 *
	 * @since 2.0.1
	 * @var int
	 */
	public $payment_post_id;

	/**
	 * Constructs and initialize payment data object
	 *
	 * @param mixed $subscription
	 *      Membership         v3.4.4.1 = M_Subscription
	 *      Membership Premium v3.5.1.2 = Membership_Model_Subscription
	 *
	 * @link https://github.com/pronamic-wpmudev/membership-premium/blob/3.5.1.2/classes/Membership/Model/Subscription.php#L21
	 *
	 * @param mixed $membership
	 *      Membership         v3.4.4.1 = M_Membership
	 *      Membership Premium v3.5.1.2 = Membership_Model_Member
	 * @ee https://github.com/pronamic-wpmudev/membership-premium/blob/3.5.1.2/classes/Membership/Model/Member.php#L21
	 */
	public function __construct( $subscription, $membership ) {
		parent::__construct();

		if ( ! is_object( $subscription ) || ! is_object( $membership ) ) {
			trigger_error( 'Subscription or membership is not an object.', E_USER_ERROR );
		}

		switch ( get_class( $subscription ) ) {
			case 'M_Subscription':
			case 'Membership_Model_Subscription':
				$this->subscription = $subscription;
				$this->membership   = $membership;

				break;
			case 'MS_Model_Relationship':
			default:
				global $current_user;

				$this->membership   = $subscription->get_membership();
				$this->subscription = $subscription->get_subscription( $current_user->ID, $this->membership->id );
		}
	}

	public function get_source() {
		return 'membership';
	}

	public function get_order_id() {
		// @todo temporary solution
		return $this->payment_post_id;
	}

	public function get_description() {
		if ( Extension::is_membership2() ) {
			return $this->subscription->name;
		}

		return $this->subscription->sub_name();
	}

	public function get_items() {
		if ( Extension::is_membership2() ) {
			$invoice = $this->subscription->get_current_invoice();

			$pricing_array = array( array( 'amount' => $invoice->total ) );
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

		$items = new Items();

		$item = new Item();
		$item->set_number( $this->get_order_id() );
		$item->set_description( $this->get_description() );
		$item->set_price( $pricing_array[0]['amount'] );
		$item->set_quantity( 1 );

		$items->addItem( $item );

		return $items;
	}

	public function get_currency_alphabetic_code() {
		$currency = Membership::get_option( 'paymentcurrency' );

		if ( empty( $currency ) ) {
			$currency = 'EUR';
		}

		return $currency;
	}

	public function get_email() {
		return $this->membership->user_email;
	}

	public function get_first_name() {
		return $this->membership->first_name;
	}

	public function get_last_name() {
		return $this->membership->last_name;
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

	public function get_normal_return_url() {
		if ( Extension::is_membership2() ) {
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
		if ( Extension::is_membership2() ) {
			return esc_url_raw(
				add_query_arg(
					array( 'ms_relationship_id' => $this->subscription->id ),
					MS_Model_Pages::get_page_url( MS_Model_Pages::MS_PAGE_REGISTER, false )
				)
			);
		}
	}

	public function get_success_url() {
		if ( Extension::is_membership2() ) {
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
