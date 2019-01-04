<?php

namespace Pronamic\WordPress\Pay\Extensions\Membership;

use Pronamic\WordPress\Pay\Core\PaymentMethods;

/**
 * Title: WordPress pay WPMU DEV Membership iDEAL gateway
 * Description:
 * Copyright: 2005-2019 Pronamic
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 2.0.0
 * @since   1.0.0
 */
class IDealGateway extends Gateway {
	/**
	 * Unique identifier for this gateway.
	 *
	 * @var string
	 */
	const ID = 'pronamic_ideal';

	/**
	 * Payment method
	 *
	 * @var string
	 */
	public $payment_method = PaymentMethods::IDEAL;
}
