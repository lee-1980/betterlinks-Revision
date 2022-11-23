<?php

namespace BetterLinksPro;

use Elementor\Controls_Manager;

class Elementor {

	public function __construct() {
		add_action( 'betterlinks/elementor/controllers/before-end', [ $this, 'extend_instant_redirect_controls' ] );
	}

	public function extend_instant_redirect_controls( $controls ) {
		$controls->add_control(
			'bl_ir_adv_heading',
			[
				'type'      => Controls_Manager::HEADING,
				'label'     => __( 'Advanced', 'betterlinks-pro' ),
				'condition' => [
					'bl_ir_active' => 'yes'
				],
				'separator' => 'before',
			]
		);

		$controls->add_control(
			'bl_ir_adv_status',
			[
				'type'      => Controls_Manager::SELECT,
				'label'     => __( 'Status', 'betterlinks-pro' ),
				'default'   => 'publish',
				'options'   => [
					'publish' => esc_html__( 'Active', 'betterlinks-pro' ),
					'expired' => esc_html__( 'Expired', 'betterlinks-pro' ),
					'draft'   => esc_html__( 'Draft', 'betterlinks-pro' ),
				],
				'condition' => [
					'bl_ir_active' => 'yes'
				],
			]
		);

		$controls->add_control(
			'bl_ir_adv_expire',
			[
				'label'        => esc_html__( 'Expire', 'betterlinks-pro' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'On', 'betterlinks-pro' ),
				'label_off'    => esc_html__( 'Off', 'betterlinks-pro' ),
				'return_value' => 'yes',
				'default'      => '',
				'condition'    => [
					'bl_ir_active' => 'yes'
				],
			]
		);

		$controls->add_control(
			'bl_ir_adv_expire_after',
			[
				'type'      => Controls_Manager::SELECT,
				'label'     => __( 'Expire After', 'betterlinks-pro' ),
				'default'   => 'date',
				'options'   => [
					'date'   => esc_html__( 'Date', 'betterlinks-pro' ),
					'clicks' => esc_html__( 'Clicks', 'betterlinks-pro' ),
				],
				'condition' => [
					'bl_ir_active'     => 'yes',
					'bl_ir_adv_expire' => 'yes'
				],
			]
		);

		$controls->add_control(
			'bl_ir_adv_expire_after_date',
			[
				'type'           => Controls_Manager::DATE_TIME,
				'label'          => __( 'Date', 'betterlinks-pro' ),
				'label_block'    => false,
				'picker_options' => [
					'altInput'   => true,
					'altFormat'  => 'F j, Y h:i K',
					'dateFormat' => 'Y-n-j H:i:S'
				],
				'condition'      => [
					'bl_ir_active'           => 'yes',
					'bl_ir_adv_expire'       => 'yes',
					'bl_ir_adv_expire_after' => 'date'
				],
			]
		);

		$controls->add_control(
			'bl_ir_adv_expire_after_clicks',
			[
				'type'      => Controls_Manager::NUMBER,
				'min'       => 1,
				'label'     => __( 'Clicks', 'betterlinks-pro' ),
				'condition' => [
					'bl_ir_active'           => 'yes',
					'bl_ir_adv_expire'       => 'yes',
					'bl_ir_adv_expire_after' => 'clicks'
				],
			]
		);

		$controls->add_control(
			'bl_ir_adv_expire_redirect',
			[
				'label'        => esc_html__( 'Redirect URL after Expiration', 'betterlinks-pro' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'On', 'betterlinks-pro' ),
				'label_off'    => esc_html__( 'Off', 'betterlinks-pro' ),
				'return_value' => 'yes',
				'default'      => '',
				'condition'    => [
					'bl_ir_active'     => 'yes',
					'bl_ir_adv_expire' => 'yes'
				],
			]
		);

		$controls->add_control(
			'bl_ir_adv_expire_redirect_url',
			[
				'type'      => Controls_Manager::TEXT,
				'label'     => __( 'Redirect URL', 'betterlinks-pro' ),
				'condition' => [
					'bl_ir_active'              => 'yes',
					'bl_ir_adv_expire'          => 'yes',
					'bl_ir_adv_expire_redirect' => 'yes'
				],
			]
		);
	}
}
