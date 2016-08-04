<?php

/**
 * Title: WordPress pay WPMU DEV Membership iDEAL + Direct Debit gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2016
 * Company: Pronamic
 *
 * @author Reüel van der Steege
 * @version 1.0.0
 * @since unreleased
 */
class Pronamic_WP_Pay_Extensions_WPMUDEV_Membership_IDealDirectDebitGateway extends Pronamic_WP_Pay_Extensions_WPMUDEV_Membership_Gateway {
	/**
	 * Unique identifier for this gateway.
	 *
	 * @var string
	 */
	const ID = 'pronamic_ideal_directdebit';

	/**
	 * Gateway name/slug
	 *
	 * @see http://plugins.trac.wordpress.org/browser/membership/tags/3.4.4.1/membershipincludes/classes/class.gateway.php#L10
	 * @var string
	 */
	public $gateway = 'pronamic_ideal_directdebit';

	/**
	 * Gateway title
	 *
	 * @see http://plugins.trac.wordpress.org/browser/membership/tags/3.4.4.1/membershipincludes/classes/class.gateway.php#L11
	 * @var string
	 */
	public $title = 'iDEAL + Direct Debit';

	/**
	 * Payment method
	 *
	 * @var string
	 */
	public $payment_method = Pronamic_WP_Pay_PaymentMethods::IDEAL_DIRECTDEBIT;

	//////////////////////////////////////////////////

	/**
	 * Constructs and initliaze an Membership iDEAL debit gateway
	 */
	public function __construct() {
		$this->name = __( 'iDEAL + Direct Debit', 'pronamic_ideal' );

		parent::__construct();
	}
}
