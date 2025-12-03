<?php
/**
 * Plugin Name: MaxtDesign Cookie Consent - Google Consent Mode v2
 * Plugin URI: https://maxtdesign.com/
 * Description: Actually controls Google Analytics & Ads tracking (not just a banner). Free alternative to $50/month solutions. Works with existing GA4. Won't slow your site.
 * Version: 1.6.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: MaxtDesign
 * Author URI: https://maxtdesign.com
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
define('MDCC_VERSION', '1.6.0');
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
        'popup_shown_duration' => 7,
        'reprompt_on_decline'  => false,
        'elementor_popup_id'   => '',
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



