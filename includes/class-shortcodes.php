<?php
/**
 * Shortcodes
 *
 * Registers and renders consent management shortcodes:
 * - [mdlc_consent_status] - Display current consent status chips
 * - [mdlc_manage_consent] - Full consent management interface
 *
 * @package MaxtDesign_Lean_Consent
 * @since 1.6.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class MDLC_Shortcodes {
    /**
     * Single instance
     *
     * @var MDLC_Shortcodes
     */
    private static $instance = null;

    /**
     * Track if assets have been enqueued
     *
     * @var bool
     */
    private $assets_enqueued = false;

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
        // Register shortcodes
        add_shortcode('mdlc_consent_status', array($this, 'render_consent_status'));
        add_shortcode('mdlc_manage_consent', array($this, 'render_manage_consent'));

        // Enqueue assets in footer (after shortcode content renders)
        add_action('wp_footer', array($this, 'enqueue_shortcode_assets'));
    }

    /**
     * Enqueue shortcode CSS and JavaScript
     *
     * Only enqueues if shortcode is actually used on the page.
     * Uses inline styles/scripts to minimize HTTP requests.
     *
     * @since 1.6.0
     */
    public function enqueue_shortcode_assets() {
        if (!$this->assets_enqueued) {
            return;
        }

        // Inline CSS for shortcodes
        wp_register_style('mdlc-shortcodes', false, array(), MDLC_VERSION);
        wp_enqueue_style('mdlc-shortcodes');

        $css = $this->get_shortcode_css();
        wp_add_inline_style('mdlc-shortcodes', $css);

        // Inline JavaScript for real-time updates
        // Depends on consent-runtime.js (already enqueued by consent manager)
        wp_register_script('mdlc-shortcodes', false, array('mdlc-consent-runtime'), MDLC_VERSION, true);
        wp_enqueue_script('mdlc-shortcodes');

        // Localize user-facing strings used in shortcode JavaScript
        wp_localize_script(
            'mdlc-shortcodes',
            'mdlcShortcodeI18n',
            array(
                'confirmReset' => __('Are you sure you want to reset your consent preferences?', 'maxtdesign-lean-consent'),
                'onLabel'      => __('On', 'maxtdesign-lean-consent'),
                'offLabel'     => __('Off', 'maxtdesign-lean-consent'),
            )
        );

        $js = $this->get_shortcode_javascript();
        wp_add_inline_script('mdlc-shortcodes', $js);
    }

    /**
     * Get shortcode CSS
     *
     * Styling for consent status chips and manage consent interface.
     * Target: <2KB for both shortcodes.
     *
     * @since 1.6.0
     * @return string CSS code
     */
    private function get_shortcode_css() {
        ob_start();
        ?>
/* MaxtDesign Lean Consent - Shortcode Styles */

/* Ensure shortcode blocks are centered within viewport when theme containers are absent */
.mdlc-status,
.mdlc-manage {
    box-sizing: border-box;
    width: min(100%, 1200px);
    margin-left: auto;
    margin-right: auto;
}

/* Consent Status Chips */
.mdlc-status {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
}

.mdlc-status__chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border: 1px solid #cbd5e0;
    border-radius: 999px;
    background: #f7fafc;
    font-size: 14px;
    line-height: 1.2;
    font-weight: 500;
    color: #4a5568;
    transition: all 0.2s ease;
}

.mdlc-status__chip--on {
    color: #22543d;
    border-color: #9ae6b4;
    background: #c6f6d5;
}

.mdlc-status__chip--off {
    color: #742a2a;
    border-color: #feb2b2;
    background: #fed7d7;
}

.mdlc-status__label {
    font-weight: 400;
}

.mdlc-status__value {
    font-weight: 600;
}

/* Manage Consent Interface */
.mdlc-manage {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 20px;
    background: #ffffff;
    max-width: 600px;
}

.mdlc-manage__title {
    margin: 0 0 8px 0;
    font-size: 20px;
    font-weight: 600;
    color: #1a202c;
}

