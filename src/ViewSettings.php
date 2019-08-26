<?php

namespace Pronamic\WordPress\Pay\Extensions\Membership;

use MS_Controller_Gateway;
use MS_Helper_Html;
use MS_View;
use Pronamic\WordPress\Pay\Plugin;

/**
 * Title: WordPress pay WPMU DEV Membership view settings
 * Description:
 * Copyright: 2005-2019 Pronamic
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 2.0.0
 * @since   1.0.0
 */
class ViewSettings extends MS_View {
	/**
	 * Payment method.
	 *
	 * @since unreleased
	 * @var string $payment_method
	 */
	protected $payment_method = null;

	/**
	 * Gateway instance.
	 *
	 * @since 1.1.0
	 * @var string $gateway
	 */
	protected $gateway;

	/**
	 * Ajax action.
	 *
	 * @since 1.1.0
	 * @var string $action
	 */
	protected $action = MS_Controller_Gateway::AJAX_ACTION_UPDATE_GATEWAY;

	protected function to_html() {
		$this->gateway = $this->data['model'];

		$fields = $this->prepare_ajax_fields();

		ob_start();

		?>

		<form class="ms-gateway-settings-form ms-form">
			<?php

			MS_Helper_Html::settings_box_header();

			foreach ( $fields as $field ) {
				MS_Helper_Html::html_element( $field );
			}

			MS_Helper_Html::settings_box_footer();

			?>
		</form>

		<?php

		$html = ob_get_clean();

		return $html;
	}

	protected function prepare_fields() {
		$fields = array(
			'config_id'                => array(
				'id'            => 'config_id',
				'type'          => MS_Helper_Html::INPUT_TYPE_SELECT,
				'title'         => __( 'Configuration', 'pronamic_ideal' ),
				'field_options' => Plugin::get_config_select_options( $this->payment_method ),
				'value'         => $this->gateway->config_id,
				'class'         => 'ms-text-large',
				'ajax_data'     => array( 1 ),
			),
			'button_image_url'         => array(
				'id'        => 'button_image_url',
				'type'      => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title'     => __( 'Button image URL', 'pronamic_ideal' ),
				'value'     => $this->gateway->button_image_url,
				'class'     => 'ms-text-large',
				'ajax_data' => array( 1 ),
			),
			'button_image_url_default' => array(
				'id'    => 'button_image_url_default',
				'type'  => MS_Helper_Html::TYPE_HTML_TEXT,
				'value' => '<span class="ms-settings-description ms-description">' . sprintf(
					/* translators: %s: <code>image url</code> */
					__( 'Default: <code>%s</code>', 'pronamic_ideal' ),
					plugins_url( 'images/ideal-logo-pay-off-2-lines.png', Plugin::$file )
				) . '</span>',
			),
			'button_description'       => array(
				'id'        => 'button_description',
				'type'      => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title'     => __( 'Button description', 'pronamic_ideal' ),
				'value'     => $this->gateway->button_description,
				'class'     => 'ms-text-large',
				'ajax_data' => array( 1 ),
			),
		);

		return $fields;
	}

	protected function prepare_ajax_fields() {
		$fields = $this->prepare_fields();

		$nonce = wp_create_nonce( $this->action );

		// Process the fields and add missing default attributes.
		foreach ( $fields as $key => $field ) {
			if ( empty( $field['ajax_data'] ) ) {
				continue;
			}

			$fields[ $key ]['ajax_data']['field']      = $fields[ $key ]['id'];
			$fields[ $key ]['ajax_data']['_wpnonce']   = $nonce;
			$fields[ $key ]['ajax_data']['action']     = $this->action;
			$fields[ $key ]['ajax_data']['gateway_id'] = $this->gateway->id;
		}

		return $fields;
	}
}
