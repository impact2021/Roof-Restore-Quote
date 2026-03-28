<?php
/**
 * AJAX email handler for Impact Websites – Roof Estimate and Quote.
 *
 * @package Impact_Roof_Estimate
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IRREQ_Email_Handler {

	public function __construct() {
		add_action( 'wp_ajax_irreq_submit', array( $this, 'handle_submission' ) );
		add_action( 'wp_ajax_nopriv_irreq_submit', array( $this, 'handle_submission' ) );
	}

	/**
	 * Handle the AJAX form submission.
	 */
	public function handle_submission() {
		// Verify nonce.
		if ( ! check_ajax_referer( 'irreq_submit', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'impact-roof-estimate' ) ), 403 );
		}

		$settings = irreq_get_settings();

		// Verify Cloudflare Turnstile token if site key is configured.
		if ( ! empty( $settings['cf_site_key'] ) && ! empty( $settings['cf_secret_key'] ) ) {
			$token = isset( $_POST['cf_turnstile_response'] ) ? sanitize_text_field( wp_unslash( $_POST['cf_turnstile_response'] ) ) : '';

			if ( empty( $token ) ) {
				wp_send_json_error( array( 'message' => __( 'Bot protection check not completed.', 'impact-roof-estimate' ) ), 400 );
			}

			$verify = $this->verify_turnstile( $settings['cf_secret_key'], $token );
			if ( ! $verify ) {
				wp_send_json_error( array( 'message' => __( 'Bot protection verification failed. Please try again.', 'impact-roof-estimate' ) ), 400 );
			}
		}

		// Collect and sanitise form fields.
		$name      = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$phone     = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
		$email     = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$roof_size = isset( $_POST['roof_size'] ) ? floatval( $_POST['roof_size'] ) : 0;
		$material  = isset( $_POST['material'] ) ? sanitize_text_field( wp_unslash( $_POST['material'] ) ) : '';
		$condition = isset( $_POST['condition'] ) ? sanitize_text_field( wp_unslash( $_POST['condition'] ) ) : '';
		$service   = isset( $_POST['service'] ) ? sanitize_text_field( wp_unslash( $_POST['service'] ) ) : '';
		$address   = isset( $_POST['address'] ) ? sanitize_text_field( wp_unslash( $_POST['address'] ) ) : '';
		$suburb    = isset( $_POST['suburb'] ) ? sanitize_text_field( wp_unslash( $_POST['suburb'] ) ) : '';
		$details   = isset( $_POST['details'] ) ? sanitize_textarea_field( wp_unslash( $_POST['details'] ) ) : '';
		$estimate  = isset( $_POST['estimate'] ) ? sanitize_text_field( wp_unslash( $_POST['estimate'] ) ) : '';

		// Basic validation.
		if ( ! $name || ! $phone || ! is_email( $email ) || $roof_size <= 0 || ! $material || ! $condition ) {
			wp_send_json_error( array( 'message' => __( 'Please fill in all required fields.', 'impact-roof-estimate' ) ), 400 );
		}

		// Whitelist material and condition values.
		$allowed_materials  = array( 'concrete', 'metal_tile', 'longrun' );
		$allowed_conditions = array( 'good', 'average', 'poor' );
		if ( ! in_array( $material, $allowed_materials, true ) || ! in_array( $condition, $allowed_conditions, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid selection.', 'impact-roof-estimate' ) ), 400 );
		}

		// Whitelist and map service value to label.
		$service_labels = irreq_get_service_options();
		if ( ! array_key_exists( $service, $service_labels ) ) {
			wp_send_json_error( array( 'message' => __( 'Please select a service.', 'impact-roof-estimate' ) ), 400 );
		}
		$service_label = $service_labels[ $service ];

		// Validate address if the field is enabled.
		if ( ! empty( $settings['show_address'] ) && ! $address ) {
			wp_send_json_error( array( 'message' => __( 'Please enter your property address.', 'impact-roof-estimate' ) ), 400 );
		}

		// Build human-readable labels.
		$material_labels = array(
			'concrete'   => __( 'Concrete Tile', 'impact-roof-estimate' ),
			'metal_tile' => __( 'Metal Tile / Decramastic', 'impact-roof-estimate' ),
			'longrun'    => __( 'Longrun Metal', 'impact-roof-estimate' ),
		);
		$condition_labels = array(
			'good'    => __( 'Good', 'impact-roof-estimate' ),
			'average' => __( 'Average', 'impact-roof-estimate' ),
			'poor'    => __( 'Poor', 'impact-roof-estimate' ),
		);

		$material_label  = $material_labels[ $material ];
		$condition_label = $condition_labels[ $condition ];

		// Build subject from template.
		$subject = str_replace(
			array( '{name}', '{email}', '{phone}', '{estimate}' ),
			array( $name, $email, $phone, $estimate ),
			$settings['email_subject_template']
		);

		// Build email body.
		$body = $this->build_email_body( array(
			'name'           => $name,
			'phone'          => $phone,
			'email'          => $email,
			'roof_size'      => $roof_size,
			'material_label' => $material_label,
			'condition_label' => $condition_label,
			'service_label'  => $service_label,
			'address'        => $address,
			'suburb'         => $suburb,
			'details'        => $details,
			'estimate'       => $estimate,
		) );

		// Set up email headers.
		$from_name  = $settings['from_name'];
		$from_email = $settings['from_email'];
		$headers    = array(
			'Content-Type: text/plain; charset=UTF-8',
			sprintf( 'From: %s <%s>', $from_name, $from_email ),
			sprintf( 'Reply-To: %s <%s>', $name, $email ),
		);

		$sent = wp_mail( $settings['receiver_email'], $subject, $body, $headers );

		if ( ! $sent ) {
			wp_send_json_error( array( 'message' => __( 'Could not send the email. Please try again later.', 'impact-roof-estimate' ) ), 500 );
		}

		wp_send_json_success( array( 'message' => $settings['success_message'] ) );
	}

	/**
	 * Verify a Cloudflare Turnstile token server-side.
	 *
	 * @param string $secret_key Turnstile secret key.
	 * @param string $token      Token submitted by the browser.
	 * @return bool Whether the token is valid.
	 */
	private function verify_turnstile( $secret_key, $token ) {
		$response = wp_remote_post(
			'https://challenges.cloudflare.com/turnstile/v0/siteverify',
			array(
				'body' => array(
					'secret'   => $secret_key,
					'response' => $token,
				),
				'timeout' => 10,
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		return ! empty( $body['success'] );
	}

	/**
	 * Build the plain-text email body.
	 *
	 * @param array $data Submission data.
	 * @return string
	 */
	private function build_email_body( $data ) {
		$lines = array(
			__( 'A new roof painting estimate has been submitted via the website:', 'impact-roof-estimate' ),
			'',
			/* translators: %s: customer name */
			sprintf( __( 'Name:            %s', 'impact-roof-estimate' ), $data['name'] ),
			/* translators: %s: customer phone */
			sprintf( __( 'Phone:           %s', 'impact-roof-estimate' ), $data['phone'] ),
			/* translators: %s: customer email */
			sprintf( __( 'Email:           %s', 'impact-roof-estimate' ), $data['email'] ),
		);

		if ( ! empty( $data['address'] ) ) {
			/* translators: %s: property address */
			$lines[] = sprintf( __( 'Address:         %s', 'impact-roof-estimate' ), $data['address'] );
		}

		if ( ! empty( $data['suburb'] ) ) {
			/* translators: %s: suburb or area */
			$lines[] = sprintf( __( 'Suburb:          %s', 'impact-roof-estimate' ), $data['suburb'] );
		}

		$lines[] = '';
		/* translators: %s: roof size in m² */
		$lines[] = sprintf( __( 'Roof Size:       %s m²', 'impact-roof-estimate' ), $data['roof_size'] );
		/* translators: %s: roof material label */
		$lines[] = sprintf( __( 'Roof Material:   %s', 'impact-roof-estimate' ), $data['material_label'] );
		/* translators: %s: roof condition label */
		$lines[] = sprintf( __( 'Roof Condition:  %s', 'impact-roof-estimate' ), $data['condition_label'] );
		/* translators: %s: service label */
		$lines[] = sprintf( __( 'Service:         %s', 'impact-roof-estimate' ), $data['service_label'] );
		$lines[] = '';
		/* translators: %s: estimate range string */
		$lines[] = sprintf( __( 'Estimate Range:  %s', 'impact-roof-estimate' ), $data['estimate'] );
		$lines[] = '';
		$lines[] = __( '(Online estimate only — subject to on-site inspection)', 'impact-roof-estimate' );

		if ( ! empty( $data['details'] ) ) {
			$lines[] = '';
			$lines[] = __( 'Additional Details:', 'impact-roof-estimate' );
			$lines[] = $data['details'];
		}

		return implode( "\n", $lines );
	}
}