.mdlc-manage__description {
    margin: 0 0 16px 0;
    font-size: 15px;
    line-height: 1.5;
    color: #4a5568;
}

.mdlc-manage__actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 16px;
}

.mdlc-manage__button {
    padding: 10px 20px;
    border: 2px solid;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
    display: inline-block;
    text-align: center;
    flex: 1 1 auto;
    min-width: 120px;
}

.mdlc-manage__button:focus {
    outline: 2px solid #4299e1;
    outline-offset: 2px;
}

.mdlc-manage__button--primary {
    background-color: #3182ce;
    border-color: #3182ce;
    color: #ffffff;
}

.mdlc-manage__button--primary:hover,
.mdlc-manage__button--primary:focus {
    background-color: #2c5282;
    border-color: #2c5282;
    color: #ffffff;
}

.mdlc-manage__button--secondary {
    background-color: #ffffff;
    border-color: #cbd5e0;
    color: #2d3748;
}

.mdlc-manage__button--secondary:hover,
.mdlc-manage__button--secondary:focus {
    background-color: #f7fafc;
    border-color: #a0aec0;
    color: #1a202c;
}

.mdlc-manage__button--tertiary {
    background-color: transparent;
    border-color: transparent;
    color: #718096;
    text-decoration: underline;
    min-width: auto;
}

.mdlc-manage__button--tertiary:hover,
.mdlc-manage__button--tertiary:focus {
    color: #2d3748;
    background-color: transparent;
}

.mdlc-manage__current {
    padding-top: 16px;
    border-top: 1px solid #e2e8f0;
}

.mdlc-manage__current-label {
    display: block;
    margin-bottom: 8px;
    font-size: 13px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #718096;
}

/* Responsive */
@media (max-width: 640px) {
    .mdlc-manage {
        padding: 16px;
    }
    
    .mdlc-manage__title {
        font-size: 18px;
    }
    
    .mdlc-manage__description {
        font-size: 14px;
    }
    
    .mdlc-manage__actions {
        flex-direction: column;
    }
    
    .mdlc-manage__button {
        width: 100%;
        min-width: 0;
    }
}

