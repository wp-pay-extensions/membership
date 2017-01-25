<?php

/**
 * Title: WordPress pay WPMU DEV Membership iDEAL gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2017
 * Company: Pronamic
 *
 * @author Remco Tolsma
 * @version 1.0.5
 * @since 1.0.0
 */
class Pronamic_WP_Pay_Extensions_WPMUDEV_Membership_IDealGateway extends Pronamic_WP_Pay_Extensions_WPMUDEV_Membership_Gateway {
	/**
	 * Unique identifier for this gateway.
	 *
	 * @var string
	 */
	const ID = 'pronamic_ideal';

	/**
	 * Gateway name/slug
	 *
	 * @see http://plugins.trac.wordpress.org/browser/membership/tags/3.4.4.1/membershipincludes/classes/class.gateway.php#L10
	 * @var string
	 */
	public $gateway = 'pronamic_ideal';

	/**
	 * Gateway title
	 *
	 * @see http://plugins.trac.wordpress.org/browser/membership/tags/3.4.4.1/membershipincludes/classes/class.gateway.php#L11
	 * @var string
	 */
	public $title = 'iDEAL';

	/**
	 * Payment method
	 *
	 * @var string
	 */
	public $payment_method = Pronamic_WP_Pay_PaymentMethods::IDEAL;

	//////////////////////////////////////////////////

	/**
	 * Constructs and initliaze an Membership iDEAL gateway
	 */
	public function __construct() {
		$this->name = __( 'iDEAL', 'pronamic_ideal' );

		parent::__construct();
	}
}
