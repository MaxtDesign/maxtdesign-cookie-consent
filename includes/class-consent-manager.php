<?php
/**
 * Core Consent Manager
 *
 * Handles Google Consent Mode v2 implementation, consent state management,
 * and frontend JavaScript enqueuing. Zero database queries, localStorage-based.
 *
 * @package MaxtDesign_Cookie_Consent
 * @since 1.6.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class MDCC_Consent_Manager {

    /**
     * Single instance
     *
     * @var MDCC_Consent_Manager
     */
    private static $instance = null;

    /**
     * localStorage key for consent state
     *
     * @var string
     */
    const STORAGE_KEY = 'mdcc_consent';

    /**
     * Get instance
     *
     * @return MDCC_Consent_Manager
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Inject GCM default consent BEFORE all scripts (priority 1)
        add_action('wp_head', array($this, 'inject_gcm_default'), 1);

        // Enqueue frontend scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
    }

    /**
     * Inject Google Consent Mode v2 default state in <head>
     *
     * Sets initial consent to 'denied' for all consent types BEFORE any tracking
     * scripts load. This ensures GTM, GA4, and Google Ads respect user consent
     * from the very first pageview.
     *
     * Only injects if the 'gcm_inject_default' setting is enabled (default: true).
     * Priority 1 on wp_head ensures this runs before tracking scripts.
     * wait_for_update gives consent-runtime.js 500ms to load and call 'update'.
     *
     * @since 1.7.1
     * @return void
     */
    public function inject_gcm_default() {
        // Don't inject in admin
        if (is_admin()) {
            return;
        }

        // Check if setting is enabled
        $settings = get_option('mdcc_settings', mdcc_default_settings());
        $inject_enabled = isset($settings['gcm_inject_default']) ? $settings['gcm_inject_default'] : true;

        if (!$inject_enabled) {
            return;
        }

        ?>
    <script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('consent', 'default', {
    'analytics_storage': 'denied',
    'ad_storage': 'denied',
    'ad_user_data': 'denied',
    'ad_personalization': 'denied',
    'wait_for_update': 500
});
    </script>
        <?php
    }

    /**
     * Enqueue frontend consent runtime JavaScript
     *
     * Loads the core consent logic with GCM v2 implementation.
     * Loads minified version by default, source version when SCRIPT_DEBUG is enabled.
     *
     * @since 1.6.0
     * @return void
     */
    public function enqueue_frontend_assets() {
        // Don't load in admin
        if (is_admin()) {
            return;
        }

        $suffix = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';

        wp_enqueue_script(
            'mdcc-consent-runtime',
            MDCC_PLUGIN_URL . 'assets/js/consent-runtime' . $suffix . '.js',
            array(),
            MDCC_VERSION,
            true
        );

        // Pass configuration to JavaScript
        wp_localize_script(
            'mdcc-consent-runtime',
            'mdccConfig',
            array(
                'storageKey' => self::STORAGE_KEY,
                'debug'      => defined('WP_DEBUG') && WP_DEBUG,
            )
        );
    }

    /**
     * Get default consent state
     *
     * Returns the default "denied all" state for privacy compliance.
     *
     * @since 1.6.0
     * @return array Default consent state
     */
    public static function get_default_state() {
        return array(
            'analytics' => false,
            'ads'       => false,
        );
    }
}

