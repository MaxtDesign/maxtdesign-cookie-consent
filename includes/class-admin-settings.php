<?php
/**
 * Admin Settings
 *
 * Handles the WordPress admin settings page for configuring popup appearance,
 * behavior, and Elementor integration. Uses Settings API for proper sanitization.
 *
 * @package MaxtDesign_Cookie_Consent
 * @since 1.6.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class MDCC_Admin_Settings {
    /**
     * Single instance
     *
     * @var MDCC_Admin_Settings
     */
    private static $instance = null;

    /**
     * Settings option name
     *
     * @var string
     */
    const OPTION_NAME = 'mdcc_settings';

    /**
     * Settings option group
     *
     * @var string
     */
    const OPTION_GROUP = 'mdcc_settings_group';

    /**
     * Settings page slug
     *
     * @var string
     */
    const PAGE_SLUG = 'mdcc-settings';

    /**
     * Get instance
     *
     * @return MDCC_Admin_Settings
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
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));

        // Add settings page to menu
        add_action('admin_menu', array($this, 'add_settings_page'));

        // Add settings link on plugins page
        add_filter('plugin_action_links_' . MDCC_PLUGIN_BASENAME, array($this, 'add_settings_link'));

        // Enqueue admin assets
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // Warn if Elementor popup ID is set but Elementor is not active
        add_action('admin_notices', array($this, 'maybe_render_elementor_missing_notice'));
    }

    /**
     * Show an admin notice if the Elementor popup integration is configured
     * but Elementor is not active. Without this, the built-in popup is
     * suppressed and the site has no consent UI at all.
     *
     * @since 1.7.4
     */
    public function maybe_render_elementor_missing_notice() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $settings = get_option(self::OPTION_NAME, mdcc_default_settings());

        if (empty($settings['elementor_popup_id'])) {
            return;
        }

        if (did_action('elementor/loaded') || defined('ELEMENTOR_VERSION')) {
            return;
        }

        $settings_url = admin_url('options-general.php?page=' . self::PAGE_SLUG);
        ?>
        <div class="notice notice-warning">
            <p>
                <strong><?php esc_html_e('MaxtDesign Cookie Consent:', 'maxtdesign-cookie-consent'); ?></strong>
                <?php
                printf(
                    /* translators: %s: link to the plugin settings page */
                    esc_html__('An Elementor popup ID is configured but Elementor is not active. The built-in consent popup is currently suppressed, so visitors will not see any consent UI. %s', 'maxtdesign-cookie-consent'),
                    '<a href="' . esc_url($settings_url) . '">' . esc_html__('Review settings', 'maxtdesign-cookie-consent') . '</a>'
                );
                ?>
            </p>
        </div>
        <?php
    }

    /**
     * Register settings with WordPress Settings API
     *
     * @since 1.6.0
     */
    public function register_settings() {
        // Register the settings option
        register_setting(
            self::OPTION_GROUP,
            self::OPTION_NAME,
            array(
                'type'              => 'array',
                'sanitize_callback' => array($this, 'sanitize_settings'),
                'default'           => mdcc_default_settings(),
            )
        );

        // Section: Popup Appearance
        add_settings_section(
            'mdcc_section_appearance',
            __('Popup Appearance', 'maxtdesign-cookie-consent'),
            array($this, 'render_section_appearance'),
            self::PAGE_SLUG
        );

        add_settings_field(
            'popup_enabled',
            __('Enable Popup', 'maxtdesign-cookie-consent'),
            array($this, 'render_field_popup_enabled'),
            self::PAGE_SLUG,
            'mdcc_section_appearance'
        );

        add_settings_field(
            'popup_style',
            __('Style Preset', 'maxtdesign-cookie-consent'),
            array($this, 'render_field_popup_style'),
            self::PAGE_SLUG,
            'mdcc_section_appearance'
        );

        add_settings_field(
            'popup_position',
            __('Position', 'maxtdesign-cookie-consent'),
            array($this, 'render_field_popup_position'),
            self::PAGE_SLUG,
            'mdcc_section_appearance'
        );

        add_settings_field(
            'popup_primary_color',
            __('Primary Color', 'maxtdesign-cookie-consent'),
            array($this, 'render_field_popup_primary_color'),
            self::PAGE_SLUG,
            'mdcc_section_appearance'
        );

        add_settings_field(
            'popup_animation',
            __('Animation', 'maxtdesign-cookie-consent'),
            array($this, 'render_field_popup_animation'),
            self::PAGE_SLUG,
            'mdcc_section_appearance'
        );

        // Section: Popup Content
        add_settings_section(
            'mdcc_section_content',
            __('Popup Content', 'maxtdesign-cookie-consent'),
            array($this, 'render_section_content'),
            self::PAGE_SLUG
        );

        add_settings_field(
            'popup_title',
            __('Popup Title', 'maxtdesign-cookie-consent'),
            array($this, 'render_field_popup_title'),
            self::PAGE_SLUG,
            'mdcc_section_content'
        );

        add_settings_field(
            'popup_message',
            __('Popup Message', 'maxtdesign-cookie-consent'),
            array($this, 'render_field_popup_message'),
            self::PAGE_SLUG,
            'mdcc_section_content'
        );

        // Section: Behavior Settings
        add_settings_section(
            'mdcc_section_behavior',
            __('Behavior Settings', 'maxtdesign-cookie-consent'),
            array($this, 'render_section_behavior'),
            self::PAGE_SLUG
        );

        add_settings_field(
            'popup_shown_duration',
            __('Cookie Duration', 'maxtdesign-cookie-consent'),
            array($this, 'render_field_popup_shown_duration'),
            self::PAGE_SLUG,
            'mdcc_section_behavior'
        );

        add_settings_field(
            'reprompt_on_decline',
            __('Re-prompt on Decline', 'maxtdesign-cookie-consent'),
            array($this, 'render_field_reprompt_on_decline'),
            self::PAGE_SLUG,
            'mdcc_section_behavior'
        );

        // Section: Elementor Integration
        add_settings_section(
            'mdcc_section_elementor',
            __('Elementor Integration (Optional)', 'maxtdesign-cookie-consent'),
            array($this, 'render_section_elementor'),
            self::PAGE_SLUG
        );

        add_settings_field(
            'elementor_popup_id',
            __('Elementor Popup ID', 'maxtdesign-cookie-consent'),
            array($this, 'render_field_elementor_popup_id'),
            self::PAGE_SLUG,
            'mdcc_section_elementor'
        );

        // Section: Advanced Settings
        add_settings_section(
            'mdcc_advanced_section',
            __('Advanced Settings', 'maxtdesign-cookie-consent'),
            array($this, 'render_advanced_section'),
            self::PAGE_SLUG
        );

        add_settings_field(
            'gcm_inject_default',
            __('Inject Default Consent State', 'maxtdesign-cookie-consent'),
            array($this, 'render_gcm_inject_default_field'),
            self::PAGE_SLUG,
            'mdcc_advanced_section'
        );
    }

    /**
     * Sanitize settings before saving
     *
     * SECURITY NOTE:
     * This settings page uses the WordPress Settings API end-to-end:
     * - The form posts to options.php which verifies the nonce generated by settings_fields().
     * - WordPress core checks user capabilities (manage_options) before processing.
     * - Only after nonce and capability checks pass, WordPress calls this sanitize callback.
     * Therefore, no manual nonce or capability checks should occur here. This function must
     * only sanitize/validate values and return a clean array.
     *
     * @since 1.6.0
     * @param array $input Raw input from form
     * @return array Sanitized settings
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        $defaults = mdcc_default_settings();

        // Popup enabled (boolean)
        $sanitized['popup_enabled'] = !empty($input['popup_enabled']);

        // Popup style (whitelist)
        $allowed_styles = array('minimal', 'modern', 'bold');
        $sanitized['popup_style'] = in_array($input['popup_style'], $allowed_styles, true)
            ? $input['popup_style']
            : $defaults['popup_style'];

        // Popup position (whitelist)
        $allowed_positions = array('top', 'bottom', 'center');
        $sanitized['popup_position'] = in_array($input['popup_position'], $allowed_positions, true)
            ? $input['popup_position']
            : $defaults['popup_position'];

        // Primary color (hex color)
        $sanitized['popup_primary_color'] = !empty($input['popup_primary_color'])
            ? sanitize_hex_color($input['popup_primary_color'])
            : $defaults['popup_primary_color'];

        // Popup animation (whitelist)
        $allowed_animations = array('slide', 'fade', 'none');
        $sanitized['popup_animation'] = in_array($input['popup_animation'], $allowed_animations, true)
            ? $input['popup_animation']
            : $defaults['popup_animation'];

        // Popup title (text)
        $sanitized['popup_title'] = !empty($input['popup_title'])
            ? sanitize_text_field($input['popup_title'])
            : $defaults['popup_title'];

        // Popup message (textarea)
        $sanitized['popup_message'] = !empty($input['popup_message'])
            ? sanitize_textarea_field($input['popup_message'])
            : $defaults['popup_message'];

        // Cookie duration (positive integer, 1-365 days)
        $duration = isset($input['popup_shown_duration']) ? absint($input['popup_shown_duration']) : 0;
        $sanitized['popup_shown_duration'] = ($duration >= 1 && $duration <= 365)
            ? $duration
            : $defaults['popup_shown_duration'];

        // Re-prompt on decline (boolean)
        $sanitized['reprompt_on_decline'] = !empty($input['reprompt_on_decline']);

        // Elementor popup ID (positive integer or empty)
        $sanitized['elementor_popup_id'] = !empty($input['elementor_popup_id'])
            ? absint($input['elementor_popup_id'])
            : '';

        // GCM inject default (boolean)
        $sanitized['gcm_inject_default'] = !empty($input['gcm_inject_default']);

        return $sanitized;
    }

    /**
     * Add settings page to WordPress admin menu
     *
     * @since 1.6.0
     */
    public function add_settings_page() {
        add_options_page(
            __('Cookie Consent Settings', 'maxtdesign-cookie-consent'),
            __('Cookie Consent', 'maxtdesign-cookie-consent'),
            'manage_options',
            self::PAGE_SLUG,
            array($this, 'render_settings_page')
        );
    }

    /**
     * Add settings link on plugins page
     *
     * @since 1.6.0
     * @param array $links Existing plugin action links
     * @return array Modified links
     */
    public function add_settings_link($links) {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            esc_url(admin_url('options-general.php?page=' . self::PAGE_SLUG)),
            esc_html__('Settings', 'maxtdesign-cookie-consent')
        );

        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Enqueue admin assets
     *
     * Loads minified version by default, source version when SCRIPT_DEBUG is enabled.
     *
     * @since 1.6.0
     * @param string $hook Current admin page hook
     */
    public function enqueue_admin_assets($hook) {
        if ('settings_page_' . self::PAGE_SLUG !== $hook) {
            return;
        }

        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');

        $suffix = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';

        wp_enqueue_style(
            'mdcc-admin',
            MDCC_PLUGIN_URL . 'assets/css/admin' . $suffix . '.css',
            array(),
            MDCC_VERSION
        );

        wp_enqueue_script(
            'mdcc-admin',
            MDCC_PLUGIN_URL . 'assets/js/admin' . $suffix . '.js',
            array('jquery', 'wp-color-picker'),
            MDCC_VERSION,
            true
        );
    }

    /**
     * Render settings page
     *
     * @since 1.6.0
     */
    public function render_settings_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }

        // Rely on WordPress core to display the settings-updated notice globally.
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <div class="mdcc-admin-header">
                <p class="description">
                    <?php esc_html_e('Configure your consent popup appearance, content, and behavior. Lightweight consent management with proper Google Consent Mode v2 implementation.', 'maxtdesign-cookie-consent'); ?>
                </p>
            </div>

            <form method="post" action="options.php">
                <?php
                settings_fields(self::OPTION_GROUP);
                do_settings_sections(self::PAGE_SLUG);
                submit_button(__('Save Settings', 'maxtdesign-cookie-consent'));
                ?>
            </form>

            <div class="mdcc-admin-sidebar">
                <div class="mdcc-info-box">
                    <h3><?php esc_html_e('Shortcodes Available', 'maxtdesign-cookie-consent'); ?></h3>
                    <p><code>[mdcc_consent_status]</code></p>
                    <p class="description">
                        <?php esc_html_e('Displays current consent status chips (Analytics: On/Off, Ads: On/Off)', 'maxtdesign-cookie-consent'); ?>
                    </p>

                    <p><code>[mdcc_manage_consent]</code></p>
                    <p class="description">
                        <?php esc_html_e('Displays consent management interface with Accept/Analytics/Decline buttons', 'maxtdesign-cookie-consent'); ?>
                    </p>
                </div>

                <div class="mdcc-info-box">
                    <h3><?php esc_html_e('Documentation & Support', 'maxtdesign-cookie-consent'); ?></h3>
                    <ul>
                        <li><a href="https://maxtdesign.com/plugins/cookie-consent" target="_blank"><?php esc_html_e('Plugin Documentation', 'maxtdesign-cookie-consent'); ?></a></li>
                        <li><a href="https://maxtdesign.com/plugins/cookie-consent#faq" target="_blank"><?php esc_html_e('FAQ & Troubleshooting', 'maxtdesign-cookie-consent'); ?></a></li>
                        <li><a href="https://wordpress.org/support/plugin/maxtdesign-cookie-consent/" target="_blank"><?php esc_html_e('Support Forum', 'maxtdesign-cookie-consent'); ?></a></li>
                        <li><a href="https://github.com/sponsors/MaxtDesign" target="_blank" style="color: #d63638; font-weight: 600;"><?php esc_html_e('♥ Sponsor This Plugin', 'maxtdesign-cookie-consent'); ?></a></li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }

    /* ========================================================================
       SECTION CALLBACKS
       ======================================================================== */

    /**
     * Render appearance section description
     */
    public function render_section_appearance() {
        echo '<p>' . esc_html__('Customize the visual appearance of the consent popup.', 'maxtdesign-cookie-consent') . '</p>';
    }

    /**
     * Render content section description
     */
    public function render_section_content() {
        echo '<p>' . esc_html__('Configure the text content displayed in the popup.', 'maxtdesign-cookie-consent') . '</p>';
    }

    /**
     * Render behavior section description
     */
    public function render_section_behavior() {
        echo '<p>' . esc_html__('Control popup behavior and timing.', 'maxtdesign-cookie-consent') . '</p>';
    }

    /**
     * Render Elementor section description
     */
    public function render_section_elementor() {
        echo '<p>' . esc_html__('If you prefer to use a custom Elementor popup instead of the built-in popup, enter your Elementor Popup ID here. Leave blank to use the built-in popup.', 'maxtdesign-cookie-consent') . '</p>';
    }

    /**
     * Render advanced settings section description
     *
     * @since 1.7.1
     */
    public function render_advanced_section() {
        echo '<p>' . esc_html__('Advanced Google Consent Mode configuration.', 'maxtdesign-cookie-consent') . '</p>';
    }

    /* ========================================================================
       FIELD CALLBACKS
       ======================================================================== */

    /**
     * Render popup enabled field
     */
    public function render_field_popup_enabled() {
        $settings = get_option(self::OPTION_NAME, mdcc_default_settings());
        ?>
        <label>
            <input type="checkbox"
                   name="<?php echo esc_attr(self::OPTION_NAME); ?>[popup_enabled]"
                   value="1"
                   <?php checked(!empty($settings['popup_enabled']), true); ?> />
            <?php esc_html_e('Display the consent popup to visitors', 'maxtdesign-cookie-consent'); ?>
        </label>
        <p class="description">
            <?php esc_html_e('Uncheck to temporarily disable the popup without losing your settings.', 'maxtdesign-cookie-consent'); ?>
        </p>
        <?php
    }

    /**
     * Render popup style field
     */
    public function render_field_popup_style() {
        $settings = get_option(self::OPTION_NAME, mdcc_default_settings());
        $current = $settings['popup_style'];
        ?>
        <select name="<?php echo esc_attr(self::OPTION_NAME); ?>[popup_style]" id="mdcc-popup-style">
            <option value="minimal" <?php selected($current, 'minimal'); ?>>
                <?php esc_html_e('Minimal', 'maxtdesign-cookie-consent'); ?>
            </option>
            <option value="modern" <?php selected($current, 'modern'); ?>>
                <?php esc_html_e('Modern', 'maxtdesign-cookie-consent'); ?>
            </option>
            <option value="bold" <?php selected($current, 'bold'); ?>>
                <?php esc_html_e('Bold', 'maxtdesign-cookie-consent'); ?>
            </option>
        </select>
        <p class="description">
            <?php esc_html_e('Choose a visual style preset. Minimal: Clean and subtle. Modern: Rounded and polished. Bold: Strong and prominent.', 'maxtdesign-cookie-consent'); ?>
        </p>
        <?php
    }

    /**
     * Render popup position field
     */
    public function render_field_popup_position() {
        $settings = get_option(self::OPTION_NAME, mdcc_default_settings());
        $current = $settings['popup_position'];
        ?>
        <select name="<?php echo esc_attr(self::OPTION_NAME); ?>[popup_position]" id="mdcc-popup-position">
            <option value="top" <?php selected($current, 'top'); ?>>
                <?php esc_html_e('Top Banner', 'maxtdesign-cookie-consent'); ?>
            </option>
            <option value="bottom" <?php selected($current, 'bottom'); ?>>
                <?php esc_html_e('Bottom Banner', 'maxtdesign-cookie-consent'); ?>
            </option>
            <option value="center" <?php selected($current, 'center'); ?>>
                <?php esc_html_e('Center Modal', 'maxtdesign-cookie-consent'); ?>
            </option>
        </select>
        <p class="description">
            <?php esc_html_e('Where the popup appears on the screen.', 'maxtdesign-cookie-consent'); ?>
        </p>
        <?php
    }

    /**
     * Render primary color field
     */
    public function render_field_popup_primary_color() {
        $settings = get_option(self::OPTION_NAME, mdcc_default_settings());
        $current = $settings['popup_primary_color'];
        ?>
        <input type="text"
               name="<?php echo esc_attr(self::OPTION_NAME); ?>[popup_primary_color]"
               value="<?php echo esc_attr($current); ?>"
               class="mdcc-color-picker"
               data-default-color="#0073aa" />
        <p class="description">
            <?php esc_html_e('Color for primary buttons. Click to choose a color.', 'maxtdesign-cookie-consent'); ?>
        </p>
        <?php
    }

    /**
     * Render animation field
     */
    public function render_field_popup_animation() {
        $settings = get_option(self::OPTION_NAME, mdcc_default_settings());
        $current = $settings['popup_animation'];
        ?>
        <select name="<?php echo esc_attr(self::OPTION_NAME); ?>[popup_animation]" id="mdcc-popup-animation">
            <option value="slide" <?php selected($current, 'slide'); ?>>
                <?php esc_html_e('Slide In', 'maxtdesign-cookie-consent'); ?>
            </option>
            <option value="fade" <?php selected($current, 'fade'); ?>>
                <?php esc_html_e('Fade In', 'maxtdesign-cookie-consent'); ?>
            </option>
            <option value="none" <?php selected($current, 'none'); ?>>
                <?php esc_html_e('No Animation', 'maxtdesign-cookie-consent'); ?>
            </option>
        </select>
        <p class="description">
            <?php esc_html_e('Animation when popup appears.', 'maxtdesign-cookie-consent'); ?>
        </p>
        <?php
    }

    /**
     * Render popup title field
     */
    public function render_field_popup_title() {
        $settings = get_option(self::OPTION_NAME, mdcc_default_settings());
        $current = $settings['popup_title'];
        ?>
        <input type="text"
               name="<?php echo esc_attr(self::OPTION_NAME); ?>[popup_title]"
               value="<?php echo esc_attr($current); ?>"
               class="regular-text" />
        <p class="description">
            <?php esc_html_e('Headline text displayed in the popup.', 'maxtdesign-cookie-consent'); ?>
        </p>
        <?php
    }

    /**
     * Render popup message field
     */
    public function render_field_popup_message() {
        $settings = get_option(self::OPTION_NAME, mdcc_default_settings());
        $current = $settings['popup_message'];
        ?>
        <textarea name="<?php echo esc_attr(self::OPTION_NAME); ?>[popup_message]"
                  rows="3"
                  class="large-text"><?php echo esc_textarea($current); ?></textarea>
        <p class="description">
            <?php esc_html_e('Description text explaining cookie usage.', 'maxtdesign-cookie-consent'); ?>
        </p>
        <?php
    }

    /**
     * Render cookie duration field
     */
    public function render_field_popup_shown_duration() {
        $settings = get_option(self::OPTION_NAME, mdcc_default_settings());
        $current = $settings['popup_shown_duration'];
        ?>
        <input type="number"
               name="<?php echo esc_attr(self::OPTION_NAME); ?>[popup_shown_duration]"
               value="<?php echo esc_attr($current); ?>"
               min="1"
               max="365"
               step="1"
               class="small-text" />
        <?php esc_html_e('days', 'maxtdesign-cookie-consent'); ?>
        <p class="description">
            <?php esc_html_e('How many days after closing the popup before showing it again (1-365 days).', 'maxtdesign-cookie-consent'); ?>
        </p>
        <?php
    }

    /**
     * Render re-prompt on decline field
     */
    public function render_field_reprompt_on_decline() {
        $settings = get_option(self::OPTION_NAME, mdcc_default_settings());
        ?>
        <label>
            <input type="checkbox"
                   name="<?php echo esc_attr(self::OPTION_NAME); ?>[reprompt_on_decline]"
                   value="1"
                   <?php checked(!empty($settings['reprompt_on_decline']), true); ?> />
            <?php esc_html_e('Show popup again once per session if user declines all cookies', 'maxtdesign-cookie-consent'); ?>
        </label>
        <p class="description">
            <?php esc_html_e('If enabled, the popup will re-appear once per browsing session when a user declines tracking.', 'maxtdesign-cookie-consent'); ?>
        </p>
        <?php
    }

    /**
     * Render Elementor popup ID field
     */
    public function render_field_elementor_popup_id() {
        $settings = get_option(self::OPTION_NAME, mdcc_default_settings());
        $current = $settings['elementor_popup_id'];
        ?>
        <input type="number"
               name="<?php echo esc_attr(self::OPTION_NAME); ?>[elementor_popup_id]"
               value="<?php echo esc_attr($current); ?>"
               min="1"
               step="1"
               class="regular-text"
               placeholder="<?php esc_attr_e('Leave blank to use built-in popup', 'maxtdesign-cookie-consent'); ?>" />
        <p class="description">
            <?php
            printf(
                /* translators: %s: Link to documentation */
                esc_html__('Enter your Elementor Popup ID to use a custom popup instead of the built-in one. %s', 'maxtdesign-cookie-consent'),
                '<a href="https://maxtdesign.com/plugins/cookie-consent#faq" target="_blank">' . esc_html__('Learn how to find your popup ID', 'maxtdesign-cookie-consent') . '</a>'
            );
            ?>
        </p>
        <?php
    }

    /**
     * Render GCM inject default checkbox field
     *
     * @since 1.7.1
     */
    public function render_gcm_inject_default_field() {
        $settings = get_option(self::OPTION_NAME, mdcc_default_settings());
        $value = isset($settings['gcm_inject_default']) ? $settings['gcm_inject_default'] : true;
        ?>
        <label>
            <input type="checkbox"
                   name="<?php echo esc_attr(self::OPTION_NAME); ?>[gcm_inject_default]"
                   value="1"
                   <?php checked($value, true); ?> />
            <?php esc_html_e('Inject default consent in page head before tracking scripts', 'maxtdesign-cookie-consent'); ?>
        </label>
        <p class="description">
            <?php
            esc_html_e(
                'Injects gtag("consent", "default", {...}) in page head before tracking scripts load. Required for proper GDPR/CCPA compliance with Google Tag Manager. Only disable if you are manually handling consent defaults or experiencing conflicts.',
                'maxtdesign-cookie-consent'
            );
            ?>
        </p>
        <?php
    }
}

