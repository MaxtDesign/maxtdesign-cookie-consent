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

        // Keep the saved address of the cookie settings page current. These
        // run when a page or the site address changes, never on a page view.
        add_action('post_updated', array($this, 'refresh_manage_url_for_post'));
        // Not deleted_post: that fires while the deleted page is still cached.
        add_action('after_delete_post', array($this, 'refresh_manage_url_for_post'));
        add_action('transition_post_status', array($this, 'refresh_manage_url_on_status'), 10, 3);
        // Not update_option_permalink_structure: that fires before WordPress
        // has loaded the new structure, so the old address would be stored.
        add_action('permalink_structure_changed', array($this, 'refresh_manage_url'), 10, 0);
        add_action('update_option_home', array($this, 'refresh_manage_url'), 10, 0);
    }

    /**
     * Button layouts the design settings offer.
     *
     * @since 1.11.0
     * @return string[]
     */
    public static function button_layouts() {
        return array('standard', 'compact');
    }

    /**
     * Address of a published page, stored relative to the home URL.
     *
     * Stored relative so the link survives a move between domains, such as
     * staging to production. Resolved when settings are saved and when the
     * page changes, so rendering the popup needs no database query.
     *
     * @since 1.11.0
     * @param mixed $page_id Page ID.
     * @return string Path starting with '/', a full URL when the page lives
     *                outside the home URL, or '' when the page is not a
     *                published page.
     */
    public static function resolve_manage_url($page_id) {
        $page_id = absint($page_id);

        if (0 === $page_id || 'page' !== get_post_type($page_id) || 'publish' !== get_post_status($page_id)) {
            return '';
        }

        $url = get_permalink($page_id);

        if (!is_string($url) || '' === $url) {
            return '';
        }

        $home = untrailingslashit(home_url());

        if ('' !== $home && 0 === strpos($url, $home . '/')) {
            return substr($url, strlen($home));
        }

        return esc_url_raw($url);
    }

    /**
     * Full address of the cookie settings page, or '' when there is none.
     *
     * @since 1.11.0
     * @param array<string, mixed> $settings Plugin settings.
     * @return string
     */
    public static function get_manage_url($settings) {
        $saved = isset($settings['manage_url']) && is_string($settings['manage_url']) ? $settings['manage_url'] : '';
        $url   = '';

        if ('' !== $saved && '/' === $saved[0] && '/' !== substr($saved, 1, 1)) {
            $url = home_url($saved);
        } elseif ('' !== $saved) {
            $url = $saved;
        }

        /**
         * Filter the address of the cookie settings page.
         *
         * For multilingual sites that serve a translated page. Return '' to
         * make the popup fall back to the standard three buttons.
         *
         * @since 1.11.0
         * @param string               $url      Full URL, or '' when no page is set.
         * @param array<string, mixed> $settings Plugin settings.
         */
        $url = apply_filters('mdcc_manage_url', $url, $settings);

        // Anything that is not a plain web address counts as "no page", and
        // the popup falls back to the standard buttons.
        return is_string($url) && 1 === preg_match('#^https?://[^\s"\'<>]+\z#i', $url) ? $url : '';
    }

    /**
     * The button layout the popup renders with.
     *
     * Compact needs a cookie settings page. Without one the popup renders
     * Standard, so a visitor is never left without choices.
     *
     * @since 1.11.0
     * @param array<string, mixed> $settings Plugin settings.
     * @return string 'standard' or 'compact'.
     */
    public static function get_button_layout($settings) {
        $wanted = isset($settings['popup_buttons']) ? $settings['popup_buttons'] : 'standard';

        return 'compact' === $wanted && '' !== self::get_manage_url($settings) ? 'compact' : 'standard';
    }

    /**
     * Re-resolve the saved address of the cookie settings page.
     *
     * @since 1.11.0
     * @return void
     */
    public function refresh_manage_url() {
        $settings = get_option('mdcc_settings');

        if (!is_array($settings) || empty($settings['manage_page_id'])) {
            return;
        }

        $url = self::resolve_manage_url($settings['manage_page_id']);

        if (isset($settings['manage_url']) && $url === $settings['manage_url']) {
            return;
        }

        $settings['manage_url'] = $url;
        update_option('mdcc_settings', $settings);
    }

    /**
     * Re-resolve the address when the cookie settings page itself changed.
     *
     * @since 1.11.0
     * @param mixed $post_id ID of the post that changed.
     * @return void
     */
    public function refresh_manage_url_for_post($post_id) {
        $settings = get_option('mdcc_settings');

        if (is_array($settings) && !empty($settings['manage_page_id']) && absint($post_id) === absint($settings['manage_page_id'])) {
            $this->refresh_manage_url();
        }
    }

    /**
     * Re-resolve the address when a post is published, unpublished or trashed.
     *
     * @since 1.11.0
     * @param mixed $new_status New post status.
     * @param mixed $old_status Old post status.
     * @param mixed $post       Post object.
     * @return void
     */
    public function refresh_manage_url_on_status($new_status, $old_status, $post) {
        if ($new_status !== $old_status && is_object($post) && isset($post->ID)) {
            $this->refresh_manage_url_for_post($post->ID);
        }
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

        // Compact: the cookie settings page holds the consent controls
        // itself. A visitor who followed "Manage options" must not get the
        // popup on top of them. Decided by the page, not by the visitor, so
        // it is safe under a page cache. Sites that change the address with
        // mdcc_manage_url use mdcc_should_show_popup for their own page.
        if (is_array($settings) && !empty($settings['manage_page_id'])
            && 'compact' === self::get_button_layout($settings)
            && is_page(absint($settings['manage_page_id']))) {
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
        // The handle is registered either way, so a theme or add-on can still
        // attach its own inline style to it. Without one it prints nothing.
        wp_register_style('mdcc-popup', false, array(), MDCC_VERSION, 'all');
        wp_enqueue_style('mdcc-popup');

        $design_css = self::get_design_css($settings);

        if ('' !== $design_css) {
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

        $radius = isset($settings['popup_radius']) && is_scalar($settings['popup_radius']) ? trim((string) $settings['popup_radius']) : '';
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

        // The Compact layout's link sits in the button row. A link is sized
        // and aligned differently from a button until told otherwise. Printed
        // here, so sites on the standard layout carry no bytes for it.
        if ('compact' === self::get_button_layout($settings)) {
            $css .= '.mdcc-popup .mdcc-popup__manage{box-sizing:border-box;text-align:center}';
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
        $color = is_string($value) ? sanitize_hex_color(trim($value)) : '';

        // Checked again with \z: the pattern in sanitize_hex_color() ends in
        // $, which lets one trailing line break through.
        if (!is_string($color) || 1 !== preg_match('/^#(?:[0-9a-f]{3}){1,2}\z/i', $color)) {
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

        $classes = array(
            'mdcc-popup',
            'mdcc-popup--style-' . $style,
            'mdcc-popup--position-' . $position,
            'mdcc-popup--animation-' . $animation,
        );

        // The address is resolved once here, and the layout follows from it,
        // so Compact can never render with an empty link.
        $wanted     = isset($settings['popup_buttons']) ? $settings['popup_buttons'] : 'standard';
        $manage_url = 'compact' === $wanted ? self::get_manage_url($settings) : '';
        $buttons    = '' !== $manage_url ? 'compact' : 'standard';

        if ('compact' === $buttons) {
            $classes[] = 'mdcc-popup--compact';
        }

        $labels = array(
            'close'          => __('Close consent popup', 'maxtdesign-cookie-consent'),
            'accept'         => __('Accept All', 'maxtdesign-cookie-consent'),
            'accept_aria'    => __('Accept all cookies', 'maxtdesign-cookie-consent'),
            'analytics'      => __('Analytics Only', 'maxtdesign-cookie-consent'),
            'analytics_aria' => __('Accept analytics cookies only', 'maxtdesign-cookie-consent'),
            'decline'        => __('Decline All', 'maxtdesign-cookie-consent'),
            'decline_aria'   => __('Decline all cookies', 'maxtdesign-cookie-consent'),
            'manage'         => __('Manage options', 'maxtdesign-cookie-consent'),
        );

        // A label the site owner typed replaces the default. Its accessible
        // name follows it, so what is read out matches what is shown.
        foreach (array('accept', 'analytics', 'decline', 'manage') as $key) {
            $custom = isset($settings['label_' . $key]) && is_string($settings['label_' . $key]) ? trim($settings['label_' . $key]) : '';

            if ('' !== $custom) {
                $labels[$key] = $custom;

                if (isset($labels[$key . '_aria'])) {
                    $labels[$key . '_aria'] = $custom;
                }
            }
        }

        return array(
            'classes'     => $classes,
            'title'       => (string) $title,
            'message'     => (string) $message,
            'privacy_url' => function_exists('get_privacy_policy_url') ? (string) get_privacy_policy_url() : '',
            'buttons'     => $buttons,
            'manage_url'  => $manage_url,
            'labels'      => $labels,
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

