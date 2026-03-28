# Impact Websites – Roof Estimate and Quote

A WordPress plugin that embeds an interactive roof painting price estimator and lead-capture form anywhere on your site via a shortcode.

## Features

- Instant client-side price calculation (no page reload)
- Configurable pricing formula: base rate × material multiplier × condition multiplier with a minimum job floor price
- Admin settings page to control all labels, pricing values, and email notifications
- AJAX form submission with nonce verification
- Optional Cloudflare Turnstile (CAPTCHA) bot protection
- Mobile-first responsive design

## Installation

1. Clone or download this repository.
2. Copy the `impact-roof-estimate` folder into your WordPress installation's `wp-content/plugins/` directory.
3. In your WordPress admin dashboard, go to **Plugins → Installed Plugins** and activate **Impact Websites – Roof Estimate and Quote**.
4. Navigate to **Roof Estimate form** in the admin sidebar to configure pricing, email, and display settings.
5. Add the shortcode `[roof_estimate_quote]` to any page or post where you want the form to appear.

## Usage

Place the shortcode on any page or post:

```
[roof_estimate_quote]
```

Visitors enter their roof size (m²), material type, and condition to get an instant price estimate. They can then submit their contact details to send a lead notification email to the configured address.

## Configuration

All settings are managed from **Roof Estimate form** in the WordPress admin menu:

| Section | Settings |
|---|---|
| General | Form title, subtitle, submit button text, success message |
| Email / Notifications | Receiver address, From name/email, subject template (`{name}`, `{email}`, `{phone}`, `{estimate}` tokens) |
| Estimate / Pricing | Minimum job total, base rate per m², material and condition multipliers |
| Form Fields | Labels and placeholder text for every field |
| Cloudflare Turnstile | Site Key and Secret Key for optional CAPTCHA protection |

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher

## License

GPL-2.0+. See [LICENSE](http://www.gnu.org/licenses/gpl-2.0.txt).
