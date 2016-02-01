<?php

class Pronamic_WP_Pay_Extensions_WPMUDEV_Membership_IDealViewSettings extends Pronamic_WP_Pay_Extensions_WPMUDEV_Membership_ViewSettings {
	protected function prepare_fields() {
		$fields = array(
			'config_id' => array(
				'id'            => 'config_id',
				'type'          => MS_Helper_Html::INPUT_TYPE_SELECT,
				'title'         => __( 'Configuration', 'pronamic_ideal' ),
				'field_options' => Pronamic_WP_Pay_Plugin::get_config_select_options(),
				'value'         => $this->gateway->config_id,
				'class'         => 'ms-text-large',
				'ajax_data'     => array( 1 ),
			),
		);

		return $fields;
	}
}