/* Accessibility */
@media (prefers-reduced-motion: reduce) {
    .mdlc-status__chip,
    .mdlc-manage__button {
        transition: none;
    }
}
        <?php
        return ob_get_clean();
    }

    /**
     * Get shortcode JavaScript
     *
     * Handles real-time consent state updates for both shortcodes.
     * Listens to mdlc:changed event and updates UI accordingly.
     *
     * @since 1.6.0
     * @return string JavaScript code
     */
    private function get_shortcode_javascript() {
        ob_start();
        ?>
(function(window, document) {
    'use strict';
    
    /**
     * Update consent status chips
     */
    function updateStatusChips() {
        if (!window.mdlcConsent || !window.mdlcConsent.current) {
            return;
        }
        
        var state = mdlcConsent.current();
        
        // Update all status chips on the page
        var chips = document.querySelectorAll('.mdlc-status__chip');
        
        chips.forEach(function(chip) {
            var type = chip.getAttribute('data-mdlc-type');
            
            if (type === 'analytics') {
                var isOn = state.analytics;
                chip.classList.remove('mdlc-status__chip--on', 'mdlc-status__chip--off');
                chip.classList.add(isOn ? 'mdlc-status__chip--on' : 'mdlc-status__chip--off');
                
                var valueEl = chip.querySelector('.mdlc-status__value');
                if (valueEl) {
                    var onLabel = (window.mdlcShortcodeI18n && window.mdlcShortcodeI18n.onLabel) ? window.mdlcShortcodeI18n.onLabel : 'On';
                    var offLabel = (window.mdlcShortcodeI18n && window.mdlcShortcodeI18n.offLabel) ? window.mdlcShortcodeI18n.offLabel : 'Off';
                    valueEl.textContent = isOn ? onLabel : offLabel;
                }
            } else if (type === 'ads') {
                var isOn = state.ads;
                chip.classList.remove('mdlc-status__chip--on', 'mdlc-status__chip--off');
                chip.classList.add(isOn ? 'mdlc-status__chip--on' : 'mdlc-status__chip--off');
                
                var valueEl = chip.querySelector('.mdlc-status__value');
                if (valueEl) {
                    var onLabel = (window.mdlcShortcodeI18n && window.mdlcShortcodeI18n.onLabel) ? window.mdlcShortcodeI18n.onLabel : 'On';
                    var offLabel = (window.mdlcShortcodeI18n && window.mdlcShortcodeI18n.offLabel) ? window.mdlcShortcodeI18n.offLabel : 'Off';
                    valueEl.textContent = isOn ? onLabel : offLabel;
                }
            }
        });
    }
    
    /**
     * Handle consent action button clicks
     */
    function handleButtonClick(e) {
        var button = e.target.closest('[data-mdlc-action]');
        if (!button) {
            return;
        }
        
        e.preventDefault();
        
        var action = button.getAttribute('data-mdlc-action');
        
        if (!window.mdlcConsent) {
            return;
        }
        
        switch (action) {
            case 'accept-all':
                mdlcConsent.acceptAll();
                break;
            case 'analytics-only':
                mdlcConsent.acceptAnalyticsOnly();
                break;
            case 'decline-all':
                mdlcConsent.declineAll();
                break;
            case 'reset':
                var confirmMsg = (window.mdlcShortcodeI18n && window.mdlcShortcodeI18n.confirmReset)
                    ? window.mdlcShortcodeI18n.confirmReset
                    : 'Are you sure you want to reset your consent preferences?';
                if (confirm(confirmMsg)) {
                    mdlcConsent.reset();
                }
                break;
        }
    }
    
    /**
     * Initialize shortcode functionality
     */
    function init() {
        // Update chips on page load
        updateStatusChips();
        
        // Listen for consent changes
        document.addEventListener('mdlc:changed', updateStatusChips);
        
        // Handle button clicks (event delegation)
        document.addEventListener('click', handleButtonClick);
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
     * Render consent status shortcode
     *
     * Displays current consent state as chips (Analytics: On/Off, Ads: On/Off).
     * Updates in real-time when consent changes.
     *
     * @since 1.6.0
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function render_consent_status($atts) {
        // Mark that assets need to be enqueued
        $this->assets_enqueued = true;
        
        // Parse shortcode attributes
        $atts = shortcode_atts(
            array(
                'show_analytics' => 'yes',
                'show_ads'       => 'yes',
            ),
            $atts,
            'mdlc_consent_status'
        );
        
        $show_analytics = (sanitize_text_field($atts['show_analytics']) === 'yes');
        $show_ads = (sanitize_text_field($atts['show_ads']) === 'yes');
        
        ob_start();
        ?>
        <div class="mdlc-status" role="status" aria-live="polite">
            <?php if ($show_analytics) : ?>
                <span class="mdlc-status__chip mdlc-status__chip--off" 
                      data-mdlc-type="analytics"
                      aria-label="<?php esc_attr_e('Analytics consent status', 'maxtdesign-lean-consent'); ?>">
                    <span class="mdlc-status__label">
                        <?php esc_html_e('Analytics:', 'maxtdesign-lean-consent'); ?>
                    </span>
                    <strong class="mdlc-status__value">
                        <?php esc_html_e('Off', 'maxtdesign-lean-consent'); ?>
                    </strong>
                </span>
            <?php endif; ?>
            
            <?php if ($show_ads) : ?>
                <span class="mdlc-status__chip mdlc-status__chip--off" 
                      data-mdlc-type="ads"
                      aria-label="<?php esc_attr_e('Advertising consent status', 'maxtdesign-lean-consent'); ?>">
                    <span class="mdlc-status__label">
                        <?php esc_html_e('Ads:', 'maxtdesign-lean-consent'); ?>
                    </span>
                    <strong class="mdlc-status__value">
                        <?php esc_html_e('Off', 'maxtdesign-lean-consent'); ?>
                    </strong>
                </span>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render manage consent shortcode
     *
     * Displays full consent management interface with action buttons
     * and current status display. Includes reset preferences option.
     *
     * @since 1.6.0
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function render_manage_consent($atts) {
        // Mark that assets need to be enqueued
        $this->assets_enqueued = true;
        
        // Parse shortcode attributes
        $atts = shortcode_atts(
            array(
                'title'       => __('Your Privacy Choices', 'maxtdesign-lean-consent'),
                'description' => __('Choose how we use cookies and similar technologies.', 'maxtdesign-lean-consent'),
                'show_status' => 'yes',
            ),
            $atts,
            'mdlc_manage_consent'
        );
        
        $title = sanitize_text_field($atts['title']);
        $description = sanitize_text_field($atts['description']);
        $show_status = (sanitize_text_field($atts['show_status']) === 'yes');
        
        ob_start();
        ?>
        <div class="mdlc-manage">
            <h3 class="mdlc-manage__title">
                <?php echo esc_html($title); ?>
            </h3>
            
            <p class="mdlc-manage__description">
                <?php echo esc_html($description); ?>
            </p>
            
            <div class="mdlc-manage__actions">
                <button type="button" 
                        class="mdlc-manage__button mdlc-manage__button--primary" 
                        data-mdlc-action="accept-all"
                        aria-label="<?php esc_attr_e('Accept all cookies', 'maxtdesign-lean-consent'); ?>">
                    <?php esc_html_e('Accept All', 'maxtdesign-lean-consent'); ?>
                </button>
                
                <button type="button" 
                        class="mdlc-manage__button mdlc-manage__button--secondary" 
                        data-mdlc-action="analytics-only"
                        aria-label="<?php esc_attr_e('Accept analytics cookies only', 'maxtdesign-lean-consent'); ?>">
                    <?php esc_html_e('Analytics Only', 'maxtdesign-lean-consent'); ?>
                </button>
                
                <button type="button" 
                        class="mdlc-manage__button mdlc-manage__button--secondary" 
                        data-mdlc-action="decline-all"
                        aria-label="<?php esc_attr_e('Decline all cookies', 'maxtdesign-lean-consent'); ?>">
                    <?php esc_html_e('Decline All', 'maxtdesign-lean-consent'); ?>
                </button>
                
                <button type="button" 
                        class="mdlc-manage__button mdlc-manage__button--tertiary" 
                        data-mdlc-action="reset"
                        aria-label="<?php esc_attr_e('Reset your consent preferences', 'maxtdesign-lean-consent'); ?>">
                    <?php esc_html_e('Reset Preferences', 'maxtdesign-lean-consent'); ?>
                </button>
            </div>
            
            <?php if ($show_status) : ?>
                <div class="mdlc-manage__current">
                    <span class="mdlc-manage__current-label">
                        <?php esc_html_e('Current Settings:', 'maxtdesign-lean-consent'); ?>
                    </span>
                    
                    <div class="mdlc-status" role="status" aria-live="polite">
                        <span class="mdlc-status__chip mdlc-status__chip--off" 
                              data-mdlc-type="analytics"
                              aria-label="<?php esc_attr_e('Analytics consent status', 'maxtdesign-lean-consent'); ?>">
                            <span class="mdlc-status__label">
                                <?php esc_html_e('Analytics:', 'maxtdesign-lean-consent'); ?>
                            </span>
                            <strong class="mdlc-status__value">
                                <?php esc_html_e('Off', 'maxtdesign-lean-consent'); ?>
                            </strong>
                        </span>
                        
                        <span class="mdlc-status__chip mdlc-status__chip--off" 
                              data-mdlc-type="ads"
                              aria-label="<?php esc_attr_e('Advertising consent status', 'maxtdesign-lean-consent'); ?>">
                            <span class="mdlc-status__label">
                                <?php esc_html_e('Ads:', 'maxtdesign-lean-consent'); ?>
                            </span>
                            <strong class="mdlc-status__value">
                                <?php esc_html_e('Off', 'maxtdesign-lean-consent'); ?>
                            </strong>
                        </span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}

