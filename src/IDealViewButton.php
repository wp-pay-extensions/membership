<?php

class Pronamic_WP_Pay_Extensions_WPMUDEV_Membership_IDealViewButton extends Pronamic_WP_Pay_Extensions_WPMUDEV_Membership_ViewButton {
	/**
	 * Payment method.
	 *
	 * @since 1.1.0
	 * @var string $payment_method
	 */
	protected $payment_method = Pronamic_WP_Pay_PaymentMethods::IDEAL;
}
