<?php
/**
 * Shortcode handler for Impact Websites – Roof Estimate and Quote.
 *
 * Usage: [roof_estimate_quote]
 *
 * @package Impact_Roof_Estimate
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IRREQ_Shortcode {

	public function __construct() {
		add_shortcode( 'roof_estimate_quote', array( $this, 'render' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue frontend styles and scripts.
	 */
	public function enqueue_assets() {
		wp_enqueue_style(
			'irreq-public',
			IRREQ_PLUGIN_URL . 'assets/css/form-public.css',
			array(),
			IRREQ_VERSION
		);

		wp_enqueue_script(
			'irreq-public',
			IRREQ_PLUGIN_URL . 'assets/js/form-public.js',
			array( 'jquery' ),
			IRREQ_VERSION,
			true
		);

		$settings = irreq_get_settings();

		// Pass settings and AJAX data to JS.
		wp_localize_script(
			'irreq-public',
			'irreqData',
			array(
				'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
				'nonce'            => wp_create_nonce( 'irreq_submit' ),
				'cfSiteKey'        => $settings['cf_site_key'],
				'minJobTotal'      => floatval( $settings['min_job_total'] ),
				'baseRate'         => floatval( $settings['base_rate'] ),
				'materialRates'    => array(
					'concrete'   => floatval( $settings['material_concrete_multiplier'] ),
					'metal_tile' => floatval( $settings['material_metal_tile_multiplier'] ),
					'longrun'    => floatval( $settings['material_longrun_multiplier'] ),
				),
				'conditionRates'   => array(
					'good'    => floatval( $settings['condition_good_multiplier'] ),
					'average' => floatval( $settings['condition_average_multiplier'] ),
					'poor'    => floatval( $settings['condition_poor_multiplier'] ),
				),
				'successMessage'   => $settings['success_message'],
				'i18n'             => array(
					'fillFields'  => __( 'Fill in all fields to see your price estimate.', 'impact-roof-estimate' ),
					'noEstimate'  => __( 'Please complete the roof details to generate an estimate first.', 'impact-roof-estimate' ),
					'noService'   => __( 'Please select a service.', 'impact-roof-estimate' ),
					'noContact'   => __( 'Please enter your name, phone number and email so we can confirm your quote.', 'impact-roof-estimate' ),
					'noAddress'   => __( 'Please enter your property address.', 'impact-roof-estimate' ),
					'sending'     => __( 'Sending…', 'impact-roof-estimate' ),
					'error'       => __( 'Something went wrong. Please try again.', 'impact-roof-estimate' ),
				),
			)
		);

		// Enqueue Cloudflare Turnstile script if site key is configured.
		if ( ! empty( $settings['cf_site_key'] ) ) {
			wp_enqueue_script(
				'cf-turnstile',
				'https://challenges.cloudflare.com/turnstile/v0/api.js',
				array(),
				null,
				true
			);
		}
	}

	/**
	 * Render the shortcode HTML.
	 *
	 * @return string Form HTML.
	 */
	public function render() {
		$s = irreq_get_settings();
		ob_start();
		?>
		<div class="rr-quote-wrap" id="irreqFormWrap">
			<div class="rr-quote-title"><?php echo esc_html( $s['form_title'] ); ?></div>
			<div class="rr-quote-sub"><?php echo esc_html( $s['form_subtitle'] ); ?></div>

			<div class="rr-quote-grid">
				<div class="rr-quote-group">
					<label for="rrRoofSize"><?php echo esc_html( $s['label_roof_size'] ); ?></label>
					<input id="rrRoofSize" type="number"
						placeholder="<?php echo esc_attr( $s['placeholder_roof_size'] ); ?>"
						min="1" />
				</div>

				<div class="rr-quote-group">
					<label for="rrRoofMaterial"><?php echo esc_html( $s['label_roof_material'] ); ?></label>
					<select id="rrRoofMaterial">
						<option value=""><?php esc_html_e( 'Select material', 'impact-roof-estimate' ); ?></option>
						<option value="concrete"><?php esc_html_e( 'Concrete Tile', 'impact-roof-estimate' ); ?></option>
						<option value="metal_tile"><?php esc_html_e( 'Metal Tile / Decramastic', 'impact-roof-estimate' ); ?></option>
						<option value="longrun"><?php esc_html_e( 'Longrun Metal', 'impact-roof-estimate' ); ?></option>
					</select>
				</div>

				<div class="rr-quote-group">
					<label for="rrRoofCondition"><?php echo esc_html( $s['label_roof_condition'] ); ?></label>
					<select id="rrRoofCondition">
						<option value=""><?php esc_html_e( 'Select condition', 'impact-roof-estimate' ); ?></option>
						<option value="good"><?php esc_html_e( 'Good', 'impact-roof-estimate' ); ?></option>
						<option value="average"><?php esc_html_e( 'Average', 'impact-roof-estimate' ); ?></option>
						<option value="poor"><?php esc_html_e( 'Poor', 'impact-roof-estimate' ); ?></option>
					</select>
				</div>

				<div class="rr-quote-group">
					<label for="rrService"><?php echo esc_html( $s['label_service'] ); ?></label>
					<select id="rrService" name="rrService">
						<option value=""><?php esc_html_e( 'Select service', 'impact-roof-estimate' ); ?></option>
						<?php foreach ( irreq_get_service_options() as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>

			<div class="rr-estimate-box">
				<h3><?php esc_html_e( 'Instant Estimate', 'impact-roof-estimate' ); ?></h3>
				<p id="rrEstimateText">
					<?php esc_html_e( 'Fill in all fields to see your price estimate.', 'impact-roof-estimate' ); ?>
				</p>
			</div>

			<div class="rr-contact-grid">
				<div class="rr-quote-group">
					<label for="rrName"><?php echo esc_html( $s['label_name'] ); ?></label>
					<input type="text" id="rrName"
						placeholder="<?php echo esc_attr( $s['placeholder_name'] ); ?>" />
				</div>
				<div class="rr-quote-group">
					<label for="rrPhone"><?php echo esc_html( $s['label_phone'] ); ?></label>
					<input type="text" id="rrPhone"
						placeholder="<?php echo esc_attr( $s['placeholder_phone'] ); ?>" />
				</div>
			</div>

			<?php if ( ! empty( $s['show_address'] ) ) : ?>
			<div class="rr-quote-group rr-full-field">
				<label for="rrAddress">
					<?php echo esc_html( $s['label_address'] ); ?>
					<span class="rr-required" aria-hidden="true">*</span>
				</label>
				<input type="text" id="rrAddress"
					placeholder="<?php echo esc_attr( $s['placeholder_address'] ); ?>"
					autocomplete="street-address" />
			</div>
			<?php endif; ?>

			<?php if ( ! empty( $s['show_suburb'] ) ) : ?>
			<div class="rr-quote-group rr-full-field">
				<label for="rrSuburb"><?php echo esc_html( $s['label_suburb'] ); ?></label>
				<input type="text" id="rrSuburb"
					placeholder="<?php echo esc_attr( $s['placeholder_suburb'] ); ?>"
					autocomplete="address-level2" />
			</div>
			<?php endif; ?>

			<div class="rr-quote-group rr-full-field">
				<label for="rrEmail"><?php echo esc_html( $s['label_email'] ); ?></label>
				<input type="email" id="rrEmail"
					placeholder="<?php echo esc_attr( $s['placeholder_email'] ); ?>" />
			</div>

			<?php if ( ! empty( $s['show_details'] ) ) : ?>
			<div class="rr-quote-group rr-full-field">
				<label for="rrDetails"><?php echo esc_html( $s['label_details'] ); ?></label>
				<textarea id="rrDetails" rows="4"
					placeholder="<?php echo esc_attr( $s['placeholder_details'] ); ?>"></textarea>
			</div>
			<?php endif; ?>

			<?php if ( ! empty( $s['cf_site_key'] ) ) : ?>
			<div class="cf-turnstile irreq-turnstile"
				data-sitekey="<?php echo esc_attr( $s['cf_site_key'] ); ?>">
			</div>
			<?php endif; ?>

			<div id="irreqFormMsg" class="irreq-form-msg" style="display:none;"></div>

			<button class="rr-submit-btn" type="button" id="irreqSubmitBtn">
				<?php echo esc_html( $s['submit_button_text'] ); ?>
			</button>
		</div>
		<?php
		return ob_get_clean();
	}
}
