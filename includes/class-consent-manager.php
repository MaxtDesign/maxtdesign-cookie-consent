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
        // Enqueue frontend scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
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

