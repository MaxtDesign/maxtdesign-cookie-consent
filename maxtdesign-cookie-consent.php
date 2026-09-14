<?php
/**
 * Plugin Name: MaxtDesign Cookie Consent - Google Consent Mode v2
 * Plugin URI: https://maxtdesign.com/plugins/cookie-consent
 * Description: Actually controls Google Analytics & Ads tracking (not just a banner). Free alternative to $50/month solutions. Works with existing GA4. Won't slow your site.
 * Version: 1.10.1
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: MaxtDesign
 * Author URI: https://maxtdesign.com
 * Donate link: https://github.com/sponsors/MaxtDesign
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: maxtdesign-cookie-consent
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

// -----------------------------------------------------------------------------
// Constants
// -----------------------------------------------------------------------------
define('MDCC_VERSION', '1.10.1');
define('MDCC_PLUGIN_FILE', __FILE__);
define('MDCC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MDCC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MDCC_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('MDCC_TEXT_DOMAIN', 'maxtdesign-cookie-consent');

// -----------------------------------------------------------------------------
// Autoloader (MDCC_ prefixed classes from includes/)
// -----------------------------------------------------------------------------
spl_autoload_register(function ($class) {
    if (strpos($class, 'MDCC_') !== 0) {
        return;
    }

    $class_file = 'class-' . str_replace('_', '-', strtolower(substr($class, 5))) . '.php';
    $file_path  = MDCC_PLUGIN_DIR . 'includes/' . $class_file;

    if (file_exists($file_path)) {
        require_once $file_path;
    }
});

// -----------------------------------------------------------------------------
// Activation / Deactivation
// -----------------------------------------------------------------------------
register_activation_hook(__FILE__, 'mdcc_activate_plugin');
function mdcc_activate_plugin() {
    if (version_compare(get_bloginfo('version'), '5.8', '<')) {
        wp_die(esc_html__('MaxtDesign Cookie Consent - Google Consent Mode v2 requires WordPress 5.8 or higher.', 'maxtdesign-cookie-consent'));
    }

    if (version_compare(PHP_VERSION, '7.4', '<')) {
        wp_die(esc_html__('MaxtDesign Cookie Consent - Google Consent Mode v2 requires PHP 7.4 or higher.', 'maxtdesign-cookie-consent'));
    }

    if (!get_option('mdcc_settings')) {
        add_option('mdcc_settings', mdcc_default_settings());
    }

    update_option('mdcc_version', MDCC_VERSION);
}

register_deactivation_hook(__FILE__, 'mdcc_deactivate_plugin');
function mdcc_deactivate_plugin() {
    delete_transient('mdcc_cache');
}

// -----------------------------------------------------------------------------
// Defaults
// -----------------------------------------------------------------------------
function mdcc_default_settings() {
    return array(
        'popup_enabled'        => true,
        'popup_style'          => 'minimal',
        'popup_position'       => 'bottom',
        'popup_primary_color'  => '#0073aa',
        'popup_animation'      => 'slide',
        'popup_title'          => __('Cookie Consent', 'maxtdesign-cookie-consent'),
        'popup_message'        => __('We use cookies to enhance your browsing experience and analyze our traffic.', 'maxtdesign-cookie-consent'),
        // Shown instead of the two fields above to visitors in an opt-out-notice
        // region (consent_model 'regional' -> California / Pacific time zone,
        // or 'optout' -> everyone). CCPA/CPRA wording: tracking is on by default
        // and the visitor may opt out. The swap happens client-side in popup.js;
        // the buttons become "Got it" and "Do Not Sell or Share My Personal
        // Information".
        'popup_title_optout'   => __('Your privacy choices', 'maxtdesign-cookie-consent'),
        'popup_message_optout' => __('We use cookies and analytics to improve the store and measure our advertising. You can opt out of the sale or sharing of your personal information at any time.', 'maxtdesign-cookie-consent'),
        'popup_shown_duration' => 7,
        // 'optin' (GDPR everywhere; the pre-1.10 behavior), 'regional' (opt-in
        // in the EEA/UK/CH, implied consent elsewhere) or 'optout' (implied
        // consent everywhere). See MDCC_Consent_Manager::consent_models().
        'consent_model'        => 'optin',
        'reprompt_on_decline'  => false,
        'elementor_popup_id'   => '',
        'gcm_inject_default'   => true,
        'consent_api_bridge'   => true,
    );
}

// -----------------------------------------------------------------------------
// Bootstrap
// -----------------------------------------------------------------------------
add_action('plugins_loaded', 'mdcc_init_plugin');
function mdcc_init_plugin() {
    if (class_exists('MDCC_Consent_Manager')) {
        MDCC_Consent_Manager::get_instance();
    }

    if (class_exists('MDCC_Consent_API_Bridge')) {
        MDCC_Consent_API_Bridge::get_instance();
    }

    if (class_exists('MDCC_Popup_System')) {
        MDCC_Popup_System::get_instance();
    }

    if (is_admin() && class_exists('MDCC_Admin_Settings')) {
        MDCC_Admin_Settings::get_instance();
    }

    if (class_exists('MDCC_Shortcodes')) {
        MDCC_Shortcodes::get_instance();
    }
}

// -----------------------------------------------------------------------------
// Privacy Policy generator content
// -----------------------------------------------------------------------------
add_action('admin_init', 'mdcc_register_privacy_policy_content');
function mdcc_register_privacy_policy_content() {
    if (!function_exists('wp_add_privacy_policy_content')) {
        return;
    }

    $paragraphs = array(
        esc_html__('This site uses MaxtDesign Cookie Consent to manage your tracking preferences for Google Analytics and Google Ads. When you make a choice in the consent popup, that choice is stored locally in your browser using localStorage (key: mdcc_consent). It is not transmitted to our servers.', 'maxtdesign-cookie-consent'),
        esc_html__('A small cookie named mdcc_popup_shown is set when you dismiss the popup, so we do not show it again for the duration configured by the site administrator (default 7 days). This cookie contains only a flag value and no personal data.', 'maxtdesign-cookie-consent'),
        esc_html__('This plugin implements Google Consent Mode v2. When you grant or deny consent, the choice is signalled to Google Analytics and Google Ads via their gtag API. Please refer to Google\'s own privacy policies for details on data they collect once consent is granted.', 'maxtdesign-cookie-consent'),
        esc_html__('If the WP Consent API plugin is active, your choice is also shared with other consent-aware plugins on this site using WordPress\'s standard consent signals: your analytics choice maps to the "statistics" category and your advertising choice maps to the "marketing" category, while strictly-necessary ("functional") cookies remain always active. This lets other plugins respect the same decision without setting additional cookies of their own.', 'maxtdesign-cookie-consent'),
    );

    // Describe the consent model when it is not the default opt-in-everywhere.
    $consent_model = class_exists('MDCC_Consent_Manager') ? MDCC_Consent_Manager::get_consent_model() : 'optin';
    if ('regional' === $consent_model) {
        $paragraphs[] = esc_html__('This site uses a regional consent model. If you are visiting from the European Economic Area, the United Kingdom or Switzerland, analytics and advertising tracking stays off until you opt in. If you are visiting from California (Pacific time zone), analytics and advertising are enabled by default and you are shown a "Do Not Sell or Share My Personal Information" notice through which you can opt out at any time, as provided by the CCPA/CPRA; you can also opt out later using the consent management controls on this site. Visitors from all other regions are treated under an implied-consent model with no banner, and can opt out at any time using the consent management controls on this site. Your region is determined in your browser (time zone) and by Google Consent Mode\'s own region handling; no location data is sent to our servers. Browsers that send the Global Privacy Control signal are treated as having opted out: tracking stays off unless you choose to opt in.', 'maxtdesign-cookie-consent');
    } elseif ('optout' === $consent_model) {
        $paragraphs[] = esc_html__('This site uses an opt-out consent model: analytics and advertising are enabled by default and you can opt out at any time using the consent popup or the consent management controls on this site. Browsers that send the Global Privacy Control signal are treated as having opted out.', 'maxtdesign-cookie-consent');
    }

    $content = '<p>' . implode('</p><p>', $paragraphs) . '</p>';

    wp_add_privacy_policy_content(
        __('MaxtDesign Cookie Consent', 'maxtdesign-cookie-consent'),
        wp_kses_post(wpautop($content, false))
    );
}



