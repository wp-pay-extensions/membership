<?php

namespace Pronamic\WordPress\Pay\Extensions\Membership;

use Pronamic\WordPress\Pay\Core\PaymentMethods;

class IDealViewSettings extends ViewSettings {
	/**
	 * Payment method.
	 *
	 * @since 2.0.0
	 *
	 * @var string $payment_method
	 */
	protected $payment_method = PaymentMethods::IDEAL;
}
