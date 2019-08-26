<?php

namespace Pronamic\WordPress\Pay\Extensions\Membership;

use MS_View;
use Pronamic\WordPress\Pay\Plugin;
use Pronamic\WordPress\Pay\Util;

/**
 * Title: WordPress pay WPMU DEV Membership view button
 * Description:
 * Copyright: 2005-2019 Pronamic
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 2.0.1
 * @since   1.0.0
 */
class ViewButton extends MS_View {
	/**
	 * Payment method.
	 *
	 * @since 1.1.0
	 * @var string $payment_method
	 */
	protected $payment_method = null;

	public function to_html() {
		global $current_user;

		$subscription = $this->data['ms_relationship'];

		$membership = $subscription->get_membership();

		$invoice = $subscription->get_current_invoice();

		$ms_gateway = $this->data['gateway'];

		$gateway = Plugin::get_gateway( $ms_gateway->config_id );

		// Don't set payment method here as the issuer id is unknown when Plugin::start() creates
		// the payment. Therefore, any chosen banks won't get used for the payment.

		$data = new PaymentData( $subscription, $membership );

		$html = '';

		if ( ! $gateway ) {
			return $html;
		}

		ob_start();

		$gateway->set_payment_method( $this->payment_method );

		echo '<form id="pronamic-pay-form" method="post">';

		// Button image URL
		$button_image_url = plugins_url( 'images/ideal-logo-pay-off-2-lines.png', Plugin::$file );

		if ( isset( $ms_gateway->button_image_url ) && '' !== $ms_gateway->button_image_url ) {
			$button_image_url = $ms_gateway->button_image_url;
		}

		// Button description
		$button_description = __( 'iDEAL - Online payment through your own bank', 'pronamic_ideal' );

		if ( isset( $ms_gateway->button_description ) && '' !== $ms_gateway->button_description ) {
			$button_description = $ms_gateway->button_description;
		}

		printf(
			'<img src="%s" alt="%s" />',
			esc_attr( $button_image_url ),
			esc_attr( $button_description )
		);

		echo '<div style="margin-top: 1em;">';

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $gateway->get_input_html();

		// Data
		$fields = array(
			'subscription_id' => Membership::get_subscription_id( $subscription ),
			'user_id'         => $current_user->ID,
			'invoice_id'      => $invoice->id,
		);

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo Util::html_hidden_fields( $fields );

		// Submit button
		printf(
			' <input type="submit" name="pronamic_pay_membership_%s" value="%s" />',
			esc_attr( $ms_gateway->gateway ),
			esc_attr__( 'Pay', 'pronamic_ideal' )
		);

		echo '</div>';

		$error = $gateway->get_error();

		if ( is_wp_error( $error ) ) {
			foreach ( $error->get_error_messages() as $message ) {
				echo esc_html( $message ), '<br />';
			}
		}

		?>
		</form>
		<?php

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
				<?php

				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $payment_form;

				?>
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

		return $html;
	}
}
