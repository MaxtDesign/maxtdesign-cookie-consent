<?php
/**
 * Plugin Name: MaxtDesign Lean Consent
 * Plugin URI: https://maxtdesign.com/lean-consent
 * Description: Lightweight consent management (<10KB gzipped) with proper Google Consent Mode v2. CCPA/GDPR compliance without slowing your site.
 * Version: 1.6.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: MaxtDesign
 * Author URI: https://maxtdesign.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: maxtdesign-lean-consent
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

// -----------------------------------------------------------------------------
// Constants
// -----------------------------------------------------------------------------
define('MDLC_VERSION', '1.6.0');
define('MDLC_PLUGIN_FILE', __FILE__);
define('MDLC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MDLC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MDLC_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('MDLC_TEXT_DOMAIN', 'maxtdesign-lean-consent');

// -----------------------------------------------------------------------------
// Autoloader (MDLC_ prefixed classes from includes/)
// -----------------------------------------------------------------------------
spl_autoload_register(function ($class) {
    if (strpos($class, 'MDLC_') !== 0) {
        return;
    }

    $class_file = 'class-' . str_replace('_', '-', strtolower(substr($class, 5))) . '.php';
    $file_path  = MDLC_PLUGIN_DIR . 'includes/' . $class_file;

    if (file_exists($file_path)) {
        require_once $file_path;
    }
});

// -----------------------------------------------------------------------------
// Activation / Deactivation
// -----------------------------------------------------------------------------
register_activation_hook(__FILE__, 'mdlc_activate_plugin');
function mdlc_activate_plugin() {
    if (version_compare(get_bloginfo('version'), '5.8', '<')) {
        wp_die(esc_html__('MaxtDesign Lean Consent requires WordPress 5.8 or higher.', 'maxtdesign-lean-consent'));
    }

    if (version_compare(PHP_VERSION, '7.4', '<')) {
        wp_die(esc_html__('MaxtDesign Lean Consent requires PHP 7.4 or higher.', 'maxtdesign-lean-consent'));
    }

    if (!get_option('mdlc_settings')) {
        add_option('mdlc_settings', mdlc_default_settings());
    }

    update_option('mdlc_version', MDLC_VERSION);
}

register_deactivation_hook(__FILE__, 'mdlc_deactivate_plugin');
function mdlc_deactivate_plugin() {
    delete_transient('mdlc_cache');
}

// -----------------------------------------------------------------------------
// Defaults
// -----------------------------------------------------------------------------
function mdlc_default_settings() {
    return array(
        'popup_enabled'        => true,
        'popup_style'          => 'minimal',
        'popup_position'       => 'bottom',
        'popup_primary_color'  => '#0073aa',
        'popup_animation'      => 'slide',
        'popup_title'          => __('Cookie Consent', 'maxtdesign-lean-consent'),
        'popup_message'        => __('We use cookies to enhance your browsing experience and analyze our traffic.', 'maxtdesign-lean-consent'),
        'popup_shown_duration' => 7,
        'reprompt_on_decline'  => false,
        'elementor_popup_id'   => '',
    );
}

// -----------------------------------------------------------------------------
// Bootstrap
// -----------------------------------------------------------------------------
add_action('plugins_loaded', 'mdlc_init_plugin');
function mdlc_init_plugin() {
    if (class_exists('MDLC_Consent_Manager')) {
        MDLC_Consent_Manager::get_instance();
    }

    if (class_exists('MDLC_Popup_System')) {
        MDLC_Popup_System::get_instance();
    }

    if (is_admin() && class_exists('MDLC_Admin_Settings')) {
        MDLC_Admin_Settings::get_instance();
    }

    if (class_exists('MDLC_Shortcodes')) {
        MDLC_Shortcodes::get_instance();
    }
}


