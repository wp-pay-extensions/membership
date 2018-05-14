<?php

namespace Pronamic\WordPress\Pay\Extensions\Membership;

use Pronamic\WordPress\Pay\Core\PaymentMethods;

class IDealViewButton extends ViewButton {
	/**
	 * Payment method.
	 *
	 * @since 1.1.0
	 * @var string $payment_method
	 */
	protected $payment_method = PaymentMethods::IDEAL;
}
