<?php
/**
 * Shortcode handler for Impact Websites – Roof Estimate and Quote.
 *
 * Usage: [roof_estimate_quote]
 *
 * Supported attributes:
 *   bg_color                – Background hex colour, e.g. "#0f1724".
 *   bg_opacity              – Background opacity 0–1 (default 1).
 *   show_title              – 1/0  Show the form title & subtitle.
 *   show_estimate_section   – 1/0  Show the entire estimate / pricing section.
 *   show_contact_section    – 1/0  Show the entire contact form section.
 *   show_roof_size          – 1/0
 *   require_roof_size       – 1/0
 *   show_roof_material      – 1/0
 *   require_roof_material   – 1/0
 *   show_roof_condition     – 1/0
 *   require_roof_condition  – 1/0
 *   show_service            – 1/0
 *   require_service         – 1/0
 *   show_name               – 1/0
 *   require_name            – 1/0
 *   show_phone              – 1/0
 *   require_phone           – 1/0
 *   show_email              – 1/0
 *   require_email           – 1/0
 *   show_address            – 1/0  (empty = use global setting)
 *   require_address         – 1/0
 *   show_suburb             – 1/0  (empty = use global setting)
 *   require_suburb          – 1/0
 *   show_details            – 1/0  (empty = use global setting)
 *   require_details         – 1/0
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
	 * Convert a shortcode attribute value to a boolean.
	 * Treats '1', 'true', 'yes', 'on' (case-insensitive) as true.
	 *
	 * @param mixed $value Raw attribute value.
	 * @return bool
	 */
	private function atts_bool( $value ) {
		if ( is_bool( $value ) ) {
			return $value;
		}
		return in_array( strtolower( (string) $value ), array( '1', 'true', 'yes', 'on' ), true );
	}

	/**
	 * Render the shortcode HTML.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Form HTML.
	 */
	public function render( $atts ) {
		$s = irreq_get_settings();

		$atts = shortcode_atts(
			array(
				// Appearance.
				'bg_color'               => '',
				'bg_opacity'             => '1',

				// Sections.
				'show_title'             => '1',
				'show_estimate_section'  => '1',
				'show_contact_section'   => '1',

				// Estimate fields.
				'show_roof_size'         => '1',
				'require_roof_size'      => '1',
				'show_roof_material'     => '1',
				'require_roof_material'  => '1',
				'show_roof_condition'    => '1',
				'require_roof_condition' => '1',
				'show_service'           => '1',
				'require_service'        => '1',

				// Contact fields.
				'show_name'              => '1',
				'require_name'           => '1',
				'show_phone'             => '1',
				'require_phone'          => '1',
				'show_email'             => '1',
				'require_email'          => '1',

				// Optional contact fields – empty string = respect global setting.
				'show_address'           => '',
				'require_address'        => '1',
				'show_suburb'            => '',
				'require_suburb'         => '0',
				'show_details'           => '',
				'require_details'        => '0',
			),
			$atts,
			'roof_estimate_quote'
		);

		// ── Resolve section visibility ────────────────────────────────────
		$show_title    = $this->atts_bool( $atts['show_title'] );
		$show_estimate = $this->atts_bool( $atts['show_estimate_section'] );
		$show_contact  = $this->atts_bool( $atts['show_contact_section'] );

		// ── Estimate fields (hidden when section is hidden) ───────────────
		$show_roof_size         = $show_estimate && $this->atts_bool( $atts['show_roof_size'] );
		$require_roof_size      = $show_roof_size && $this->atts_bool( $atts['require_roof_size'] );
		$show_roof_material     = $show_estimate && $this->atts_bool( $atts['show_roof_material'] );
		$require_roof_material  = $show_roof_material && $this->atts_bool( $atts['require_roof_material'] );
		$show_roof_condition    = $show_estimate && $this->atts_bool( $atts['show_roof_condition'] );
		$require_roof_condition = $show_roof_condition && $this->atts_bool( $atts['require_roof_condition'] );
		$show_service           = $show_estimate && $this->atts_bool( $atts['show_service'] );
		$require_service        = $show_service && $this->atts_bool( $atts['require_service'] );

		// ── Contact fields (hidden when section is hidden) ────────────────
		$show_name    = $show_contact && $this->atts_bool( $atts['show_name'] );
		$require_name = $show_name && $this->atts_bool( $atts['require_name'] );
		$show_phone   = $show_contact && $this->atts_bool( $atts['show_phone'] );
		$require_phone = $show_phone && $this->atts_bool( $atts['require_phone'] );
		$show_email   = $show_contact && $this->atts_bool( $atts['show_email'] );
		$require_email = $show_email && $this->atts_bool( $atts['require_email'] );

		// Optional contact fields: empty string attr = fall back to global setting.
		$show_address    = '' !== $atts['show_address']
			? ( $show_contact && $this->atts_bool( $atts['show_address'] ) )
			: ( $show_contact && ! empty( $s['show_address'] ) );
		$require_address = $show_address && $this->atts_bool( $atts['require_address'] );

		$show_suburb    = '' !== $atts['show_suburb']
			? ( $show_contact && $this->atts_bool( $atts['show_suburb'] ) )
			: ( $show_contact && ! empty( $s['show_suburb'] ) );
		$require_suburb = $show_suburb && $this->atts_bool( $atts['require_suburb'] );

		$show_details    = '' !== $atts['show_details']
			? ( $show_contact && $this->atts_bool( $atts['show_details'] ) )
			: ( $show_contact && ! empty( $s['show_details'] ) );
		$require_details = $show_details && $this->atts_bool( $atts['require_details'] );

		// ── Background colour / opacity ───────────────────────────────────
		$wrap_style = '';
		if ( '' !== $atts['bg_color'] ) {
			$bg_color = sanitize_hex_color( $atts['bg_color'] );
			if ( $bg_color ) {
				$opacity = max( 0.0, min( 1.0, floatval( $atts['bg_opacity'] ) ) );
				if ( $opacity < 1.0 ) {
					$r          = hexdec( substr( $bg_color, 1, 2 ) );
					$g          = hexdec( substr( $bg_color, 3, 2 ) );
					$b          = hexdec( substr( $bg_color, 5, 2 ) );
					$wrap_style = sprintf( 'background:rgba(%d,%d,%d,%s);', $r, $g, $b, rtrim( rtrim( number_format( $opacity, 2 ), '0' ), '.' ) );
				} else {
					$wrap_style = 'background:' . $bg_color . ';';
				}
			}
		}

		// ── Build signed form config for server-side validation ───────────
		$form_config = array(
			'show_estimate_section'  => $show_estimate,
			'show_contact_section'   => $show_contact,
			'require_roof_size'      => $require_roof_size,
			'require_roof_material'  => $require_roof_material,
			'require_roof_condition' => $require_roof_condition,
			'require_service'        => $require_service,
			'require_name'           => $require_name,
			'require_phone'          => $require_phone,
			'require_email'          => $require_email,
			'require_address'        => $require_address,
			'require_suburb'         => $require_suburb,
			'require_details'        => $require_details,
		);
		$config_json = wp_json_encode( $form_config );
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		$config_b64  = base64_encode( $config_json );
		$config_hash = wp_hash( $config_json );

		ob_start();
		?>
		<div class="rr-quote-wrap" id="irreqFormWrap"
			data-irreq-config="<?php echo esc_attr( $config_b64 ); ?>"
			<?php if ( $wrap_style ) : ?>style="<?php echo esc_attr( $wrap_style ); ?>"<?php endif; ?>>

			<?php if ( $show_title ) : ?>
			<div class="rr-quote-title"><?php echo esc_html( $s['form_title'] ); ?></div>
			<div class="rr-quote-sub"><?php echo esc_html( $s['form_subtitle'] ); ?></div>
			<?php endif; ?>

			<?php if ( $show_estimate ) : ?>
			<div class="rr-quote-grid">
				<?php if ( $show_roof_size ) : ?>
				<div class="rr-quote-group">
					<label for="rrRoofSize">
						<?php echo esc_html( $s['label_roof_size'] ); ?>
						<?php if ( $require_roof_size ) : ?>
						<span class="rr-required" aria-hidden="true">*</span>
						<?php endif; ?>
					</label>
					<input id="rrRoofSize" type="number"
						placeholder="<?php echo esc_attr( $s['placeholder_roof_size'] ); ?>"
						min="1" />
				</div>
				<?php endif; ?>

				<?php if ( $show_roof_material ) : ?>
				<div class="rr-quote-group">
					<label for="rrRoofMaterial">
						<?php echo esc_html( $s['label_roof_material'] ); ?>
						<?php if ( $require_roof_material ) : ?>
						<span class="rr-required" aria-hidden="true">*</span>
						<?php endif; ?>
					</label>
					<select id="rrRoofMaterial">
						<option value=""><?php esc_html_e( 'Select material', 'impact-roof-estimate' ); ?></option>
						<option value="concrete"><?php esc_html_e( 'Concrete Tile', 'impact-roof-estimate' ); ?></option>
						<option value="metal_tile"><?php esc_html_e( 'Metal Tile / Decramastic', 'impact-roof-estimate' ); ?></option>
						<option value="longrun"><?php esc_html_e( 'Longrun Metal', 'impact-roof-estimate' ); ?></option>
					</select>
				</div>
				<?php endif; ?>

				<?php if ( $show_roof_condition ) : ?>
				<div class="rr-quote-group">
					<label for="rrRoofCondition">
						<?php echo esc_html( $s['label_roof_condition'] ); ?>
						<?php if ( $require_roof_condition ) : ?>
						<span class="rr-required" aria-hidden="true">*</span>
						<?php endif; ?>
					</label>
					<select id="rrRoofCondition">
						<option value=""><?php esc_html_e( 'Select condition', 'impact-roof-estimate' ); ?></option>
						<option value="good"><?php esc_html_e( 'Good', 'impact-roof-estimate' ); ?></option>
						<option value="average"><?php esc_html_e( 'Average', 'impact-roof-estimate' ); ?></option>
						<option value="poor"><?php esc_html_e( 'Poor', 'impact-roof-estimate' ); ?></option>
					</select>
				</div>
				<?php endif; ?>

				<?php if ( $show_service ) : ?>
				<div class="rr-quote-group">
					<label for="rrService">
						<?php echo esc_html( $s['label_service'] ); ?>
						<?php if ( $require_service ) : ?>
						<span class="rr-required" aria-hidden="true">*</span>
						<?php endif; ?>
					</label>
					<select id="rrService" name="rrService">
						<option value=""><?php esc_html_e( 'Select service', 'impact-roof-estimate' ); ?></option>
						<?php foreach ( irreq_get_service_options() as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<?php endif; ?>
			</div>

			<div class="rr-estimate-box">
				<h3><?php esc_html_e( 'Instant Estimate', 'impact-roof-estimate' ); ?></h3>
				<p id="rrEstimateText">
					<?php esc_html_e( 'Fill in all fields to see your price estimate.', 'impact-roof-estimate' ); ?>
				</p>
			</div>
			<?php endif; // end show_estimate ?>

			<?php if ( $show_contact ) : ?>
			<div class="rr-contact-grid">
				<?php if ( $show_name ) : ?>
				<div class="rr-quote-group">
					<label for="rrName">
						<?php echo esc_html( $s['label_name'] ); ?>
						<?php if ( $require_name ) : ?>
						<span class="rr-required" aria-hidden="true">*</span>
						<?php endif; ?>
					</label>
					<input type="text" id="rrName"
						placeholder="<?php echo esc_attr( $s['placeholder_name'] ); ?>" />
				</div>
				<?php endif; ?>
				<?php if ( $show_phone ) : ?>
				<div class="rr-quote-group">
					<label for="rrPhone">
						<?php echo esc_html( $s['label_phone'] ); ?>
						<?php if ( $require_phone ) : ?>
						<span class="rr-required" aria-hidden="true">*</span>
						<?php endif; ?>
					</label>
					<input type="text" id="rrPhone"
						placeholder="<?php echo esc_attr( $s['placeholder_phone'] ); ?>" />
				</div>
				<?php endif; ?>
			</div>

			<?php if ( $show_address ) : ?>
			<div class="rr-quote-group rr-full-field">
				<label for="rrAddress">
					<?php echo esc_html( $s['label_address'] ); ?>
					<?php if ( $require_address ) : ?>
					<span class="rr-required" aria-hidden="true">*</span>
					<?php endif; ?>
				</label>
				<input type="text" id="rrAddress"
					placeholder="<?php echo esc_attr( $s['placeholder_address'] ); ?>"
					autocomplete="street-address" />
			</div>
			<?php endif; ?>

			<?php if ( $show_suburb ) : ?>
			<div class="rr-quote-group rr-full-field">
				<label for="rrSuburb">
					<?php echo esc_html( $s['label_suburb'] ); ?>
					<?php if ( $require_suburb ) : ?>
					<span class="rr-required" aria-hidden="true">*</span>
					<?php endif; ?>
				</label>
				<input type="text" id="rrSuburb"
					placeholder="<?php echo esc_attr( $s['placeholder_suburb'] ); ?>"
					autocomplete="address-level2" />
			</div>
			<?php endif; ?>

			<?php if ( $show_email ) : ?>
			<div class="rr-quote-group rr-full-field">
				<label for="rrEmail">
					<?php echo esc_html( $s['label_email'] ); ?>
					<?php if ( $require_email ) : ?>
					<span class="rr-required" aria-hidden="true">*</span>
					<?php endif; ?>
				</label>
				<input type="email" id="rrEmail"
					placeholder="<?php echo esc_attr( $s['placeholder_email'] ); ?>" />
			</div>
			<?php endif; ?>

			<?php if ( $show_details ) : ?>
			<div class="rr-quote-group rr-full-field">
				<label for="rrDetails">
					<?php echo esc_html( $s['label_details'] ); ?>
					<?php if ( $require_details ) : ?>
					<span class="rr-required" aria-hidden="true">*</span>
					<?php endif; ?>
				</label>
				<textarea id="rrDetails" rows="4"
					placeholder="<?php echo esc_attr( $s['placeholder_details'] ); ?>"></textarea>
			</div>
			<?php endif; ?>
			<?php endif; // end show_contact ?>

			<?php if ( ! empty( $s['cf_site_key'] ) ) : ?>
			<div class="cf-turnstile irreq-turnstile"
				data-sitekey="<?php echo esc_attr( $s['cf_site_key'] ); ?>">
			</div>
			<?php endif; ?>

			<input type="hidden" name="irreq_form_config" value="<?php echo esc_attr( $config_b64 ); ?>" />
			<input type="hidden" name="irreq_form_config_hash" value="<?php echo esc_attr( $config_hash ); ?>" />

			<div id="irreqFormMsg" class="irreq-form-msg" style="display:none;"></div>

			<button class="rr-submit-btn" type="button" id="irreqSubmitBtn">
				<?php echo esc_html( $s['submit_button_text'] ); ?>
			</button>
		</div>
		<?php
		return ob_get_clean();
	}
}
