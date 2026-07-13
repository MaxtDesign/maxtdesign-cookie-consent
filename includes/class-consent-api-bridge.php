<?php
/**
 * WP Consent API Bridge
 *
 * Makes MaxtDesign Cookie Consent a first-class provider for the WordPress
 * Consent API (the wp.org standard that WooCommerce and others read). This is
 * purely additive: it mirrors the plugin's existing analytics/ads choices onto
 * the standard consent categories so *other* plugins on the site can gate their
 * own behavior with wp_has_consent(), without changing anything this plugin
 * already does client-side.
 *
 * Category mapping (three-tier model):
 *   - functional   → always 'allow' (strictly-necessary cookies are exempt and
 *                    do not require consent; declared explicitly for clarity)
 *   - statistics   → mirrors the plugin's "analytics" choice
 *   - marketing    → mirrors the plugin's "ads" choice
 *
 * The bridge only engages when BOTH conditions hold:
 *   1. The "WP Consent API integration" admin toggle is on (default: on), and
 *   2. The free WP Consent API plugin is active (function_exists('wp_has_consent')).
 *
 * With the WP Consent API plugin absent, every code path here is a no-op and the
 * plugin keeps its existing client-only behavior — nothing breaks.
 *
 * @package MaxtDesign_Cookie_Consent
 * @since 1.8.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class MDCC_Consent_API_Bridge {

    /**
     * Single instance
     *
     * @var MDCC_Consent_API_Bridge
     */
    private static $instance = null;

    /**
     * WP Consent API frontend script handle.
     *
     * Registered by the WP Consent API plugin for assets/js/wp-consent-api.js,
     * which defines wp_set_consent()/wp_has_consent() in the browser.
     *
     * @var string
     */
    const CONSENT_API_HANDLE = 'wp-consent-api';

    /**
     * Get instance
     *
     * @return MDCC_Consent_API_Bridge
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
     * Register hooks.
     *
     * All hooks self-gate on the admin toggle (and, where relevant, on the WP
     * Consent API plugin being active), so registering them unconditionally is
     * safe and keeps the wiring in one place.
     */
    private function init_hooks() {
        // Tell the consent runtime whether to mirror choices into the API.
        add_filter('mdcc_consent_api_enabled', array($this, 'filter_bridge_enabled'));

        // Declare a script load order so wp_set_consent() is available when the
        // runtime initializes (only if the API script is actually registered).
        add_filter('mdcc_consent_runtime_deps', array($this, 'filter_runtime_deps'));

        // Declare this plugin as the site's consent manager (GDPR opt-in model).
        add_filter('wp_get_consent_type', array($this, 'filter_consent_type'));

        // Mark the plugin as an integrated/compatible consent plugin so the WP
        // Consent API does not warn that no compatible manager is present.
        add_filter('wp_consent_api_registered_' . MDCC_PLUGIN_BASENAME, array($this, 'filter_registered'));
    }

    /**
     * Whether the admin has enabled the bridge (independent of API presence).
     *
     * @return bool
     */
    private function toggle_on() {
        $settings = get_option('mdcc_settings', mdcc_default_settings());
        // Default on when the key is absent (older installs upgrading in place).
        return !array_key_exists('consent_api_bridge', $settings) || !empty($settings['consent_api_bridge']);
    }

    /**
     * Whether the WP Consent API plugin is active.
     *
     * @return bool
     */
    private function api_active() {
        return function_exists('wp_has_consent');
    }

    /**
     * Whether the bridge should actively mirror consent (toggle on AND API present).
     *
     * @param bool $enabled Incoming value from the filter chain.
     * @return bool
     */
    public function filter_bridge_enabled($enabled) {
        return $this->toggle_on() && $this->api_active();
    }

    /**
     * Declare the WP Consent API script as a runtime dependency.
     *
     * This is the integration pattern the WP Consent API documents: the consent
     * manager declares a dependency on its script so wp_set_consent() is defined
     * before the runtime initializes. The API plugin enqueues 'wp-consent-api'
     * unconditionally (at PHP_INT_MAX - 100) whenever it is active, and WordPress
     * resolves dependencies at print time — so gating on api_active() guarantees
     * the handle is registered by the time our script prints, without dropping it.
     *
     * We gate on function existence rather than wp_script_is('...','registered'):
     * because the API enqueues so late, the handle is not yet registered when our
     * runtime enqueues at the default priority, so a registered-check would never
     * add the dependency. The runtime additionally guards every call on
     * `typeof wp_set_consent === 'function'`, so even in the unlikely case the
     * handle is absent, behavior is a safe no-op rather than a broken page.
     *
     * @param string[] $deps Existing dependency handles.
     * @return string[]
     */
    public function filter_runtime_deps($deps) {
        if ($this->toggle_on() && $this->api_active()) {
            $deps[] = self::CONSENT_API_HANDLE;
        }
        return $deps;
    }

    /**
     * Declare the site's consent model to the WP Consent API.
     *
     * Returns 'optin' (GDPR: nothing is granted until the visitor allows it),
     * which matches this plugin's deny-by-default behavior. Filterable via
     * mdcc_consent_type for sites that need a different regional model.
     *
     * Only overrides when the admin toggle is on; otherwise the incoming value
     * is passed through untouched so a different consent manager can win.
     *
     * @param string|false $type Incoming consent type.
     * @return string|false
     */
    public function filter_consent_type($type) {
        if (!$this->toggle_on()) {
            return $type;
        }

        /**
         * Filter the consent type this plugin declares to the WP Consent API.
         *
         * @since 1.8.0
         * @param string $type Either 'optin' (GDPR) or 'optout' (CCPA-style).
         */
        return apply_filters('mdcc_consent_type', 'optin');
    }

    /**
     * Report the plugin as an integrated consent plugin.
     *
     * @param bool $registered Incoming value.
     * @return bool
     */
    public function filter_registered($registered) {
        return $this->toggle_on() ? true : $registered;
    }
}
