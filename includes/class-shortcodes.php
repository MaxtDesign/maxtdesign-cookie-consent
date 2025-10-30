<?php
/**
 * Shortcode handlers (placeholder)
 *
 * @package MaxtDesign_Lean_Consent
 * @since 1.6.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class MDLC_Shortcodes {
    /**
     * Single instance of the class
     *
     * @var MDLC_Shortcodes|null
     */
    private static $instance = null;

    /**
     * Get instance
     *
     * @return MDLC_Shortcodes
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
        // Hooks will be added in subsequent phases
    }
}


