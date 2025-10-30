<?php
/**
 * Core Consent Manager
 *
 * Handles Google Consent Mode v2 implementation, consent state management,
 * and frontend JavaScript enqueuing. Zero database queries, localStorage-based.
 *
 * @package MaxtDesign_Lean_Consent
 * @since 1.6.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class MDLC_Consent_Manager {

    /**
     * Single instance
     *
     * @var MDLC_Consent_Manager
     */
    private static $instance = null;

    /**
     * localStorage key for consent state
     *
     * @var string
     */
    const STORAGE_KEY = 'mdlc_consent';

    /**
     * Get instance
     *
     * @return MDLC_Consent_Manager
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
     * Passes configuration data via wp_localize_script for security.
     *
     * @since 1.6.0
     * @return void
     */
    public function enqueue_frontend_assets() {
        // Don't load in admin
        if (is_admin()) {
            return;
        }

        // Enqueue the consent runtime script
        wp_enqueue_script(
            'mdlc-consent-runtime',
            MDLC_PLUGIN_URL . 'assets/js/consent-runtime.js',
            array(), // No dependencies (vanilla JS)
            MDLC_VERSION,
            true // Load in footer
        );

        // Pass configuration to JavaScript
        wp_localize_script(
            'mdlc-consent-runtime',
            'mdlcConfig',
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

