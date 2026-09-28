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

        // Section: Popup Design
        add_settings_section(
            'mdcc_section_design',
            __('Popup Design', 'maxtdesign-cookie-consent'),
            array($this, 'render_section_design'),
            self::PAGE_SLUG
        );

        add_settings_field(
            'popup_buttons',
            __('Buttons', 'maxtdesign-cookie-consent'),
            array($this, 'render_field_popup_buttons'),
            self::PAGE_SLUG,
            'mdcc_section_design'
        );

        add_settings_field(
            'manage_page_id',
            __('Cookie settings page', 'maxtdesign-cookie-consent'),
            array($this, 'render_field_manage_page_id'),
            self::PAGE_SLUG,
            'mdcc_section_design'
        );

        add_settings_field(
            'popup_labels',
            __('Button Labels', 'maxtdesign-cookie-consent'),
            array($this, 'render_field_popup_labels'),
            self::PAGE_SLUG,
            'mdcc_section_design'
        );

        add_settings_field(
            'popup_desktop_width',
            __('Desktop Width', 'maxtdesign-cookie-consent'),
            array($this, 'render_field_popup_desktop_width'),
            self::PAGE_SLUG,
            'mdcc_section_design'
        );

        $design_colors = array(
            'popup_bg_color'          => __('Background Color', 'maxtdesign-cookie-consent'),
            'popup_text_color'        => __('Text Color', 'maxtdesign-cookie-consent'),
            'popup_button_text_color' => __('Primary Button Text Color', 'maxtdesign-cookie-consent'),
        );
        foreach ($design_colors as $key => $label) {
            add_settings_field(
                $key,
                $label,
                array($this, 'render_field_design_color'),
                self::PAGE_SLUG,
                'mdcc_section_design',
                array('key' => $key)
            );
        }

        add_settings_field(
            'popup_radius',
            __('Corner Radius', 'maxtdesign-cookie-consent'),
            array($this, 'render_field_popup_radius'),
            self::PAGE_SLUG,
            'mdcc_section_design'
        );

        add_settings_field(
            'popup_inherit_font',
            __('Button Font', 'maxtdesign-cookie-consent'),
            array($this, 'render_field_popup_inherit_font'),
            self::PAGE_SLUG,
            'mdcc_section_design'
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

        add_settings_field(
            'popup_title_optout',
            __('Popup Title (opt-out regions)', 'maxtdesign-cookie-consent'),
            array($this, 'render_field_popup_title_optout'),
            self::PAGE_SLUG,
            'mdcc_section_content'
        );

        add_settings_field(
            'popup_message_optout',
            __('Popup Message (opt-out regions)', 'maxtdesign-cookie-consent'),
            array($this, 'render_field_popup_message_optout'),
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
            'consent_model',
            __('Consent Model', 'maxtdesign-cookie-consent'),
            array($this, 'render_field_consent_model'),
            self::PAGE_SLUG,
            'mdcc_section_behavior'
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

        add_settings_field(
            'consent_api_bridge',
            __('WP Consent API Integration', 'maxtdesign-cookie-consent'),
            array($this, 'render_consent_api_bridge_field'),
            self::PAGE_SLUG,
            'mdcc_advanced_section'
        );

        /**
         * Fires after the core settings sections and fields are registered.
         *
         * Lets add-ons (e.g. Pro) register their own sections/fields under the
         * plugin's settings page via add_settings_section()/add_settings_field().
         *
         * @since 1.9.0
         * @param string $page  The settings page slug (self::PAGE_SLUG).
         * @param string $group The registered setting/option group (self::OPTION_GROUP).
         */
        do_action('mdcc_admin_settings_sections', self::PAGE_SLUG, self::OPTION_GROUP);
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

        // Button layout (whitelist)
        $sanitized['popup_buttons'] = isset($input['popup_buttons']) && in_array($input['popup_buttons'], MDCC_Popup_System::button_layouts(), true)
            ? $input['popup_buttons']
            : $defaults['popup_buttons'];

        // Cookie settings page: must be a published page, else none. Its
        // address is resolved here, never taken from the form.
        $manage_url = MDCC_Popup_System::resolve_manage_url($input['manage_page_id'] ?? 0);
        $sanitized['manage_page_id'] = '' !== $manage_url ? absint($input['manage_page_id']) : 0;
        $sanitized['manage_url']     = $manage_url;

        // Button labels (text, or empty to use the translated default)
        foreach (array('label_accept', 'label_manage', 'label_decline', 'label_analytics') as $key) {
            $sanitized[$key] = isset($input[$key]) && is_string($input[$key]) ? sanitize_text_field($input[$key]) : '';
        }

        // Desktop width (whitelist, percent)
        $width = isset($input['popup_desktop_width']) ? absint($input['popup_desktop_width']) : 0;
        $sanitized['popup_desktop_width'] = in_array($width, MDCC_Popup_System::desktop_widths(), true)
            ? $width
            : $defaults['popup_desktop_width'];

        // Design colours (hex colour, or empty to keep the style preset's own)
        foreach (array('popup_bg_color', 'popup_text_color', 'popup_button_text_color') as $key) {
            $color = isset($input[$key]) && is_string($input[$key]) ? sanitize_hex_color(trim($input[$key])) : '';
            $sanitized[$key] = is_string($color) ? $color : '';
        }

        // Corner radius (0 to 24 px, or empty to keep the style preset's own)
        $sanitized['popup_radius'] = isset($input['popup_radius']) && is_numeric($input['popup_radius'])
            ? min(24, absint($input['popup_radius']))
            : '';

        // Buttons follow the theme font (boolean)
        $sanitized['popup_inherit_font'] = !empty($input['popup_inherit_font']);

        // Popup title (text)
        $sanitized['popup_title'] = !empty($input['popup_title'])
            ? sanitize_text_field($input['popup_title'])
            : $defaults['popup_title'];

        // Popup message (textarea)
        $sanitized['popup_message'] = !empty($input['popup_message'])
            ? sanitize_textarea_field($input['popup_message'])
            : $defaults['popup_message'];

        // Opt-out region popup title (text)
        $sanitized['popup_title_optout'] = !empty($input['popup_title_optout'])
            ? sanitize_text_field($input['popup_title_optout'])
            : $defaults['popup_title_optout'];

        // Opt-out region popup message (textarea)
        $sanitized['popup_message_optout'] = !empty($input['popup_message_optout'])
            ? sanitize_textarea_field($input['popup_message_optout'])
            : $defaults['popup_message_optout'];

        // Consent model (whitelist; default 'optin' keeps pre-1.10 behavior)
        $allowed_models = array_keys(MDCC_Consent_Manager::consent_models());
        $sanitized['consent_model'] = isset($input['consent_model']) && in_array($input['consent_model'], $allowed_models, true)
            ? $input['consent_model']
            : $defaults['consent_model'];

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

        // WP Consent API bridge (boolean)
        $sanitized['consent_api_bridge'] = !empty($input['consent_api_bridge']);

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

            <?php $this->render_template_notice(); ?>

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
                        <li><a href="https://maxtdesign.com/plugins/cookie-consent/docs" target="_blank"><?php esc_html_e('Plugin Documentation', 'maxtdesign-cookie-consent'); ?></a></li>
                        <li><a href="https://maxtdesign.com/plugins/cookie-consent#faq" target="_blank"><?php esc_html_e('FAQ & Troubleshooting', 'maxtdesign-cookie-consent'); ?></a></li>
                        <li><a href="https://wordpress.org/support/plugin/maxtdesign-cookie-consent/" target="_blank"><?php esc_html_e('Support Forum', 'maxtdesign-cookie-consent'); ?></a></li>
                        <li><a href="https://github.com/sponsors/MaxtDesign" target="_blank" style="color: #d63638; font-weight: 600;"><?php esc_html_e('♥ Sponsor This Plugin', 'maxtdesign-cookie-consent'); ?></a></li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * State of the popup template in use, for the settings screen.
     *
     * @since 1.11.0
     * @return array{override: bool, outdated: bool, path: string, version: string, bundled_version: string}
     */
    public static function template_status() {
        $bundled = MDCC_Popup_System::bundled_template();
        $active  = MDCC_Popup_System::locate_popup_template();
        $ours    = MDCC_Popup_System::template_version($bundled);
        $theirs  = MDCC_Popup_System::template_version($active);

        $override = wp_normalize_path($active) !== wp_normalize_path($bundled);

        return array(
            'override'        => $override,
            'outdated'        => $override && ('' === $theirs || version_compare($theirs, $ours, '<')),
            'path'            => str_replace(wp_normalize_path(ABSPATH), '', wp_normalize_path($active)),
            'version'         => $theirs,
            'bundled_version' => $ours,
        );
    }

    /**
     * Tell the site owner when a popup template override is in use, and warn
     * when it is older than the template bundled with the plugin.
     *
     * Runs on this settings screen only. Nothing is checked on the frontend.
     *
     * @since 1.11.0
     */
    private function render_template_notice(): void {
        $status = self::template_status();

        if (!$status['override']) {
            return;
        }

        $version = '' !== $status['version'] ? $status['version'] : __('none', 'maxtdesign-cookie-consent');
        ?>
        <div class="notice inline <?php echo $status['outdated'] ? 'notice-warning' : 'notice-info'; ?>">
            <p>
                <?php
                if ($status['outdated']) {
                    printf(
                        /* translators: 1: version of the override, 2: version of the bundled template, 3: file path */
                        esc_html__('Your popup template override is older than the template in the plugin. Your version: %1$s. Plugin version: %2$s. Compare your copy with templates/popup.php in the plugin folder and update it. File: %3$s', 'maxtdesign-cookie-consent'),
                        esc_html($version),
                        esc_html($status['bundled_version']),
                        '<code>' . esc_html($status['path']) . '</code>'
                    );
                } else {
                    printf(
                        /* translators: 1: file path, 2: version of the override */
                        esc_html__('The popup markup comes from a template override: %1$s (version %2$s).', 'maxtdesign-cookie-consent'),
                        '<code>' . esc_html($status['path']) . '</code>',
                        esc_html($version)
                    );
                }
                ?>
            </p>
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
     * Render design section description
     *
     * @since 1.11.0
     */
    public function render_section_design(): void {
        echo '<p>' . esc_html__('Optional. Leave a field empty to keep what the style preset does. A site that changes nothing here looks the same as before.', 'maxtdesign-cookie-consent') . '</p>';
        echo '<p>' . esc_html__('The plugin does not check your colors for contrast. Make sure the text stays readable against the background you choose.', 'maxtdesign-cookie-consent') . '</p>';
    }

    /**
     * Render button layout field, with the Compact setup steps
     *
     * @since 1.11.0
     */
    public function render_field_popup_buttons(): void {
        $settings = get_option(self::OPTION_NAME, mdcc_default_settings());
        $settings = is_array($settings) ? $settings : mdcc_default_settings();
        $current  = isset($settings['popup_buttons']) ? $settings['popup_buttons'] : 'standard';
        ?>
        <select name="<?php echo esc_attr(self::OPTION_NAME); ?>[popup_buttons]" id="mdcc-popup-buttons">
            <option value="standard" <?php selected($current, 'standard'); ?>>
                <?php esc_html_e('Standard: Accept All, Analytics Only, Decline All', 'maxtdesign-cookie-consent'); ?>
            </option>
            <option value="compact" <?php selected($current, 'compact'); ?>>
                <?php esc_html_e('Compact: Manage options link, Accept all', 'maxtdesign-cookie-consent'); ?>
            </option>
        </select>
        <?php if ('compact' === $current && 'compact' !== MDCC_Popup_System::get_button_layout($settings)) : ?>
            <div class="notice notice-warning inline">
                <p>
                    <?php esc_html_e('Compact is selected, but no published cookie settings page is set. The popup shows the standard three buttons until you select one below.', 'maxtdesign-cookie-consent'); ?>
                </p>
            </div>
        <?php endif; ?>
        <p class="description">
            <strong><?php esc_html_e('Compact', 'maxtdesign-cookie-consent'); ?></strong>
            <?php esc_html_e('shows a "Manage options" link and an "Accept all" button. To use it:', 'maxtdesign-cookie-consent'); ?>
        </p>
        <ol class="description">
            <li><?php esc_html_e('Create a page, for example "Cookie settings".', 'maxtdesign-cookie-consent'); ?></li>
            <li>
                <?php
                printf(
                    /* translators: %s: the shortcode [mdcc_manage_consent] */
                    esc_html__('Add the shortcode %s to that page and publish it.', 'maxtdesign-cookie-consent'),
                    '<code>[mdcc_manage_consent]</code>'
                );
                ?>
            </li>
            <li><?php esc_html_e('Select that page under Cookie settings page below.', 'maxtdesign-cookie-consent'); ?></li>
            <li><?php esc_html_e('Set Buttons to Compact and save.', 'maxtdesign-cookie-consent'); ?></li>
        </ol>
        <p class="description">
            <?php esc_html_e('Visitors who must give consent before tracking also see a Decline button. If no page is selected, the popup keeps the standard three buttons.', 'maxtdesign-cookie-consent'); ?>
        </p>
        <?php
    }

    /**
     * Render cookie settings page picker
     *
     * @since 1.11.0
     */
    public function render_field_manage_page_id(): void {
        $settings = get_option(self::OPTION_NAME, mdcc_default_settings());
        $current  = is_array($settings) && isset($settings['manage_page_id']) ? absint($settings['manage_page_id']) : 0;

        wp_dropdown_pages(
            array(
                'name'              => esc_attr(self::OPTION_NAME) . '[manage_page_id]',
                'id'                => 'mdcc-manage-page-id',
                'selected'          => $current, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- integer, escaped by wp_dropdown_pages().
                'show_option_none'  => esc_html__('None', 'maxtdesign-cookie-consent'),
                'option_none_value' => '0',
                'post_status'       => 'publish',
            )
        );
        ?>
        <p class="description">
            <?php
            printf(
                /* translators: %s: the shortcode [mdcc_manage_consent] */
                esc_html__('The published page that holds %s. The Compact layout links to it.', 'maxtdesign-cookie-consent'),
                '<code>[mdcc_manage_consent]</code>'
            );
            ?>
        </p>
        <?php
    }

    /**
     * Render the four button label fields
     *
     * @since 1.11.0
     */
    public function render_field_popup_labels(): void {
        $settings = get_option(self::OPTION_NAME, mdcc_default_settings());
        $fields   = array(
            'label_accept'    => __('Accept All', 'maxtdesign-cookie-consent'),
            'label_analytics' => __('Analytics Only', 'maxtdesign-cookie-consent'),
            'label_decline'   => __('Decline All', 'maxtdesign-cookie-consent'),
            'label_manage'    => __('Manage options', 'maxtdesign-cookie-consent'),
        );

        foreach ($fields as $key => $default_label) {
            $current = is_array($settings) && isset($settings[$key]) && is_string($settings[$key]) ? $settings[$key] : '';
            ?>
            <p>
                <label>
                    <input type="text"
                           name="<?php echo esc_attr(self::OPTION_NAME . '[' . $key . ']'); ?>"
                           value="<?php echo esc_attr($current); ?>"
                           placeholder="<?php echo esc_attr($default_label); ?>"
                           class="regular-text" />
                </label>
            </p>
            <?php
        }
        ?>
        <p class="description">
            <?php esc_html_e('Empty uses the default shown in grey, in the language of the site. Visitors in opt-out regions see the opt-out wording instead.', 'maxtdesign-cookie-consent'); ?>
        </p>
        <?php
    }

    /**
     * Render desktop width field
     *
     * @since 1.11.0
     */
    public function render_field_popup_desktop_width(): void {
        $settings = get_option(self::OPTION_NAME, mdcc_default_settings());
        $current  = absint($settings['popup_desktop_width'] ?? 100);
        ?>
        <select name="<?php echo esc_attr(self::OPTION_NAME); ?>[popup_desktop_width]" id="mdcc-popup-desktop-width">
            <?php foreach (MDCC_Popup_System::desktop_widths() as $width) : ?>
                <option value="<?php echo esc_attr((string) $width); ?>" <?php selected($current, $width); ?>>
                    <?php echo esc_html($width . '%'); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description">
            <?php esc_html_e('Width of the Top and Bottom banner on screens 1025 pixels and wider. The banner is centered. On tablets and phones it is always full width. The Center Modal keeps its own size.', 'maxtdesign-cookie-consent'); ?>
        </p>
        <?php
    }

    /**
     * Render one of the design colour fields
     *
     * @since 1.11.0
     * @param array<string, string> $args Field arguments; `key` is the setting key.
     */
    public function render_field_design_color($args): void {
        $allowed = array('popup_bg_color', 'popup_text_color', 'popup_button_text_color');
        $key     = isset($args['key']) && in_array($args['key'], $allowed, true) ? $args['key'] : '';

        if ('' === $key) {
            return;
        }

        $settings = get_option(self::OPTION_NAME, mdcc_default_settings());
        $current  = isset($settings[$key]) && is_string($settings[$key]) ? $settings[$key] : '';
        ?>
        <input type="text"
               name="<?php echo esc_attr(self::OPTION_NAME . '[' . $key . ']'); ?>"
               value="<?php echo esc_attr($current); ?>"
               class="mdcc-color-picker" />
        <p class="description">
            <?php esc_html_e('Empty keeps the color of the style preset.', 'maxtdesign-cookie-consent'); ?>
        </p>
        <?php
    }

    /**
     * Render corner radius field
     *
     * @since 1.11.0
     */
    public function render_field_popup_radius(): void {
        $settings = get_option(self::OPTION_NAME, mdcc_default_settings());
        $current  = isset($settings['popup_radius']) && is_numeric($settings['popup_radius']) ? (string) absint($settings['popup_radius']) : '';
        ?>
        <input type="number"
               name="<?php echo esc_attr(self::OPTION_NAME); ?>[popup_radius]"
               id="mdcc-popup-radius"
               value="<?php echo esc_attr($current); ?>"
               min="0"
               max="24"
               step="1"
               class="small-text" />
        <?php esc_html_e('pixels', 'maxtdesign-cookie-consent'); ?>
        <p class="description">
            <?php esc_html_e('0 to 24. Applies to the popup and its buttons. Empty keeps the corners of the style preset.', 'maxtdesign-cookie-consent'); ?>
        </p>
        <?php
    }

    /**
     * Render button font field
     *
     * @since 1.11.0
     */
    public function render_field_popup_inherit_font(): void {
        $settings = get_option(self::OPTION_NAME, mdcc_default_settings());
        ?>
        <label>
            <input type="checkbox"
                   name="<?php echo esc_attr(self::OPTION_NAME); ?>[popup_inherit_font]"
                   value="1"
                   <?php checked(!empty($settings['popup_inherit_font'])); ?> />
            <?php esc_html_e('Use the theme font on the popup buttons', 'maxtdesign-cookie-consent'); ?>
        </label>
        <p class="description">
            <?php esc_html_e('Off by default: buttons use the browser font, as in earlier versions.', 'maxtdesign-cookie-consent'); ?>
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
     * Render opt-out region popup title field
     *
     * @since 1.10.0
     */
    public function render_field_popup_title_optout(): void {
        $settings = get_option(self::OPTION_NAME, mdcc_default_settings());
        $defaults = mdcc_default_settings();
        $current  = !empty($settings['popup_title_optout']) ? $settings['popup_title_optout'] : $defaults['popup_title_optout'];
        ?>
        <input type="text"
               name="<?php echo esc_attr(self::OPTION_NAME); ?>[popup_title_optout]"
               value="<?php echo esc_attr($current); ?>"
               class="regular-text" />
        <p class="description">
            <?php esc_html_e('Headline shown instead of the Popup Title to visitors in an opt-out region (Consent Model set to Regional or Opt-out). Not used under the Opt-in model.', 'maxtdesign-cookie-consent'); ?>
        </p>
        <?php
    }

    /**
     * Render opt-out region popup message field
     *
     * @since 1.10.0
     */
    public function render_field_popup_message_optout(): void {
        $settings = get_option(self::OPTION_NAME, mdcc_default_settings());
        $defaults = mdcc_default_settings();
        $current  = !empty($settings['popup_message_optout']) ? $settings['popup_message_optout'] : $defaults['popup_message_optout'];
        ?>
        <textarea name="<?php echo esc_attr(self::OPTION_NAME); ?>[popup_message_optout]"
                  rows="3"
                  class="large-text"><?php echo esc_textarea($current); ?></textarea>
        <p class="description">
            <?php esc_html_e('Description shown instead of the Popup Message to visitors in an opt-out region. The buttons become "Got it" and "Opt out"; the Analytics Only button is hidden.', 'maxtdesign-cookie-consent'); ?>
        </p>
        <?php
    }

    /**
     * Render consent model select field
     *
     * @since 1.10.0
     */
    public function render_field_consent_model(): void {
        $current = MDCC_Consent_Manager::get_consent_model();
        ?>
        <select name="<?php echo esc_attr(self::OPTION_NAME); ?>[consent_model]" id="mdcc-consent-model">
            <?php foreach (MDCC_Consent_Manager::consent_models() as $value => $label) : ?>
                <option value="<?php echo esc_attr($value); ?>" <?php selected($current, $value); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description">
            <?php esc_html_e('Opt-in everywhere: nothing is tracked until the visitor accepts (GDPR; the default and the behavior of every earlier version). Regional: an opt-in popup for visitors in the EEA, UK and Switzerland; a "Do Not Sell or Share" opt-out notice for California (CCPA/CPRA is an opt-out law, so tracking is on by default there); no banner and implied consent everywhere else. Opt-out everywhere: implied consent for every visitor, shown the opt-out notice, who can opt out at any time.', 'maxtdesign-cookie-consent'); ?>
        </p>
        <p class="description">
            <?php esc_html_e('Regional mode uses Google Consent Mode\'s own region-specific defaults for tracking (Google resolves the visitor\'s region; nothing is looked up on your server, so pages stay cacheable) plus a browser time-zone heuristic to decide which banner to show. California is detected by the Pacific time zone, so visitors in Washington, Oregon and part of Nevada also see the (dismissible, harmless) opt-out notice. Visitors whose browser sends the Global Privacy Control signal are always treated as opt-in.', 'maxtdesign-cookie-consent'); ?>
        </p>
        <p class="description" style="color:#b32d2e;">
            <?php esc_html_e('You remain responsible for choosing the model that is lawful for your audience and jurisdiction. If in doubt, keep Opt-in everywhere.', 'maxtdesign-cookie-consent'); ?>
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
        <p class="description" style="color:#b32d2e;">
            <?php esc_html_e('Caution: EU regulators (EDPB, CNIL) have flagged re-prompting after a decline as approaching a "dark pattern" — a decline should be as easy to honor as an accept. Leave this off for GDPR-strict compliance; enable only where you have a lawful basis.', 'maxtdesign-cookie-consent'); ?>
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

    /**
     * Render WP Consent API bridge checkbox field
     *
     * When enabled and the free WP Consent API plugin is active, the plugin
     * mirrors each visitor choice onto the standard consent categories
     * (analytics -> statistics, ads -> marketing, functional always allowed) so
     * other consent-aware plugins on the site respect the same decision.
     *
     * @since 1.8.0
     */
    public function render_consent_api_bridge_field() {
        $settings = get_option(self::OPTION_NAME, mdcc_default_settings());
        // Default on for installs upgrading before this key existed.
        $value = !array_key_exists('consent_api_bridge', $settings) || !empty($settings['consent_api_bridge']);
        $api_active = function_exists('wp_has_consent');
        ?>
        <label>
            <input type="checkbox"
                   name="<?php echo esc_attr(self::OPTION_NAME); ?>[consent_api_bridge]"
                   value="1"
                   <?php checked($value, true); ?> />
            <?php esc_html_e('Share consent choices with the WordPress Consent API', 'maxtdesign-cookie-consent'); ?>
        </label>
        <p class="description">
            <?php
            esc_html_e(
                'When the free WP Consent API plugin is active, this bridges each visitor choice onto the standard WordPress consent categories so other consent-aware plugins (WooCommerce and others) respect the same decision. Analytics maps to "statistics", Ads maps to "marketing", and strictly-necessary "functional" cookies are always allowed. Has no effect unless the WP Consent API plugin is installed and active.',
                'maxtdesign-cookie-consent'
            );
            ?>
        </p>
        <p class="description">
            <?php if ($api_active) : ?>
                <strong style="color:#227122;">&#10003; <?php esc_html_e('WP Consent API plugin detected — the bridge is active.', 'maxtdesign-cookie-consent'); ?></strong>
            <?php else : ?>
                <strong style="color:#996800;">&#9432; <?php esc_html_e('WP Consent API plugin not detected. Install and activate it to enable the bridge; until then this setting has no effect and the plugin works exactly as before.', 'maxtdesign-cookie-consent'); ?></strong>
            <?php endif; ?>
        </p>
        <?php
    }
}

