<?php

class Pronamic_WP_Pay_Extensions_WPMUDEV_Membership_IDealDirectDebitViewButton extends Pronamic_WP_Pay_Extensions_WPMUDEV_Membership_ViewButton {
	/**
	 * Payment method.
	 *
	 * @since unreleased
	 * @var string $payment_method
	 */
	protected $payment_method = Pronamic_WP_Pay_PaymentMethods::IDEAL_DIRECTDEBIT;
}
