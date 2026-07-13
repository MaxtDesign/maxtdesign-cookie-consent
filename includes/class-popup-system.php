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
        if (isset($_COOKIE[self::SHOWN_COOKIE])) {
            return false;
        }

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

        wp_enqueue_style(
            'mdcc-popup',
            MDCC_PLUGIN_URL . 'assets/css/popup' . $suffix . '.css',
            array(),
            MDCC_VERSION,
            'all'
        );

        // Get settings for dynamic CSS
        $settings = get_option('mdcc_settings', mdcc_default_settings());

        // Add inline CSS for primary color customization
        $primary_color = !empty($settings['popup_primary_color']) ? sanitize_hex_color($settings['popup_primary_color']) : '#0073aa';

        $custom_css = "
            .mdcc-popup__button--primary {
                background-color: {$primary_color};
                border-color: {$primary_color};
            }
            .mdcc-popup__button--primary:hover,
            .mdcc-popup__button--primary:focus {
                background-color: {$primary_color}dd;
                border-color: {$primary_color}dd;
            }
        ";

        wp_add_inline_style('mdcc-popup', $custom_css);

        // Enqueue popup behavior script (inline for minimal size)
        wp_register_script('mdcc-popup-behavior', false, array('mdcc-consent-runtime'), MDCC_VERSION, true);
        wp_enqueue_script('mdcc-popup-behavior');

        // Pass settings to JavaScript
        wp_localize_script(
            'mdcc-popup-behavior',
            'mdccPopupConfig',
            array(
                'cookieName'      => self::SHOWN_COOKIE,
                'cookieDuration'  => absint($settings['popup_shown_duration']), // Days
                'repromptDecline' => !empty($settings['reprompt_on_decline']),
            )
        );

        // Add inline popup behavior script
        $popup_js = $this->get_popup_javascript();
        wp_add_inline_script('mdcc-popup-behavior', $popup_js);
    }

    /**
     * Get popup behavior JavaScript
     *
     * Returns the popup's client-side logic for inlining into the footer.
     * Authored in assets/js/popup.js and minified to popup.min.js by terser
     * (`npm run build:popup-js`); the minified build ships by default, with the
     * readable source served when SCRIPT_DEBUG is enabled. Kept inline rather
     * than enqueued as a URL on purpose — the consent gate is render-critical
     * and show-once, so an extra HTTP request would never benefit from caching.
     *
     * @since 1.6.0
     * @return string JavaScript code (empty string if the asset is missing)
     */
    private function get_popup_javascript() {
        $suffix = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';
        $path   = MDCC_PLUGIN_DIR . 'assets/js/popup' . $suffix . '.js';

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
     * Render popup HTML in footer
     *
     * Outputs the popup markup with proper ARIA attributes,
     * keyboard navigation support, and responsive design.
     *
     * @since 1.6.0
     */
    public function render_popup() {
        if (!$this->should_show_popup()) {
            return;
        }
        
        $settings = get_option('mdcc_settings', mdcc_default_settings());
        
        // Get settings values with defaults
        $style     = sanitize_text_field($settings['popup_style'] ?? 'minimal');
        $position  = sanitize_text_field($settings['popup_position'] ?? 'bottom');
        $animation = sanitize_text_field($settings['popup_animation'] ?? 'slide');
        $title     = !empty($settings['popup_title']) ? $settings['popup_title'] : __('Cookie Consent', 'maxtdesign-cookie-consent');
        $message   = !empty($settings['popup_message']) ? $settings['popup_message'] : __('We use cookies to enhance your browsing experience and analyze our traffic.', 'maxtdesign-cookie-consent');
        
        // Build CSS classes
        $classes = array(
            'mdcc-popup',
            'mdcc-popup--style-' . $style,
            'mdcc-popup--position-' . $position,
            'mdcc-popup--animation-' . $animation,
        );
        
        ?>
        <div class="<?php echo esc_attr(implode(' ', $classes)); ?>" 
             role="dialog" 
             aria-modal="true" 
             aria-labelledby="mdcc-popup-title"
             aria-describedby="mdcc-popup-message"
             style="display: none;">
            
            <div class="mdcc-popup__overlay" aria-hidden="true"></div>
            
            <div class="mdcc-popup__container">
                <div class="mdcc-popup__content">
                    
                    <button type="button" 
                            class="mdcc-popup__close" 
                            aria-label="<?php esc_attr_e('Close consent popup', 'maxtdesign-cookie-consent'); ?>">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    
                    <h2 id="mdcc-popup-title" class="mdcc-popup__title">
                        <?php echo esc_html($title); ?>
                    </h2>
                    
                    <p id="mdcc-popup-message" class="mdcc-popup__message">
                        <?php echo esc_html($message); ?>
                    </p>

                    <?php
                    // Link the site's designated Privacy Policy (Settings → Privacy)
                    // when one is set. A consent notice should point users to the
                    // policy that explains the cookies; renders nothing otherwise.
                    $mdcc_privacy_url = function_exists('get_privacy_policy_url') ? get_privacy_policy_url() : '';
                    if ($mdcc_privacy_url) :
                    ?>
                    <p class="mdcc-popup__privacy-link">
                        <a href="<?php echo esc_url($mdcc_privacy_url); ?>">
                            <?php esc_html_e('Privacy Policy', 'maxtdesign-cookie-consent'); ?>
                        </a>
                    </p>
                    <?php endif; ?>

                    <div class="mdcc-popup__actions">
                        <button type="button" 
                                class="mdcc-popup__button mdcc-popup__button--primary" 
                                data-mdcc-action="accept-all"
                                aria-label="<?php esc_attr_e('Accept all cookies', 'maxtdesign-cookie-consent'); ?>">
                            <?php esc_html_e('Accept All', 'maxtdesign-cookie-consent'); ?>
                        </button>
                        
                        <button type="button" 
                                class="mdcc-popup__button mdcc-popup__button--secondary" 
                                data-mdcc-action="analytics-only"
                                aria-label="<?php esc_attr_e('Accept analytics cookies only', 'maxtdesign-cookie-consent'); ?>">
                            <?php esc_html_e('Analytics Only', 'maxtdesign-cookie-consent'); ?>
                        </button>
                        
                        <button type="button" 
                                class="mdcc-popup__button mdcc-popup__button--tertiary" 
                                data-mdcc-action="decline-all"
                                aria-label="<?php esc_attr_e('Decline all cookies', 'maxtdesign-cookie-consent'); ?>">
                            <?php esc_html_e('Decline All', 'maxtdesign-cookie-consent'); ?>
                        </button>
                    </div>
                    
                </div>
            </div>
        </div>
        <?php
    }
}

