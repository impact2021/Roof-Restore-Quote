=== Impact Websites – Roof Estimate and Quote ===
Contributors: impactwebsites
Tags: roof, estimate, quote, calculator, contact form
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt

Displays an instant roof painting estimate calculator and contact form via shortcode. Fully configurable from the admin settings page.

== Description ==

**Impact Websites – Roof Estimate and Quote** lets you embed an interactive roof painting price estimator anywhere on your WordPress site using a simple shortcode.

Visitors enter their roof size, material type, and condition to instantly see a ballpark price range. Once happy with the estimate, they submit their contact details and the lead is emailed directly to you.

**Features:**

* Instant client-side price calculation (no page reload)
* Configurable pricing formula: base rate × material multiplier × condition multiplier
* Minimum job floor price
* Admin settings page to control all labels, pricing values, and email settings
* AJAX form submission with nonce verification
* Optional Cloudflare Turnstile (CAPTCHA) bot protection
* Plain-text notification email with Reply-To set to the customer's address
* Mobile-first responsive design

**Shortcode:**

Place `[roof_estimate_quote]` on any page or post to display the form.

== Installation ==

1. Upload the `impact-roof-estimate` folder to the `/wp-content/plugins/` directory, or install the plugin through the WordPress Plugins screen directly.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Go to **Roof Estimate form** in the admin sidebar to configure pricing, email, and display settings.
4. Add the shortcode `[roof_estimate_quote]` to any page or post where you want the form to appear.

== Configuration ==

After activation, visit **Roof Estimate form** in the WordPress admin menu.

**General**
* Form title and subtitle
* Submit button text
* Success message shown after a successful submission

**Email / Notifications**
* Receiver email address (where leads are sent)
* "From" name and email address
* Email subject template — supports `{name}`, `{email}`, `{phone}`, and `{estimate}` tokens

**Estimate / Pricing**
* Minimum job total (floor price)
* Base rate per m²
* Material multipliers: Concrete Tile, Metal Tile / Decramastic, Longrun Metal
* Condition multipliers: Good, Average, Poor

**Form Fields**
* Customise all labels and placeholder text

**Cloudflare Turnstile (CAPTCHA)**
* Enter your Site Key and Secret Key from the [Cloudflare Turnstile dashboard](https://dash.cloudflare.com/?to=/:account/turnstile) to enable bot protection. Leave blank to disable.

== Frequently Asked Questions ==

= How is the estimate calculated? =

`Estimate = Roof Size (m²) × Base Rate × Material Multiplier × Condition Multiplier`

If the result is below the configured **Minimum Job Total** the floor price is used instead. The displayed range is ±10% of the calculated figure.

= Can I change the currency symbol? =

The JavaScript currently formats amounts as NZD (`NZ$`). To change this, edit `assets/js/form-public.js` and update the `formatNZD` function.

= Does it work without Cloudflare Turnstile? =

Yes. Leave both Turnstile key fields blank and no CAPTCHA widget will be shown. Nonce-based spam protection is always active.

== Changelog ==

= 1.0.0 =
* Initial release.
