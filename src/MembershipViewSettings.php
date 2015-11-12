<?php

class MS_Gateway_Pronamic_View_Settings extends MS_View {
	public function to_html() {
		$fields = $this->prepare_fields();

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
		$gateway = $this->data['model'];
		$action  = MS_Controller_Gateway::AJAX_ACTION_UPDATE_GATEWAY;
		$nonce   = wp_create_nonce( $action );

		$fields = array(
			'config_id' => array(
				'id'            => 'config_id',
				'type'          => MS_Helper_Html::INPUT_TYPE_SELECT,
				'title'         => __( 'Configuration', 'pronamic_ideal' ),
				'field_options' => Pronamic_WP_Pay_Plugin::get_config_select_options(),
				'value'         => $gateway->config_id,
				'class'         => 'ms-text-large',
				'ajax_data'     => array( 1 ),
			),
			'button_image_url' => array(
				'id'        => 'button_image_url',
				'type'      => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title'     => __( 'Button image URL', 'pronamic-ideal' ),
				'value'     => $gateway->button_image_url,
				'class'     => 'ms-text-large',
				'ajax_data' => array( 1 ),
			),
			'button_image_url_default' => array(
				'id' => 'button_image_url_default',
				'type' => MS_Helper_Html::TYPE_HTML_TEXT,
				'value' => '<span class="ms-settings-description ms-description">' . sprintf(
					__( 'Default: <code>%s</code>', 'pronamic-ideal' ),
					plugins_url( 'images/ideal-logo-pay-off-2-lines.png', Pronamic_WP_Pay_Plugin::$file )
				) . '</span>',
			),
			'button_description' => array(
				'id'        => 'button_description',
				'type'      => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title'     => __( 'Button description', 'pronamic-ideal' ),
				'value'     => $gateway->button_description,
				'class'     => 'ms-text-large',
				'ajax_data' => array( 1 ),
			),
		);

		// Process the fields and add missing default attributes.
		foreach ( $fields as $key => $field ) {
			if ( ! empty( $field['ajax_data'] ) ) {
				$fields[ $key ]['ajax_data']['field']      = $fields[ $key ]['id'];
				$fields[ $key ]['ajax_data']['_wpnonce']   = $nonce;
				$fields[ $key ]['ajax_data']['action']     = $action;
				$fields[ $key ]['ajax_data']['gateway_id'] = $gateway->id;
			}
		}

		return $fields;
	}
}
