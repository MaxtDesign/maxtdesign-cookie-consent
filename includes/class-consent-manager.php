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
     * Consent model: opt-in everywhere (GDPR). The pre-1.10.0 behavior.
     *
     * @since 1.10.0
     */
    const MODEL_OPTIN = 'optin';

    /**
     * Consent model: opt-in inside the EEA/UK/CH, implied consent elsewhere.
     *
     * @since 1.10.0
     */
    const MODEL_REGIONAL = 'regional';

    /**
     * Consent model: implied consent everywhere; the visitor can opt out.
     *
     * @since 1.10.0
     */
    const MODEL_OPTOUT = 'optout';

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
     * The consent models this plugin supports.
     *
     * Used as the whitelist for the `consent_model` setting and to build the
     * admin select. Keys are stored values; values are admin labels.
     *
     * @since 1.10.0
     * @return array<string, string> model => label
     */
    public static function consent_models() {
        return array(
            self::MODEL_OPTIN    => __('Opt-in everywhere (GDPR)', 'maxtdesign-cookie-consent'),
            self::MODEL_REGIONAL => __('Regional: opt-in in the EEA, UK and Switzerland; opt-out notice in California; no banner elsewhere', 'maxtdesign-cookie-consent'),
            self::MODEL_OPTOUT   => __('Opt-out everywhere (implied consent)', 'maxtdesign-cookie-consent'),
        );
    }

    /**
     * The configured consent model, validated against consent_models().
     *
     * Falls back to 'optin' for installs saved before this setting existed and
     * for any unrecognised value, so upgrading never changes behavior.
     *
     * @since 1.10.0
     * @return string One of 'optin', 'regional', 'optout'.
     */
    public static function get_consent_model() {
        $settings = get_option('mdcc_settings', mdcc_default_settings());
        $model    = is_array($settings) && isset($settings['consent_model']) ? (string) $settings['consent_model'] : self::MODEL_OPTIN;

        return array_key_exists($model, self::consent_models()) ? $model : self::MODEL_OPTIN;
    }

    /**
     * ISO 3166-1 alpha-2 codes of the regions that require opt-in consent under
     * the 'regional' model: the EEA (EU 27 + IS, LI, NO), the UK and Switzerland.
     *
     * Consumed by inject_gcm_default() as the `region` list on the denied
     * gtag('consent','default') command; Google resolves the visitor's region
     * itself, so the plugin performs no geolocation.
     *
     * @since 1.10.0
     * @return string[]
     */
    public static function optin_regions() {
        $regions = array(
            'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR',
            'DE', 'GR', 'HU', 'IS', 'IE', 'IT', 'LV', 'LI', 'LT', 'LU',
            'MT', 'NL', 'NO', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE',
            'GB', 'CH',
        );

        /**
         * Filter the regions that require opt-in consent under the 'regional' model.
         *
         * Google Consent Mode accepts ISO 3166-2 codes here, so subdivisions
         * such as 'US-CA' are valid additions. Returned values are upper-cased
         * and de-duplicated.
         *
         * @since 1.10.0
         * @param string[] $regions ISO 3166-1/3166-2 region codes.
         */
        $regions = (array) apply_filters('mdcc_optin_regions', $regions);

        return array_values(array_unique(array_map('strtoupper', array_filter(array_map('strval', $regions)))));
    }

    /**
     * Browser time-zone heuristic the runtime uses to decide whether to show
     * the opt-in banner under the 'regional' model.
     *
     * This is a banner-presentation heuristic only: Google Consent Mode's own
     * region handling (see optin_regions()) governs the tracking default. Any
     * IANA zone starting with one of `prefixes`, or listed in `zones`, is
     * treated as opt-in. The runtime fails closed (opt-in) when the browser
     * cannot report a time zone.
     *
     * @since 1.10.0
     * @return array{prefixes: string[], zones: string[]}
     */
    public static function optin_timezones() {
        $timezones = array(
            'prefixes' => array('Europe/'),
            'zones'    => array(
                'Atlantic/Reykjavik',
                'Atlantic/Canary',
                'Atlantic/Madeira',
                'Atlantic/Azores',
                'Atlantic/Faroe',
                'Asia/Nicosia',
                'Asia/Famagusta',
                'Africa/Ceuta',
            ),
        );

        /**
         * Filter the time-zone heuristic used for the regional banner decision.
         *
         * @since 1.10.0
         * @param array{prefixes: string[], zones: string[]} $timezones
         */
        $filtered = apply_filters('mdcc_optin_timezones', $timezones);

        if (!is_array($filtered)) {
            return $timezones;
        }

        return array(
            'prefixes' => array_values(array_map('strval', (array) ($filtered['prefixes'] ?? array()))),
            'zones'    => array_values(array_map('strval', (array) ($filtered['zones'] ?? array()))),
        );
    }

    /**
     * Browser time-zone heuristic for the opt-out-notice tier under the
     * 'regional' model: California.
     *
     * CCPA/CPRA is an opt-out law (tracking is permitted by default; the site
     * must offer a "Do Not Sell or Share" control and honor Global Privacy
     * Control), so Californians get implied consent plus a dismissible opt-out
     * notice rather than the EEA opt-in popup. California cannot be isolated
     * client-side: America/Los_Angeles is the whole Pacific zone, so WA, OR and
     * part of NV also see the notice. Tracking stays on for them by default,
     * so the only cost is a dismissible notice. Presentation heuristic only;
     * no GCM region default is emitted for this tier (it is implied consent).
     *
     * @since 1.10.0
     * @return array{prefixes: string[], zones: string[]}
     */
    public static function optout_timezones() {
        $timezones = array(
            'prefixes' => array(),
            'zones'    => array('America/Los_Angeles'),
        );

        /**
         * Filter the time-zone heuristic for the opt-out-notice (California) tier.
         *
         * @since 1.10.0
         * @param array{prefixes: string[], zones: string[]} $timezones
         */
        $filtered = apply_filters('mdcc_optout_timezones', $timezones);

        if (!is_array($filtered)) {
            return $timezones;
        }

        return array(
            'prefixes' => array_values(array_map('strval', (array) ($filtered['prefixes'] ?? array()))),
            'zones'    => array_values(array_map('strval', (array) ($filtered['zones'] ?? array()))),
        );
    }

    /**
     * Inject Google Consent Mode v2 default state in <head>
     *
     * Emits one or more gtag('consent','default', ...) commands BEFORE any
     * tracking scripts load, so GTM, GA4 and Google Ads respect consent from
     * the very first pageview. Which commands depends on the consent model:
     *
     * - optin:    one denied default (the pre-1.10.0 output, unchanged).
     * - regional: a granted default for everyone, followed by a denied default
     *             scoped with `region` to optin_regions(). Google resolves the
     *             visitor's region itself and the more specific command wins,
     *             so no geolocation happens on this server (pages stay cacheable).
     * - optout:   one granted default.
     *
     * Only injects if the 'gcm_inject_default' setting is enabled (default: true).
     * Priority 1 on wp_head ensures this runs before tracking scripts.
     * wait_for_update gives consent-runtime.js 500ms to load and call 'update'.
     *
     * @since 1.7.1
     * @since 1.10.0 Region-aware: emits per-model default commands.
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

        $model = self::get_consent_model();

        $denied = array(
            'analytics_storage'       => 'denied',
            'ad_storage'              => 'denied',
            'ad_user_data'            => 'denied',
            'ad_personalization'      => 'denied',
            'security_storage'        => 'granted',
            'functionality_storage'   => 'granted',
            'personalization_storage' => 'denied',
            'wait_for_update'         => 500,
        );

        $granted = array(
            'analytics_storage'       => 'granted',
            'ad_storage'              => 'granted',
            'ad_user_data'            => 'granted',
            'ad_personalization'      => 'granted',
            'security_storage'        => 'granted',
            'functionality_storage'   => 'granted',
            'personalization_storage' => 'denied',
            'wait_for_update'         => 500,
        );

        // Ordered list of [state, region] pairs. The global (region-less)
        // command must come first; GCM applies the most specific region.
        if (self::MODEL_REGIONAL === $model) {
            $commands = array(
                array($granted, null),
                array($denied, self::optin_regions()),
            );
        } elseif (self::MODEL_OPTOUT === $model) {
            $commands = array(array($granted, null));
        } else {
            $commands = array(array($denied, null));
        }

        $js = "window.dataLayer = window.dataLayer || [];\n"
            . "function gtag(){dataLayer.push(arguments);}\n";

        foreach ($commands as $command) {
            list($default_state, $region) = $command;

            /**
             * Filter a Google Consent Mode v2 default state injected in <head>.
             *
             * Runs once per emitted gtag('consent','default') command. Under the
             * 'optin' and 'optout' models that is a single command with
             * `$region === null`. Under the 'regional' model it runs twice: first
             * for the global granted default (`$region === null`), then for the
             * denied default scoped to the opt-in regions (`$region` is the
             * string[] of ISO codes from optin_regions()). Callbacks MUST inspect
             * `$region` and return `$default_state` untouched for commands they
             * do not intend to change; a callback written for 1.9.0 that ignores
             * the second argument will override both commands.
             *
             * Return an associative array of GCM signals ('granted'/'denied');
             * keep the int 'wait_for_update' (ms). Do not add a 'region' key;
             * it is applied after filtering. Runs synchronously on wp_head at
             * priority 1 before any tracking; do NOT perform async work here.
             *
             * @since 1.9.0
             * @since 1.10.0 `$region` is string[] for region-scoped commands.
             * @param array                $default_state GCM default signals.
             * @param string|string[]|null $region        null for the global default;
             *                                            string[] of ISO region codes for
             *                                            a region-scoped default.
             */
            $default_state = (array) apply_filters('mdcc_gcm_default_state', $default_state, $region);
            unset($default_state['region']);

            if (is_array($region) && !empty($region)) {
                $default_state['region'] = $region;
            }

            $js .= 'gtag(\'consent\', \'default\', ' . wp_json_encode($default_state) . ');' . "\n";
        }

        // wp_print_inline_script_tag (WP 5.7+) composes with CSP nonce plugins
        // via the wp_inline_script_attributes filter.
        wp_print_inline_script_tag(rtrim($js, "\n"));
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

        /**
         * Filter the script dependencies of the consent runtime.
         *
         * Lets add-on features (e.g. the WP Consent API bridge) declare a load
         * order without this class needing to know about them. The bridge adds
         * the 'wp-consent-api' handle here — but only after verifying it is
         * actually registered — so wp_set_consent() is guaranteed available by
         * the time the runtime initializes.
         *
         * @since 1.8.0
         * @param string[] $deps Registered script handles the runtime depends on.
         */
        $deps = (array) apply_filters('mdcc_consent_runtime_deps', array());

        wp_enqueue_script(
            'mdcc-consent-runtime',
            MDCC_PLUGIN_URL . 'assets/js/consent-runtime' . $suffix . '.js',
            $deps,
            MDCC_VERSION,
            true
        );

        $model = self::get_consent_model();

        $config = array(
            'storageKey' => self::STORAGE_KEY,
            'debug'      => defined('WP_DEBUG') && WP_DEBUG,

            /**
             * Consent model the runtime applies: 'optin' | 'regional' | 'optout'.
             * The banner/implied-consent decision is made client-side so
             * full-page-cached HTML stays correct for every visitor.
             *
             * @since 1.10.0
             */
            'consentModel' => $model,

            /**
             * Whether to mirror consent choices into the WP Consent API.
             *
             * The bridge sets this true only when its admin toggle is on and
             * the WP Consent API plugin is active; the runtime additionally
             * guards on `typeof wp_set_consent === 'function'` before calling.
             *
             * @since 1.8.0
             */
            'consentApi' => (bool) apply_filters('mdcc_consent_api_enabled', false),

            /**
             * Filter the consent categories exposed to the runtime + UI.
             *
             * The stored consent axes stay analytics/ads (they map 1:1 to
             * GCM v2 and the WP Consent API); 'functional' is always granted.
             * Add-ons use this to label/describe categories. Each entry:
             * id => ['label' => string, 'required' => bool].
             *
             * @since 1.9.0
             * @param array $categories
             */
            'categories' => apply_filters('mdcc_consent_categories', array(
                'functional' => array('label' => __('Functional', 'maxtdesign-cookie-consent'), 'required' => true),
                'analytics'  => array('label' => __('Analytics', 'maxtdesign-cookie-consent'), 'required' => false),
                'ads'        => array('label' => __('Advertising', 'maxtdesign-cookie-consent'), 'required' => false),
            )),

            /**
             * Filter server-declared, consent-gated tracking services.
             *
             * Data channel for add-ons (Pro) to declare services the runtime
             * should gate. Each entry: id => ['label' => string, 'category' =>
             * 'analytics'|'ads'|'functional', ...]. The load behavior is JS —
             * consumers call window.mdccConsent.registerService(id, {...}).
             *
             * @since 1.9.0
             * @param array $services
             */
            'trackingServices' => apply_filters('mdcc_tracking_services', array()),
        );

        // Time-zone heuristics for the regional banner decision: opt-in zones
        // (EEA/UK/CH) and opt-out-notice zones (California / Pacific). Only
        // emitted under the 'regional' model; the runtime fails closed (opt-in)
        // when the opt-in list is absent. See optin_timezones() and
        // optout_timezones() for the filters.
        if (self::MODEL_REGIONAL === $model) {
            $config['optinTimezones']  = self::optin_timezones();
            $config['optoutTimezones'] = self::optout_timezones();
        }

        wp_localize_script('mdcc-consent-runtime', 'mdccConfig', $config);
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

