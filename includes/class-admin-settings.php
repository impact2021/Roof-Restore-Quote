<?php
/**
 * Admin Settings page for Impact Websites – Roof Estimate and Quote.
 *
 * @package Impact_Roof_Estimate
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IRREQ_Admin_Settings {

	/** @var string The settings page slug. */
	const PAGE_SLUG = 'irreq-settings';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Register the top-level admin menu item "Roof Estimate form".
	 */
	public function add_menu_page() {
		add_menu_page(
			__( 'Roof Estimate & Quote Settings', 'impact-roof-estimate' ),
			__( 'Roof Estimate form', 'impact-roof-estimate' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_settings_page' ),
			'dashicons-admin-home',
			30
		);
	}

	/**
	 * Enqueue admin stylesheet on our settings page only.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( 'toplevel_page_' . self::PAGE_SLUG !== $hook ) {
			return;
		}
		wp_enqueue_style(
			'irreq-admin',
			IRREQ_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			IRREQ_VERSION
		);
		wp_enqueue_script(
			'irreq-admin-builder',
			IRREQ_PLUGIN_URL . 'assets/js/admin-shortcode-builder.js',
			array( 'jquery' ),
			IRREQ_VERSION,
			true
		);
	}

	/**
	 * Register settings, sections and fields using the Settings API.
	 */
	public function register_settings() {
		register_setting(
			'irreq_settings_group',
			IRREQ_OPTION_KEY,
			array(
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
			)
		);

		// ── Section: General ──────────────────────────────────────────────
		add_settings_section(
			'irreq_section_general',
			__( 'General', 'impact-roof-estimate' ),
			null,
			self::PAGE_SLUG
		);
		$this->add_text_field( 'form_title', __( 'Form Title', 'impact-roof-estimate' ), 'irreq_section_general' );
		$this->add_text_field( 'form_subtitle', __( 'Form Subtitle', 'impact-roof-estimate' ), 'irreq_section_general' );
		$this->add_text_field( 'submit_button_text', __( 'Submit Button Text', 'impact-roof-estimate' ), 'irreq_section_general' );
		$this->add_textarea_field( 'success_message', __( 'Success Message', 'impact-roof-estimate' ), 'irreq_section_general' );

		// ── Section: Email / Notifications ────────────────────────────────
		add_settings_section(
			'irreq_section_email',
			__( 'Email / Notifications', 'impact-roof-estimate' ),
			array( $this, 'render_section_email_intro' ),
			self::PAGE_SLUG
		);
		$this->add_text_field( 'receiver_email', __( 'Form Receiver Email', 'impact-roof-estimate' ), 'irreq_section_email' );
		$this->add_text_field( 'from_name', __( '"From" Name', 'impact-roof-estimate' ), 'irreq_section_email' );
		$this->add_text_field( 'from_email', __( '"From" Email', 'impact-roof-estimate' ), 'irreq_section_email' );
		$this->add_text_field( 'email_subject_template', __( 'Email Subject', 'impact-roof-estimate' ), 'irreq_section_email' );

		// ── Section: Estimate / Pricing ───────────────────────────────────
		add_settings_section(
			'irreq_section_pricing',
			__( 'Estimate / Pricing', 'impact-roof-estimate' ),
			array( $this, 'render_section_pricing_intro' ),
			self::PAGE_SLUG
		);
		$this->add_number_field( 'min_job_total', __( 'Minimum Job Total (NZD)', 'impact-roof-estimate' ), 'irreq_section_pricing', 0 );
		$this->add_number_field( 'base_rate', __( 'Base Rate per m² (NZD)', 'impact-roof-estimate' ), 'irreq_section_pricing', 0 );
		$this->add_number_field( 'material_concrete_multiplier', __( 'Material Multiplier – Concrete Tile', 'impact-roof-estimate' ), 'irreq_section_pricing', 0, 0.01 );
		$this->add_number_field( 'material_metal_tile_multiplier', __( 'Material Multiplier – Metal Tile / Decramastic', 'impact-roof-estimate' ), 'irreq_section_pricing', 0, 0.01 );
		$this->add_number_field( 'material_longrun_multiplier', __( 'Material Multiplier – Longrun Metal', 'impact-roof-estimate' ), 'irreq_section_pricing', 0, 0.01 );
		$this->add_number_field( 'condition_good_multiplier', __( 'Condition Multiplier – Good', 'impact-roof-estimate' ), 'irreq_section_pricing', 0, 0.01 );
		$this->add_number_field( 'condition_average_multiplier', __( 'Condition Multiplier – Average', 'impact-roof-estimate' ), 'irreq_section_pricing', 0, 0.01 );
		$this->add_number_field( 'condition_poor_multiplier', __( 'Condition Multiplier – Poor', 'impact-roof-estimate' ), 'irreq_section_pricing', 0, 0.01 );

		// ── Section: Form Fields ──────────────────────────────────────────
		add_settings_section(
			'irreq_section_fields',
			__( 'Form Fields', 'impact-roof-estimate' ),
			null,
			self::PAGE_SLUG
		);
		$this->add_text_field( 'label_roof_size', __( 'Roof Size – Label', 'impact-roof-estimate' ), 'irreq_section_fields' );
		$this->add_text_field( 'placeholder_roof_size', __( 'Roof Size – Placeholder', 'impact-roof-estimate' ), 'irreq_section_fields' );
		$this->add_text_field( 'label_roof_material', __( 'Roof Material – Label', 'impact-roof-estimate' ), 'irreq_section_fields' );
		$this->add_text_field( 'label_roof_condition', __( 'Roof Condition – Label', 'impact-roof-estimate' ), 'irreq_section_fields' );
		$this->add_text_field( 'label_service', __( 'Service – Label', 'impact-roof-estimate' ), 'irreq_section_fields' );
		$this->add_text_field( 'service_description', __( 'Service – Description (shown in read-only field)', 'impact-roof-estimate' ), 'irreq_section_fields' );
		$this->add_text_field( 'label_name', __( 'Name – Label', 'impact-roof-estimate' ), 'irreq_section_fields' );
		$this->add_text_field( 'placeholder_name', __( 'Name – Placeholder', 'impact-roof-estimate' ), 'irreq_section_fields' );
		$this->add_text_field( 'label_phone', __( 'Phone – Label', 'impact-roof-estimate' ), 'irreq_section_fields' );
		$this->add_text_field( 'placeholder_phone', __( 'Phone – Placeholder', 'impact-roof-estimate' ), 'irreq_section_fields' );
		$this->add_text_field( 'label_email', __( 'Email – Label', 'impact-roof-estimate' ), 'irreq_section_fields' );
		$this->add_text_field( 'placeholder_email', __( 'Email – Placeholder', 'impact-roof-estimate' ), 'irreq_section_fields' );
		$this->add_checkbox_field( 'show_address', __( 'Property Address – Show field', 'impact-roof-estimate' ), 'irreq_section_fields', __( 'Display the Property Address field (required when shown)', 'impact-roof-estimate' ) );
		$this->add_text_field( 'label_address', __( 'Property Address – Label', 'impact-roof-estimate' ), 'irreq_section_fields' );
		$this->add_text_field( 'placeholder_address', __( 'Property Address – Placeholder', 'impact-roof-estimate' ), 'irreq_section_fields' );
		$this->add_checkbox_field( 'show_suburb', __( 'Suburb / Area – Show field', 'impact-roof-estimate' ), 'irreq_section_fields', __( 'Display the Suburb / Area field', 'impact-roof-estimate' ) );
		$this->add_text_field( 'label_suburb', __( 'Suburb / Area – Label', 'impact-roof-estimate' ), 'irreq_section_fields' );
		$this->add_text_field( 'placeholder_suburb', __( 'Suburb / Area – Placeholder', 'impact-roof-estimate' ), 'irreq_section_fields' );
		$this->add_checkbox_field( 'show_details', __( 'Details – Show field', 'impact-roof-estimate' ), 'irreq_section_fields', __( 'Display the Details message field', 'impact-roof-estimate' ) );
		$this->add_text_field( 'label_details', __( 'Details – Label', 'impact-roof-estimate' ), 'irreq_section_fields' );
		$this->add_text_field( 'placeholder_details', __( 'Details – Placeholder', 'impact-roof-estimate' ), 'irreq_section_fields' );

		// ── Section: Cloudflare Turnstile ─────────────────────────────────
		add_settings_section(
			'irreq_section_cloudflare',
			__( 'Cloudflare Turnstile (CAPTCHA)', 'impact-roof-estimate' ),
			array( $this, 'render_section_cloudflare_intro' ),
			self::PAGE_SLUG
		);
		$this->add_text_field( 'cf_site_key', __( 'Turnstile Site Key', 'impact-roof-estimate' ), 'irreq_section_cloudflare' );
		$this->add_password_field( 'cf_secret_key', __( 'Turnstile Secret Key', 'impact-roof-estimate' ), 'irreq_section_cloudflare' );
	}

	// ── Section description callbacks ─────────────────────────────────────

	public function render_section_email_intro() {
		echo '<p class="irreq-section-desc">'
			. esc_html__( 'Configure where form submissions are sent. The {name}, {email}, {phone} and {estimate} tokens are available in the subject template.', 'impact-roof-estimate' )
			. '</p>';
	}

	public function render_section_pricing_intro() {
		echo '<p class="irreq-section-desc">'
			. esc_html__( 'Adjust the pricing formula. Estimate = (Roof m²) × (Base Rate) × (Material Multiplier) × (Condition Multiplier), with a floor at the Minimum Job Total.', 'impact-roof-estimate' )
			. '</p>';
	}

	public function render_section_cloudflare_intro() {
		echo '<p class="irreq-section-desc">'
			. sprintf(
				/* translators: %s: Cloudflare Turnstile dashboard URL */
				esc_html__( 'Obtain your keys from the %s. Leave blank to disable bot protection.', 'impact-roof-estimate' ),
				'<a href="https://dash.cloudflare.com/?to=/:account/turnstile" target="_blank" rel="noopener noreferrer">'
				. esc_html__( 'Cloudflare Turnstile dashboard', 'impact-roof-estimate' )
				. '</a>'
			)
			. '</p>';
	}

	// ── Field rendering helpers ───────────────────────────────────────────

	/**
	 * Register and add a simple text field.
	 *
	 * @param string $key     Option key.
	 * @param string $label   Field label.
	 * @param string $section Settings section id.
	 */
	private function add_text_field( $key, $label, $section ) {
		add_settings_field(
			'irreq_field_' . $key,
			$label,
			array( $this, 'render_text_field' ),
			self::PAGE_SLUG,
			$section,
			array( 'key' => $key )
		);
	}

	/**
	 * Register and add a password field.
	 *
	 * @param string $key     Option key.
	 * @param string $label   Field label.
	 * @param string $section Settings section id.
	 */
	private function add_password_field( $key, $label, $section ) {
		add_settings_field(
			'irreq_field_' . $key,
			$label,
			array( $this, 'render_password_field' ),
			self::PAGE_SLUG,
			$section,
			array( 'key' => $key )
		);
	}

	/**
	 * Register and add a textarea field.
	 *
	 * @param string $key     Option key.
	 * @param string $label   Field label.
	 * @param string $section Settings section id.
	 */
	private function add_textarea_field( $key, $label, $section ) {
		add_settings_field(
			'irreq_field_' . $key,
			$label,
			array( $this, 'render_textarea_field' ),
			self::PAGE_SLUG,
			$section,
			array( 'key' => $key )
		);
	}

	/**
	 * Register and add a number field.
	 *
	 * @param string    $key     Option key.
	 * @param string    $label   Field label.
	 * @param string    $section Settings section id.
	 * @param int|float $min     Minimum value.
	 * @param float     $step    Step size.
	 */
	private function add_number_field( $key, $label, $section, $min = 0, $step = 1 ) {
		add_settings_field(
			'irreq_field_' . $key,
			$label,
			array( $this, 'render_number_field' ),
			self::PAGE_SLUG,
			$section,
			array(
				'key'  => $key,
				'min'  => $min,
				'step' => $step,
			)
		);
	}

	/**
	 * Register and add a checkbox field.
	 *
	 * @param string $key         Option key.
	 * @param string $label       Field label.
	 * @param string $section     Settings section id.
	 * @param string $description Optional description shown next to the checkbox.
	 */
	private function add_checkbox_field( $key, $label, $section, $description = '' ) {
		add_settings_field(
			'irreq_field_' . $key,
			$label,
			array( $this, 'render_checkbox_field' ),
			self::PAGE_SLUG,
			$section,
			array( 'key' => $key, 'description' => $description )
		);
	}

	/** Render a text input. */
	public function render_text_field( $args ) {
		$settings = irreq_get_settings();
		$key      = $args['key'];
		$value    = isset( $settings[ $key ] ) ? $settings[ $key ] : '';
		printf(
			'<input type="text" class="irreq-text-input" name="%s[%s]" value="%s" />',
			esc_attr( IRREQ_OPTION_KEY ),
			esc_attr( $key ),
			esc_attr( $value )
		);
	}

	/** Render a password input. */
	public function render_password_field( $args ) {
		$settings = irreq_get_settings();
		$key      = $args['key'];
		$value    = isset( $settings[ $key ] ) ? $settings[ $key ] : '';
		printf(
			'<input type="password" class="irreq-text-input" name="%s[%s]" value="%s" autocomplete="new-password" />',
			esc_attr( IRREQ_OPTION_KEY ),
			esc_attr( $key ),
			esc_attr( $value )
		);
	}

	/** Render a textarea. */
	public function render_textarea_field( $args ) {
		$settings = irreq_get_settings();
		$key      = $args['key'];
		$value    = isset( $settings[ $key ] ) ? $settings[ $key ] : '';
		printf(
			'<textarea class="irreq-textarea" name="%s[%s]" rows="3">%s</textarea>',
			esc_attr( IRREQ_OPTION_KEY ),
			esc_attr( $key ),
			esc_textarea( $value )
		);
	}

	/** Render a number input. */
	public function render_number_field( $args ) {
		$settings = irreq_get_settings();
		$key      = $args['key'];
		$value    = isset( $settings[ $key ] ) ? $settings[ $key ] : '';
		$min      = isset( $args['min'] ) ? $args['min'] : 0;
		$step     = isset( $args['step'] ) ? $args['step'] : 1;
		printf(
			'<input type="number" class="irreq-number-input" name="%s[%s]" value="%s" min="%s" step="%s" />',
			esc_attr( IRREQ_OPTION_KEY ),
			esc_attr( $key ),
			esc_attr( $value ),
			esc_attr( $min ),
			esc_attr( $step )
		);
	}

	/** Render a checkbox input. */
	public function render_checkbox_field( $args ) {
		$settings = irreq_get_settings();
		$key      = $args['key'];
		$desc     = isset( $args['description'] ) ? $args['description'] : '';
		printf(
			'<label><input type="checkbox" name="%s[%s]" value="1"%s /> %s</label>',
			esc_attr( IRREQ_OPTION_KEY ),
			esc_attr( $key ),
			checked( ! empty( $settings[ $key ] ), true, false ),
			esc_html( $desc )
		);
	}

	// ── Sanitisation ──────────────────────────────────────────────────────

	/**
	 * Sanitise all incoming settings values.
	 *
	 * @param array $input Raw POST data.
	 * @return array Sanitised values.
	 */
	public function sanitize_settings( $input ) {
		$defaults = irreq_default_settings();
		$output   = array();

		$text_fields = array(
			'form_title', 'form_subtitle', 'submit_button_text',
			'receiver_email', 'from_name', 'from_email', 'email_subject_template',
			'label_roof_size', 'placeholder_roof_size',
			'label_roof_material', 'label_roof_condition',
			'label_service', 'service_description',
			'label_name', 'placeholder_name',
			'label_phone', 'placeholder_phone',
			'label_email', 'placeholder_email',
			'label_address', 'placeholder_address',
			'label_suburb', 'placeholder_suburb',
			'label_details', 'placeholder_details',
			'cf_site_key',
		);

		foreach ( $text_fields as $field ) {
			$output[ $field ] = isset( $input[ $field ] )
				? sanitize_text_field( $input[ $field ] )
				: $defaults[ $field ];
		}

		// Textarea fields.
		$output['success_message'] = isset( $input['success_message'] )
			? sanitize_textarea_field( $input['success_message'] )
			: $defaults['success_message'];

		// Checkbox fields – absent when unchecked so default to '0'.
		$checkbox_fields = array( 'show_address', 'show_suburb', 'show_details' );
		foreach ( $checkbox_fields as $field ) {
			$output[ $field ] = ! empty( $input[ $field ] ) ? '1' : '0';
		}

		// Secret key – strip tags only (may contain non-word chars).
		$output['cf_secret_key'] = isset( $input['cf_secret_key'] )
			? sanitize_text_field( $input['cf_secret_key'] )
			: '';

		// Email fields – validate format.
		foreach ( array( 'receiver_email', 'from_email' ) as $email_field ) {
			if ( ! is_email( $output[ $email_field ] ) ) {
				$output[ $email_field ] = $defaults[ $email_field ];
				add_settings_error(
					IRREQ_OPTION_KEY,
					'invalid_email_' . $email_field,
					sprintf(
						/* translators: %s: field name */
						__( 'Invalid email address for "%s". Reverted to default.', 'impact-roof-estimate' ),
						$email_field
					)
				);
			}
		}

		// Number fields – cast and enforce positive.
		$number_fields = array(
			'min_job_total', 'base_rate',
			'material_concrete_multiplier', 'material_metal_tile_multiplier', 'material_longrun_multiplier',
			'condition_good_multiplier', 'condition_average_multiplier', 'condition_poor_multiplier',
		);
		foreach ( $number_fields as $field ) {
			$val = isset( $input[ $field ] ) ? floatval( $input[ $field ] ) : floatval( $defaults[ $field ] );
			$output[ $field ] = max( 0, $val );
		}

		return $output;
	}

	// ── Page renderer ─────────────────────────────────────────────────────

	/**
	 * Render the full settings page HTML.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap irreq-wrap">
			<h1 class="irreq-page-title">
				<span class="dashicons dashicons-admin-home"></span>
				<?php esc_html_e( 'Roof Estimate &amp; Quote – Settings', 'impact-roof-estimate' ); ?>
			</h1>

			<?php settings_errors( IRREQ_OPTION_KEY ); ?>

			<?php $this->render_shortcode_builder(); ?>

			<form method="post" action="options.php" class="irreq-settings-form">
				<?php
				settings_fields( 'irreq_settings_group' );
				do_settings_sections( self::PAGE_SLUG );
				submit_button( __( 'Save Settings', 'impact-roof-estimate' ) );
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render the Shortcode Builder panel.
	 */
	private function render_shortcode_builder() {
		$s = irreq_get_settings();
		// Determine default checked state for optional fields from global settings.
		$def_address = ! empty( $s['show_address'] );
		$def_suburb  = ! empty( $s['show_suburb'] );
		$def_details = ! empty( $s['show_details'] );
		?>
		<div class="irreq-builder-panel" id="irreq-shortcode-builder">
			<h2 class="irreq-builder-heading">
				<span class="dashicons dashicons-shortcode"></span>
				<?php esc_html_e( 'Shortcode Builder', 'impact-roof-estimate' ); ?>
				<button type="button" class="irreq-builder-toggle button button-small" aria-expanded="false">
					<?php esc_html_e( 'Show / Hide', 'impact-roof-estimate' ); ?>
				</button>
			</h2>
			<div class="irreq-builder-body" style="display:none;">
				<p class="irreq-section-desc">
					<?php esc_html_e( 'Configure a custom shortcode below, then copy it into any page or post. Each shortcode can have its own background colour, sections, and visible/required fields.', 'impact-roof-estimate' ); ?>
				</p>

				<div class="irreq-builder-grid">

					<!-- Col 1: Appearance + Sections -->
					<div class="irreq-builder-col">
						<div class="irreq-builder-group">
							<h3><?php esc_html_e( 'Appearance', 'impact-roof-estimate' ); ?></h3>
							<table class="irreq-bld-table">
								<tr>
									<td><?php esc_html_e( 'Background Colour', 'impact-roof-estimate' ); ?></td>
									<td><input type="color" id="bldBgColor" value="#0f1724" /></td>
								</tr>
								<tr>
									<td><?php esc_html_e( 'Background Opacity', 'impact-roof-estimate' ); ?></td>
									<td>
										<input type="range" id="bldBgOpacity" min="0" max="1" step="0.05" value="1" style="width:120px;" />
										<span id="bldBgOpacityVal">1</span>
									</td>
								</tr>
							</table>
						</div>

						<div class="irreq-builder-group">
							<h3><?php esc_html_e( 'Sections', 'impact-roof-estimate' ); ?></h3>
							<label class="irreq-bld-check">
								<input type="checkbox" id="bldShowTitle" checked />
								<?php esc_html_e( 'Show Title &amp; Subtitle', 'impact-roof-estimate' ); ?>
							</label>
							<label class="irreq-bld-check">
								<input type="checkbox" id="bldShowEstimate" checked />
								<?php esc_html_e( 'Show Estimate Section', 'impact-roof-estimate' ); ?>
							</label>
							<label class="irreq-bld-check">
								<input type="checkbox" id="bldShowContact" checked />
								<?php esc_html_e( 'Show Contact Section', 'impact-roof-estimate' ); ?>
							</label>
						</div>
					</div>

					<!-- Col 2: Estimate Fields -->
					<div class="irreq-builder-col">
						<div class="irreq-builder-group">
							<h3><?php esc_html_e( 'Estimate Fields', 'impact-roof-estimate' ); ?></h3>
							<table class="irreq-bld-table">
								<thead>
									<tr>
										<th><?php esc_html_e( 'Field', 'impact-roof-estimate' ); ?></th>
										<th><?php esc_html_e( 'Show', 'impact-roof-estimate' ); ?></th>
										<th><?php esc_html_e( 'Required', 'impact-roof-estimate' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<tr class="irreq-bld-estimate-row">
										<td><?php esc_html_e( 'Roof Size', 'impact-roof-estimate' ); ?></td>
										<td><input type="checkbox" id="bldShowRoofSize" checked /></td>
										<td><input type="checkbox" id="bldReqRoofSize" checked /></td>
									</tr>
									<tr class="irreq-bld-estimate-row">
										<td><?php esc_html_e( 'Roof Material', 'impact-roof-estimate' ); ?></td>
										<td><input type="checkbox" id="bldShowRoofMaterial" checked /></td>
										<td><input type="checkbox" id="bldReqRoofMaterial" checked /></td>
									</tr>
									<tr class="irreq-bld-estimate-row">
										<td><?php esc_html_e( 'Roof Condition', 'impact-roof-estimate' ); ?></td>
										<td><input type="checkbox" id="bldShowRoofCondition" checked /></td>
										<td><input type="checkbox" id="bldReqRoofCondition" checked /></td>
									</tr>
									<tr class="irreq-bld-estimate-row">
										<td><?php esc_html_e( 'Service', 'impact-roof-estimate' ); ?></td>
										<td><input type="checkbox" id="bldShowService" checked /></td>
										<td><input type="checkbox" id="bldReqService" checked /></td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>

					<!-- Col 3: Contact Fields -->
					<div class="irreq-builder-col">
						<div class="irreq-builder-group">
							<h3><?php esc_html_e( 'Contact Fields', 'impact-roof-estimate' ); ?></h3>
							<table class="irreq-bld-table">
								<thead>
									<tr>
										<th><?php esc_html_e( 'Field', 'impact-roof-estimate' ); ?></th>
										<th><?php esc_html_e( 'Show', 'impact-roof-estimate' ); ?></th>
										<th><?php esc_html_e( 'Required', 'impact-roof-estimate' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<tr class="irreq-bld-contact-row">
										<td><?php esc_html_e( 'Name', 'impact-roof-estimate' ); ?></td>
										<td><input type="checkbox" id="bldShowName" checked /></td>
										<td><input type="checkbox" id="bldReqName" checked /></td>
									</tr>
									<tr class="irreq-bld-contact-row">
										<td><?php esc_html_e( 'Phone', 'impact-roof-estimate' ); ?></td>
										<td><input type="checkbox" id="bldShowPhone" checked /></td>
										<td><input type="checkbox" id="bldReqPhone" checked /></td>
									</tr>
									<tr class="irreq-bld-contact-row">
										<td><?php esc_html_e( 'Email', 'impact-roof-estimate' ); ?></td>
										<td><input type="checkbox" id="bldShowEmail" checked /></td>
										<td><input type="checkbox" id="bldReqEmail" checked /></td>
									</tr>
									<tr class="irreq-bld-contact-row">
										<td><?php esc_html_e( 'Address', 'impact-roof-estimate' ); ?></td>
										<td><input type="checkbox" id="bldShowAddress" <?php checked( $def_address ); ?> /></td>
										<td><input type="checkbox" id="bldReqAddress" checked /></td>
									</tr>
									<tr class="irreq-bld-contact-row">
										<td><?php esc_html_e( 'Suburb', 'impact-roof-estimate' ); ?></td>
										<td><input type="checkbox" id="bldShowSuburb" <?php checked( $def_suburb ); ?> /></td>
										<td><input type="checkbox" id="bldReqSuburb" /></td>
									</tr>
									<tr class="irreq-bld-contact-row">
										<td><?php esc_html_e( 'Details', 'impact-roof-estimate' ); ?></td>
										<td><input type="checkbox" id="bldShowDetails" <?php checked( $def_details ); ?> /></td>
										<td><input type="checkbox" id="bldReqDetails" /></td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>

				</div><!-- .irreq-builder-grid -->

				<div class="irreq-builder-output">
					<label for="bldOutput"><strong><?php esc_html_e( 'Generated Shortcode', 'impact-roof-estimate' ); ?></strong></label>
					<div class="irreq-builder-output-row">
						<input type="text" id="bldOutput" class="irreq-builder-code" readonly value="[roof_estimate_quote]" />
						<button type="button" id="bldCopyBtn" class="button button-primary">
							<?php esc_html_e( 'Copy', 'impact-roof-estimate' ); ?>
						</button>
					</div>
					<p class="irreq-section-desc">
						<?php esc_html_e( 'Paste this shortcode into any page or post to display the form with these settings.', 'impact-roof-estimate' ); ?>
					</p>
				</div>

			</div><!-- .irreq-builder-body -->
		</div><!-- .irreq-builder-panel -->
		<?php
	}
}
