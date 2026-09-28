<?php
/**
 * Popup System
 *
 * Renders and manages the standalone consent popup with multiple style presets,
 * position options, and full accessibility support. Cookie-based "shown" tracking.
 *
 * @package MaxtDesign_Cookie_Consent
 * @since 1.6.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class MDCC_Popup_System {
    /**
     * Single instance
     *
     * @var MDCC_Popup_System
     */
    private static $instance = null;

    /**
     * Cookie name for tracking popup shown state
     *
     * @var string
     */
    const SHOWN_COOKIE = 'mdcc_popup_shown';

    /**
     * Get instance
     *
     * @return MDCC_Popup_System
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
        // Enqueue popup assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_popup_assets'));

        // Render popup in footer
        add_action('wp_footer', array($this, 'render_popup'));
    }

    /**
     * Check if popup should be shown
     *
     * Don't show popup if:
     * - User is in admin
     * - Elementor popup ID is set (use Elementor instead)
     * - Cookie indicates popup already shown (and not expired)
     * - User has already made a consent choice
     *
     * The final decision is passed through the `mdcc_should_show_popup` filter
     * so site developers can selectively suppress the popup (per page, post
     * type, user role, etc.) without disabling it globally. The filter only
     * runs after every built-in gate has passed — it can turn a "would show"
     * into "don't show", but it intentionally cannot force the popup back on in
     * admin, when disabled, or once already shown.
     *
     * @since 1.6.0
     * @return bool True if popup should display
     */
    private function should_show_popup() {
        // Don't show in admin
        if (is_admin()) {
            return false;
        }

        // Get settings
        $settings = get_option('mdcc_settings', mdcc_default_settings());

        // Don't show if popup disabled in settings
        if (empty($settings['popup_enabled'])) {
            return false;
        }

        // Don't show if Elementor popup ID is set (use Elementor instead)
        if (!empty($settings['elementor_popup_id'])) {
            return false;
        }

        // Don't show if popup shown cookie exists and not expired
        // Visitor cookies are checked in the browser; PHP output is shared by
        // full-page caches and must never omit the popup for later visitors.

        /**
         * Filter whether the consent popup should display on this request.
         *
         * Fires only after all built-in gates pass (not in admin, popup
         * enabled, no Elementor override, no "already shown" cookie). Return
         * false to suppress the popup on specific pages or conditions without
         * disabling it sitewide.
         *
         * Example — hide the popup on the contact page:
         *
         *     add_filter('mdcc_should_show_popup', function ($show) {
         *         return is_page('contact') ? false : $show;
         *     });
         *
         * @since 1.7.7
         * @param bool $should_show True when the popup would otherwise display.
         */
        return (bool) apply_filters('mdcc_should_show_popup', true);
    }

    /**
     * Enqueue popup CSS and JavaScript
     *
     * Only enqueues if popup should be shown.
     * Loads minified version by default, source version when SCRIPT_DEBUG is enabled.
     *
     * @since 1.6.0
     */
    public function enqueue_popup_assets() {
        if (!$this->should_show_popup()) {
            return;
        }

        $suffix = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';

        $settings = get_option('mdcc_settings', mdcc_default_settings());

        if (!is_array($settings)) {
            $settings = mdcc_default_settings();
        }

        // Design settings print as CSS variables, and only the ones that
        // differ from the defaults. A site that changed nothing prints no
        // inline style at all.
        $design_css = self::get_design_css($settings);

        if ('' !== $design_css) {
            wp_register_style('mdcc-popup', false, array(), MDCC_VERSION, 'all');
            wp_enqueue_style('mdcc-popup');
            wp_add_inline_style('mdcc-popup', $design_css);
        }

        // Enqueue popup behavior script (inline for minimal size)
        wp_register_script('mdcc-popup-behavior', false, array('mdcc-consent-runtime'), MDCC_VERSION, true);
        wp_enqueue_script('mdcc-popup-behavior');

        $popup_config = array(
            'cookieName'      => self::SHOWN_COOKIE,
            'cookieDuration'  => absint($settings['popup_shown_duration']), // Days
            'repromptDecline' => !empty($settings['reprompt_on_decline']),
            'scriptUrl'      => MDCC_PLUGIN_URL . 'assets/js/popup' . $suffix . '.js?ver=' . MDCC_VERSION,
            'styleUrl'       => MDCC_PLUGIN_URL . 'assets/css/popup' . $suffix . '.css?ver=' . MDCC_VERSION,
        );

        // Opt-out-notice presentation (California under 'regional'; everyone
        // under 'optout'). The markup is server-rendered (and cached) with the
        // opt-in copy; popup.js swaps in these strings client-side when
        // mdccConsent.bannerMode() === 'optout'. Only emitted when the model can
        // produce such a visitor, so opt-in installs ship identical config. The
        // decline label is the CCPA/CPRA-prescribed control wording.
        if (MDCC_Consent_Manager::MODEL_OPTIN !== MDCC_Consent_Manager::get_consent_model()) {
            $defaults = mdcc_default_settings();

            $popup_config['optoutTitle']        = !empty($settings['popup_title_optout']) ? (string) $settings['popup_title_optout'] : (string) $defaults['popup_title_optout'];
            $popup_config['optoutMessage']      = !empty($settings['popup_message_optout']) ? (string) $settings['popup_message_optout'] : (string) $defaults['popup_message_optout'];
            $popup_config['optoutAcceptLabel']  = __('Got it', 'maxtdesign-cookie-consent');
            $popup_config['optoutDeclineLabel'] = __('Do Not Sell or Share My Personal Information', 'maxtdesign-cookie-consent');
        }

        // Pass settings to JavaScript
        wp_localize_script('mdcc-popup-behavior', 'mdccPopupConfig', $popup_config);

        // Add inline popup behavior script
        $popup_js = $this->get_popup_javascript();
        wp_add_inline_script('mdcc-popup-behavior', $popup_js);
    }

    /**
     * Desktop widths the design settings offer, in percent.
     *
     * @since 1.11.0
     * @return int[]
     */
    public static function desktop_widths() {
        return array(100, 80, 60, 50);
    }

    /**
     * The design settings as CSS variables.
     *
     * Holds only the settings that differ from the defaults, so a site that
     * changed nothing gets an empty array. Every value is validated again
     * here, whatever was saved.
     *
     * @since 1.11.0
     * @param array<string, mixed> $settings Plugin settings.
     * @return array<string, string> Variable name => value.
     */
    public static function get_design_variables($settings) {
        $vars = array();

        $primary = self::hex_color($settings['popup_primary_color'] ?? '');
        if ('' !== $primary && '#0073aa' !== $primary) {
            $vars['--mdcc-primary'] = $primary;
            $vars['--mdcc-hover']   = $primary . 'dd';
        }

        $colors = array(
            'popup_button_text_color' => '--mdcc-btn-fg',
            'popup_bg_color'          => '--mdcc-bg',
            'popup_text_color'        => '--mdcc-fg',
        );
        foreach ($colors as $key => $name) {
            $color = self::hex_color($settings[$key] ?? '');
            if ('' !== $color) {
                $vars[$name] = $color;
            }
        }

        $radius = $settings['popup_radius'] ?? '';
        if (is_numeric($radius)) {
            $radius          = min(24, absint($radius));
            $vars['--mdcc-r'] = 0 === $radius ? '0' : $radius . 'px';
        }

        $width = absint($settings['popup_desktop_width'] ?? 100);
        if (100 !== $width && in_array($width, self::desktop_widths(), true)) {
            $vars['--mdcc-w'] = $width . '%';
        }

        return $vars;
    }

    /**
     * The inline style that carries the design settings, or '' for none.
     *
     * @since 1.11.0
     * @param array<string, mixed> $settings Plugin settings.
     * @return string
     */
    public static function get_design_css($settings) {
        $css = '';

        foreach (self::get_design_variables($settings) as $name => $value) {
            $css .= $name . ':' . $value . ';';
        }

        if ('' !== $css) {
            $css = '.mdcc-popup{' . rtrim($css, ';') . '}';
        }

        // Not a variable: a custom property set to "inherit" inherits itself
        // instead of carrying the keyword. Two classes outrank the stylesheet
        // wherever this block sits in the document.
        if (!empty($settings['popup_inherit_font'])) {
            $css .= '.mdcc-popup .mdcc-popup__button{font-family:inherit}';
        }

        return $css;
    }

    /**
     * A six digit lowercase hex colour, or '' when the value is not one.
     *
     * Three digit colours are expanded, so an alpha suffix can be appended.
     *
     * @since 1.11.0
     * @param mixed $value Saved colour.
     * @return string
     */
    private static function hex_color($value) {
        $color = is_string($value) ? sanitize_hex_color($value) : '';

        if (!is_string($color) || '' === $color) {
            return '';
        }

        $color = strtolower($color);

        if (4 === strlen($color)) {
            $color = '#' . $color[1] . $color[1] . $color[2] . $color[2] . $color[3] . $color[3];
        }

        return $color;
    }

    /**
     * Get popup behavior JavaScript
     *
     * Returns the under-1KB inline presentation loader. It waits for consent
     * readiness and only fetches popup CSS/JS for visitors who need a dialog.
     * Both loader builds are generated by npm run build:popup-js.
     *
     * @since 1.6.0
     * @return string JavaScript code (empty string if the asset is missing)
     */
    private function get_popup_javascript() {
        $suffix = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';
        $path   = MDCC_PLUGIN_DIR . 'assets/js/popup-loader' . $suffix . '.js';

        if (!is_readable($path)) {
            return '';
        }

        // Reading a bundled plugin asset off local disk to inline it; this is a
        // file read, not a remote fetch, so WP_Filesystem is unnecessary here.
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        $js = file_get_contents($path);

        return false === $js ? '' : $js;
    }

    /**
     * Path of the theme override, relative to the theme folder.
     *
     * @since 1.11.0
     * @var string
     */
    const TEMPLATE_NAME = 'maxtdesign-cookie-consent/popup.php';

    /**
     * Full path of the popup template bundled with the plugin.
     *
     * @since 1.11.0
     * @return string
     */
    public static function bundled_template() {
        return MDCC_PLUGIN_DIR . 'templates/popup.php';
    }

    /**
     * Full path of the theme's popup template, or '' when the theme has none.
     *
     * locate_template() checks the child theme before the parent theme.
     *
     * @since 1.11.0
     * @return string
     */
    public static function theme_template() {
        return (string) locate_template(array(self::TEMPLATE_NAME));
    }

    /**
     * Full path of the popup template to render.
     *
     * Order: the theme override, then the bundled template. The result passes
     * through the `mdcc_popup_template` filter. A filtered path that cannot be
     * read is ignored, so a wrong path never removes the popup.
     *
     * @since 1.11.0
     * @return string
     */
    public static function locate_popup_template() {
        $template = self::theme_template();

        if ('' === $template) {
            $template = self::bundled_template();
        }

        /**
         * Filter the path of the popup template.
         *
         * For sites that keep templates outside the theme folder. The file
         * must keep the contract documented at the top of the bundled
         * templates/popup.php.
         *
         * @since 1.11.0
         * @param string $template Full path of the template about to be used.
         */
        $filtered = apply_filters('mdcc_popup_template', $template);

        if (is_string($filtered) && '' !== $filtered && is_readable($filtered)) {
            return $filtered;
        }

        return $template;
    }

    /**
     * Read the `@version` tag from the header of a popup template.
     *
     * @since 1.11.0
     * @param string $path Full path of a template file.
     * @return string The version, or '' when the file has none or cannot be read.
     */
    public static function template_version($path) {
        if (!is_readable($path)) {
            return '';
        }

        // Reading the header of a local template file, not a remote fetch.
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        $head = file_get_contents($path, false, null, 0, 8192);

        if (false === $head || !preg_match('/^[ \t\/*#]*@version[ \t]+([0-9][0-9A-Za-z.\-]*)/m', $head, $matches)) {
            return '';
        }

        return $matches[1];
    }

    /**
     * Build the data handed to the popup template.
     *
     * @since 1.11.0
     * @param array<string, mixed> $settings Plugin settings.
     * @return array<string, mixed>
     */
    private function get_template_args($settings) {
        // Get settings values with defaults
        $style     = sanitize_text_field($settings['popup_style'] ?? 'minimal');
        $position  = sanitize_text_field($settings['popup_position'] ?? 'bottom');
        $animation = sanitize_text_field($settings['popup_animation'] ?? 'slide');
        $title     = !empty($settings['popup_title']) ? $settings['popup_title'] : __('Cookie Consent', 'maxtdesign-cookie-consent');
        $message   = !empty($settings['popup_message']) ? $settings['popup_message'] : __('We use cookies to enhance your browsing experience and analyze our traffic.', 'maxtdesign-cookie-consent');

        return array(
            'classes'     => array(
                'mdcc-popup',
                'mdcc-popup--style-' . $style,
                'mdcc-popup--position-' . $position,
                'mdcc-popup--animation-' . $animation,
            ),
            'title'       => (string) $title,
            'message'     => (string) $message,
            'privacy_url' => function_exists('get_privacy_policy_url') ? (string) get_privacy_policy_url() : '',
            'labels'      => array(
                'close'          => __('Close consent popup', 'maxtdesign-cookie-consent'),
                'accept'         => __('Accept All', 'maxtdesign-cookie-consent'),
                'accept_aria'    => __('Accept all cookies', 'maxtdesign-cookie-consent'),
                'analytics'      => __('Analytics Only', 'maxtdesign-cookie-consent'),
                'analytics_aria' => __('Accept analytics cookies only', 'maxtdesign-cookie-consent'),
                'decline'        => __('Decline All', 'maxtdesign-cookie-consent'),
                'decline_aria'   => __('Decline all cookies', 'maxtdesign-cookie-consent'),
            ),
            'settings'    => $settings,
        );
    }

    /**
     * Render popup HTML in footer
     *
     * Prints templates/popup.php, or the theme's override of it. The markup
     * carries ARIA attributes, keyboard navigation support and the responsive
     * layout.
     *
     * @since 1.6.0
     */
    public function render_popup() {
        if (!$this->should_show_popup()) {
            return;
        }

        $settings = get_option('mdcc_settings', mdcc_default_settings());

        if (!is_array($settings)) {
            $settings = mdcc_default_settings();
        }

        load_template(self::locate_popup_template(), false, $this->get_template_args($settings));
    }
}

