<?php
/**
 * Consent popup template
 *
 * To change the markup, copy this file to
 * yourtheme/maxtdesign-cookie-consent/popup.php and edit the copy. A child
 * theme is checked before its parent. Sites that keep templates elsewhere can
 * return a path from the `mdcc_popup_template` filter.
 *
 * This output is rendered once and shared by every visitor through the page
 * cache. Do not print anything here that depends on who is visiting.
 *
 * CONTRACT. popup.js finds elements by the items below and by nothing else.
 * An override must keep all of them, or the buttons stop working:
 *
 * - the root element with the class `mdcc-popup`, printed with
 *   `style="display: none;"`
 * - `id="mdcc-popup-title"` and `id="mdcc-popup-message"`
 * - one control for each of `data-mdcc-action="accept-all"`,
 *   `data-mdcc-action="decline-all"` and `data-mdcc-action="analytics-only"`
 * - the close control with the class `mdcc-popup__close`
 * - the `mdcc_popup_before_actions` action, which add-ons print into
 *
 * Escape everything you print. Available in `$args`:
 *
 * - `classes`     string[] CSS classes for the root element
 * - `title`       string   popup title
 * - `message`     string   popup message
 * - `privacy_url` string   the site's privacy policy URL, or ''
 * - `labels`      array    `close`, `accept`, `accept_aria`, `analytics`,
 *                          `analytics_aria`, `decline`, `decline_aria`
 * - `settings`    array    the plugin settings
 *
 * When the plugin updates this file it raises the version below. The plugin's
 * settings screen tells you when your copy is older.
 *
 * @package MaxtDesign_Cookie_Consent
 * @since   1.11.0
 * @version 1.11.0
 *
 * @var array<string, mixed> $args
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
        <div class="<?php echo esc_attr(implode(' ', $args['classes'])); ?>" 
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
                            aria-label="<?php echo esc_attr($args['labels']['close']); ?>">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    
                    <h2 id="mdcc-popup-title" class="mdcc-popup__title">
                        <?php echo esc_html($args['title']); ?>
                    </h2>
                    
                    <p id="mdcc-popup-message" class="mdcc-popup__message">
                        <?php echo esc_html($args['message']); ?>
                    </p>

                    <?php
                    // Link the site's designated Privacy Policy (Settings → Privacy)
                    // when one is set. A consent notice should point users to the
                    // policy that explains the cookies; renders nothing otherwise.
                    if ($args['privacy_url']) :
                    ?>
                    <p class="mdcc-popup__privacy-link">
                        <a href="<?php echo esc_url($args['privacy_url']); ?>">
                            <?php esc_html_e('Privacy Policy', 'maxtdesign-cookie-consent'); ?>
                        </a>
                    </p>
                    <?php endif; ?>

                    <?php
                    /**
                     * Fires inside the popup, before the action buttons.
                     *
                     * Add-ons (e.g. Pro granular consent) echo their own markup
                     * here — e.g. per-category toggle checkboxes. Callbacks are
                     * responsible for escaping their own output.
                     *
                     * @since 1.9.0
                     * @param array $settings Current plugin settings.
                     */
                    do_action('mdcc_popup_before_actions', $args['settings']);
                    ?>

                    <div class="mdcc-popup__actions">
                        <button type="button" 
                                class="mdcc-popup__button mdcc-popup__button--primary" 
                                data-mdcc-action="accept-all"
                                aria-label="<?php echo esc_attr($args['labels']['accept_aria']); ?>">
                            <?php echo esc_html($args['labels']['accept']); ?>
                        </button>
                        
                        <button type="button" 
                                class="mdcc-popup__button mdcc-popup__button--secondary" 
                                data-mdcc-action="analytics-only"
                                aria-label="<?php echo esc_attr($args['labels']['analytics_aria']); ?>">
                            <?php echo esc_html($args['labels']['analytics']); ?>
                        </button>
                        
                        <button type="button" 
                                class="mdcc-popup__button mdcc-popup__button--tertiary" 
                                data-mdcc-action="decline-all"
                                aria-label="<?php echo esc_attr($args['labels']['decline_aria']); ?>">
                            <?php echo esc_html($args['labels']['decline']); ?>
                        </button>
                    </div>
                    
                </div>
            </div>
        </div>
        <?php
// The indentation and the trailing spaces above are kept on purpose. They make
// the output match versions before 1.11.0 byte for byte.
