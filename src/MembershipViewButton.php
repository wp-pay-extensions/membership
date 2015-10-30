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

		$data = new Pronamic_WP_Pay_Extensions_WPMUDEV_Membership_PaymentData( $subscription, $membership );

		$html = '';

		if ( $gateway ) {

			ob_start();

			$payment = Pronamic_WP_Pay_Plugin::start( $config_id, $gateway, $data );

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
				$fields = array (
					'subscription_id' => $data->get_subscription_id(),
					'user_id' => $current_user->ID,
					'invoice_id' => $invoice->id,
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

	/**
	 * Prepare the PayPal IPN fields
	 *
	 * Details here:
	 * https://developer.paypal.com/docs/classic/paypal-payments-standard/integration-guide/Appx_websitestandard_htmlvariables/
	 *
	 * @since  1.0.0
	 * @return array
	 */
	private function prepare_fields() {
		$subscription = $this->data['ms_relationship'];
		$membership = $subscription->get_membership();

		if ( 0 === $membership->price ) {
			return;
		}

		$gateway = $this->data['gateway'];
		$invoice = $subscription->get_current_invoice();

		$fields = array(
			'business' => array(
				'id' => 'business',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $gateway->paypal_email,
			),
			'cmd' => array(
				'id' => 'cmd',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => '_xclick',
			),
			'bn' => array(
				'id' => 'bn',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => 'incsub_SP',
			),
			'item_number' => array(
				'id' => 'item_number',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $subscription->membership_id,
			),
			'item_name' => array(
				'id' => 'item_name',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $membership->name,
			),
			'amount' => array(
				'id' => 'amount',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => MS_Helper_Billing::format_price( $invoice->total ),
			),
			'currency_code' => array(
				'id' => 'currency_code',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $invoice->currency,
			),
			'return' => array(
				'id' => 'return',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => esc_url_raw(
					add_query_arg(
						array( 'ms_relationship_id' => $subscription->id ),
						MS_Model_Pages::get_page_url( MS_Model_Pages::MS_PAGE_REG_COMPLETE, false )
					)
				),
			),
			'cancel_return' => array(
				'id' => 'cancel_return',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => MS_Model_Pages::get_page_url( MS_Model_Pages::MS_PAGE_REGISTER ),
			),
			'notify_url' => array(
				'id' => 'notify_url',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $gateway->get_return_url(),
			),
			'lc' => array(
				'id' => 'lc',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $gateway->paypal_site,
			),
			'invoice' => array(
				'id' => 'invoice',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $invoice->id,
			),
		);

		// Don't send to paypal if free
		if ( 0 === $invoice->total ) {
			$fields = array(
				'gateway' => array(
					'id' => 'gateway',
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => $gateway->id,
				),
				'ms_relationship_id' => array(
					'id' => 'ms_relationship_id',
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => $subscription->id,
				),
				'step' => array(
					'id' => 'step',
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => MS_Controller_Frontend::STEP_PROCESS_PURCHASE,
				),
				'_wpnonce' => array(
					'id' => '_wpnonce',
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => wp_create_nonce(
						$gateway->id . '_' .$subscription->id
					),
				),
			);
			$this->data['action_url'] = null;
		} else {
			if ( $gateway->is_live_mode() ) {
				$this->data['action_url'] = 'https://www.paypal.com/cgi-bin/webscr';
			} else {
				$this->data['action_url'] = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
			}
		}

		$fields['submit'] = array(
			'id' => 'submit',
			'type' => MS_Helper_Html::INPUT_TYPE_IMAGE,
			'value' => 'https://www.paypalobjects.com/en_US/i/btn/x-click-but06.gif',
			'alt' => __( 'PayPal - The safer, easier way to pay online', MS_TEXT_DOMAIN ),
		);

		// custom pay button defined in gateway settings
		$custom_label = $gateway->pay_button_url;
		if ( ! empty( $custom_label ) ) {
			if ( false !== strpos( $custom_label, '://' ) ) {
				$fields['submit']['value'] = $custom_label;
			} else {
				$fields['submit'] = array(
					'id' => 'submit',
					'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
					'value' => $custom_label,
				);
			}
		}

		return apply_filters(
			'ms_gateway_paypalsingle_view_prepare_fields',
			$fields
		);
	}
}