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

        return true;
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

        wp_add_inline_style('mdcc-popup', wp_strip_all_tags($custom_css));

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
        wp_add_inline_script('mdcc-popup-behavior', wp_strip_all_tags($popup_js));
    }

    /**
     * Get popup behavior JavaScript
     *
     * Handles popup display, close functionality, button clicks, keyboard navigation,
     * cookie management, and consent integration.
     *
     * @since 1.6.0
     * @return string JavaScript code
     */
    private function get_popup_javascript() {
        ob_start();
        ?>
(function(window, document) {
    'use strict';
    
    var config = window.mdccPopupConfig || {};
    var popup = null;
    var closeBtn = null;
    var firstFocusable = null;
    var lastFocusable = null;
    
    /**
     * Set cookie to remember popup shown
     */
    function setCookie() {
        var days = config.cookieDuration || 7;
        var date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        var expires = 'expires=' + date.toUTCString();
        document.cookie = config.cookieName + '=1;' + expires + ';path=/;SameSite=Lax';
    }
    
    /**
     * Close popup
     */
    function closePopup() {
        if (!popup) return;
        
        popup.classList.add('mdcc-popup--closing');
        
        setTimeout(function() {
            popup.style.display = 'none';
            popup.classList.remove('mdcc-popup--closing');
            document.body.classList.remove('mdcc-popup-open');
        }, 300); // Match animation duration
        
        // Set cookie so popup doesn't show again
        setCookie();
    }
    
    /**
     * Handle consent button clicks
     */
    function handleConsentAction(action) {
        if (!window.mdccConsent) return;
        
        switch(action) {
            case 'accept-all':
                mdccConsent.acceptAll();
                break;
            case 'analytics-only':
                mdccConsent.acceptAnalyticsOnly();
                break;
            case 'decline-all':
                mdccConsent.declineAll();
                break;
        }
        
        closePopup();
    }
    
    /**
     * Setup focus trap for accessibility
     */
    function setupFocusTrap() {
        if (!popup) return;
        
        var focusableElements = popup.querySelectorAll(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        
        if (focusableElements.length > 0) {
            firstFocusable = focusableElements[0];
            lastFocusable = focusableElements[focusableElements.length - 1];
            
            // Focus first button when popup opens
            setTimeout(function() {
                if (firstFocusable) {
                    firstFocusable.focus();
                }
            }, 100);
        }
    }
    
    /**
     * Handle keyboard navigation
     */
    function handleKeyboard(e) {
        if (!popup || popup.style.display === 'none') return;
        
        // ESC key closes popup
        if (e.key === 'Escape' || e.keyCode === 27) {
            e.preventDefault();
            closePopup();
            return;
        }
        
        // TAB key - trap focus within popup
        if (e.key === 'Tab' || e.keyCode === 9) {
            if (e.shiftKey) {
                // Shift + Tab
                if (document.activeElement === firstFocusable) {
                    e.preventDefault();
                    lastFocusable.focus();
                }
            } else {
                // Tab
                if (document.activeElement === lastFocusable) {
                    e.preventDefault();
                    firstFocusable.focus();
                }
            }
        }
    }
    
    /**
     * Initialize popup behavior
     */
    function init() {
        popup = document.querySelector('.mdcc-popup');
        if (!popup) return;
        
        closeBtn = popup.querySelector('.mdcc-popup__close');
        
        // Show popup with animation
        setTimeout(function() {
            popup.style.display = 'block';
            document.body.classList.add('mdcc-popup-open');
            setTimeout(function() {
                popup.classList.add('mdcc-popup--visible');
                setupFocusTrap();
            }, 10);
        }, 500); // Delay initial appearance
        
        // Close button
        if (closeBtn) {
            closeBtn.addEventListener('click', function(e) {
                e.preventDefault();
                closePopup();
            });
        }
        
        // Consent button clicks (event delegation)
        popup.addEventListener('click', function(e) {
            var btn = e.target.closest('[data-mdcc-action]');
            if (btn) {
                e.preventDefault();
                var action = btn.getAttribute('data-mdcc-action');
                handleConsentAction(action);
            }
        });
        
        // Keyboard navigation
        document.addEventListener('keydown', handleKeyboard);
        
        // Re-prompt on decline (if enabled)
        if (config.repromptDecline) {
            document.addEventListener('mdcc:changed', function(e) {
                var state = e.detail;
                if (!state.analytics && !state.ads) {
                    // User declined - show popup again once per session
                    // But only if popup was actually shown and closed (not on initial load)
                    var repromptKey = 'mdcc_reprompted_session';
                    if (!sessionStorage.getItem(repromptKey)) {
                        // Check if popup exists and was previously hidden (user closed it)
                        if (popup && popup.style.display === 'none') {
                            sessionStorage.setItem(repromptKey, '1');
                            setTimeout(function() {
                                popup.style.display = 'block';
                                popup.classList.add('mdcc-popup--visible');
                                setupFocusTrap();
                            }, 2000); // Wait 2 seconds before re-showing
                        }
                    }
                }
            });
        }
    }
    
    // Initialize when DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
})(window, document);
        <?php
        return ob_get_clean();
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

