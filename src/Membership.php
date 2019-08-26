<?php

namespace Pronamic\WordPress\Pay\Extensions\Membership;

use M_Membership;
use M_Subscription;
use Membership_Plugin;

/**
 * Title: WordPress pay WPMU DEV Membership
 * Description:
 * Copyright: 2005-2019 Pronamic
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 2.0.1
 * @since   1.0.0
 */
class Membership {
	/**
	 * Check if Membership is active (Automattic/developer style)
	 *
	 * @link https://plugins.trac.wordpress.org/browser/membership/tags/3.4.4.1/membership.php
	 * @link https://plugins.trac.wordpress.org/browser/membership/tags/3.4.4.1/membershipincludes/classes/class.membership.php#L5
	 * @link https://github.com/Automattic/developer/blob/1.1.2/developer.php#L73
	 *
	 * @return boolean
	 */
	public static function is_active() {
		return class_exists( 'M_Membership' ) || class_exists( 'MS_Plugin' );
	}

	/**
	 * Check if the Membership pricing array is free
	 *
	 * @link https://plugins.trac.wordpress.org/browser/membership/tags/3.4.4.1/membershipincludes/gateways/gateway.paypalexpress.php#L578
	 *
	 * @param array $pricing
	 *
	 * @return bool
	 */
	public static function is_pricing_free( $pricing ) {
		if ( is_array( $pricing ) ) {
			foreach ( $pricing as $key => $price ) {
				if ( isset( $price['amount'] ) && $price['amount'] > 0 ) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Get an subscription by an subscription ID
	 *
	 * @param int $subscription_id
	 *
	 * @return M_Subscription|null
	 */
	public static function get_subscription( $subscription_id ) {
		$subscription = null;

		// @link https://github.com/pronamic-wpmudev/membership-premium/blob/3.5.1.3/classes/Membership/Factory.php#L76
		if ( method_exists( 'Membership_Plugin', 'factory' ) ) {
			$factory = Membership_Plugin::factory();

			// @link https://github.com/pronamic-wpmudev/membership-premium/blob/3.5.1.3/classes/Membership/Factory.php#L108
			$subscription = $factory->get_subscription( $subscription_id );
		} elseif ( class_exists( 'M_Subscription' ) ) {
			// @link https://plugins.trac.wordpress.org/browser/membership/tags/3.4.4.1/membershipincludes/classes/class.subscription.php#L26
			$subscription = new M_Subscription( $subscription_id );
		}

		return $subscription;
	}

	/**
	 * Get subscription ID
	 *
	 * @link https://github.com/pronamic-wpmudev/membership-premium/blob/3.5.1.2/classes/Membership/Model/Subscription.php#L57
	 * @since 2.0.1
	 * @param M_Subscription $subscription The Membership subscription.
	 * @return string
	 */
	public static function get_subscription_id( $subscription ) {
		if ( Extension::is_membership2() ) {
			return $subscription->id;
		}

		// @link https://github.com/pronamic-wpmudev/membership-premium/blob/3.5.1.2/classes/Membership/Model/Subscription.php#L32
		return $subscription->sub_id();
	}

	/**
	 * Get an membership by an user ID
	 *
	 * @param int $user_id
	 *
	 * @return M_Membership|null
	 */
	public static function get_membership( $user_id ) {
		$membership = null;

		// @link https://github.com/pronamic-wpmudev/membership-premium/blob/3.5.1.3/classes/Membership/Factory.php#L76
		if ( method_exists( 'Membership_Plugin', 'factory' ) ) {
			$factory = Membership_Plugin::factory();

			// @link https://github.com/pronamic-wpmudev/membership-premium/blob/3.5.1.3/classes/Membership/Factory.php#L76
			$membership = $factory->get_member( $user_id );
		} elseif ( class_exists( 'M_Membership' ) ) {
			// @link https://plugins.trac.wordpress.org/browser/membership/tags/3.4.4.1/membershipincludes/classes/class.membership.php#L18
			$membership = new M_Membership( $user_id );
		}

		return $membership;
	}

	/**
	 * Get option.
	 *
	 * @link https://plugins.trac.wordpress.org/browser/membership/tags/3.4.4.1/membershipincludes/classes/membershipadmin.php#K2908
	 *
	 * @param string $name
	 *
	 * @return string|bool
	 */
	public static function get_option( $name ) {
		// @codingStandardsIgnoreStart
		global $M_options;

		$options = $M_options;
		// @codingStandardsIgnoreEnd

		// @link https://plugins.trac.wordpress.org/browser/membership/tags/3.4.4.1/membershipincludes/classes/membershipadmin.php#K2908
		if ( isset( $options[ $name ] ) ) {
			return $options[ $name ];
		}

		return false;
	}
}
