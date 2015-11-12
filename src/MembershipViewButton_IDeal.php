<?php

class Pronamic_WP_Pay_Extensions_WPMUDEV_Membership_IDealGateway_View_Button extends MS_View {

	public function to_html() {
		global $current_user;

		$subscription = $this->data['ms_relationship'];

		$membership = $subscription->get_membership();

		$invoice = $subscription->get_current_invoice();

		$ms_gateway = $this->data['gateway'];

		$config_id = get_option( Pronamic_WP_Pay_Extensions_WPMUDEV_Membership_Extension::OPTION_CONFIG_ID );

		$gateway = Pronamic_WP_Pay_Plugin::get_gateway( $config_id );

		// Don't set payment method to iDEAL as the issuer id is unknown when Pronamic_WP_Pay_Plugin::start() creates
		// the payment. Therefore, any chosen banks won't get used for the payment.
		// $gateway->set_payment_method( Pronamic_WP_Pay_PaymentMethods::IDEAL );

		$data = new Pronamic_WP_Pay_Extensions_WPMUDEV_Membership_PaymentData( $subscription, $membership );

		$html = '';

		if ( $gateway ) {

			ob_start();

			$payment = Pronamic_WP_Pay_Plugin::start( $config_id, $gateway, $data, Pronamic_WP_Pay_PaymentMethods::IDEAL );

			update_post_meta( $payment->get_id(), '_pronamic_payment_membership_invoice_id', $invoice->id );

			if ( $gateway->is_html_form() ) {
				echo $gateway->get_form_html( $payment, $auto_submit = false );
			}

			if ( $gateway->is_http_redirect() ) {
				printf(
					'<form id="pronamic-pay-form" method="post" action="%s">',
					$payment->get_action_url()
				);

				printf(
					'<img src="%s" alt="%s" />',
					esc_attr( plugins_url( 'images/ideal-logo-pay-off-2-lines.png', Pronamic_WP_Pay_Plugin::$file ) ),
					esc_attr__( 'iDEAL - Online payment through your own bank', 'pronamic_ideal' )
				);

				echo '<div style="margin-top: 1em;">';

				echo $gateway->get_input_html();

				// Data
				$fields = array(
					'subscription_id' => $data->get_subscription_id(),
					'user_id'         => $current_user->ID,
					'invoice_id'      => $invoice->id,
				);

				echo Pronamic_IDeal_IDeal::htmlHiddenFields( $fields );

				// Submit button
				printf(
					' <input type="submit" name="pronamic_pay_membership" value="%s" />',
					esc_attr__( 'Pay', 'pronamic_ideal' )
				);

				echo '</div>';

				if ( is_wp_error( $this->error ) ) {
					foreach ( $this->error->get_error_messages() as $message ) {
						echo $message, '<br />';
					}
				}

				?>
				</form>
			<?php
			}

			$payment_form = apply_filters(
				'ms_gateway_form',
				ob_get_clean(),
				$ms_gateway,
				$invoice,
				$this
			);

			$row_class = 'gateway_' . $ms_gateway->id;

			if ( ! $ms_gateway->is_live_mode() ) {
				$row_class .= ' sandbox-mode';
			}

			ob_start();
			?>
			<tr class="<?php echo esc_attr( $row_class ); ?>">
				<td class="ms-buy-now-column" colspan="2">
					<?php echo $payment_form; ?>
				</td>
			</tr>
			<?php
			$html = ob_get_clean();

			$html = apply_filters(
				'ms_gateway_button',
				$html,
				$ms_gateway->id,
				$this
			);
		}

		return $html;
	}
}
