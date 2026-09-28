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
 * - one control for each of `data-mdcc-action="accept-all"` and
 *   `data-mdcc-action="decline-all"`, and in the standard layout also
 *   `data-mdcc-action="analytics-only"`
 * - in the Compact layout, the link with the class `mdcc-popup__manage`.
 *   popup.js gives it the opt-out wording and hides the decline control for
 *   visitors who are tracked by default and may opt out
 * - the close control with the class `mdcc-popup__close`
 * - the `mdcc_popup_before_actions` action, which add-ons print into
 *
 * Escape everything you print. Available in `$args`:
 *
 * - `classes`     string[] CSS classes for the root element. Print all of
 *                          them: the stylesheet sizes the title and places
 *                          the popup through the style and position classes.
 *                          Keep the control that must come last in the tab
 *                          order, Accept, last in the markup
 * - `title`       string   popup title
 * - `message`     string   popup message
 * - `privacy_url` string   the site's privacy policy URL, or ''
 * - `buttons`     string   `standard` or `compact`. It is `compact` only when
 *                          the site has a published cookie settings page
 * - `manage_url`  string   address of the cookie settings page, or ''
 * - `labels`      array    `close`, `accept`, `accept_aria`, `analytics`,
 *                          `analytics_aria`, `decline`, `decline_aria`,
 *                          `manage`
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
<?php if ('compact' === $args['buttons']) : ?>
                        <a class="mdcc-popup__button mdcc-popup__button--tertiary mdcc-popup__manage"
                           href="<?php echo esc_url($args['manage_url']); ?>">
                            <?php echo esc_html($args['labels']['manage']); ?>
                        </a>

                        <button type="button"
                                class="mdcc-popup__button mdcc-popup__button--secondary"
                                data-mdcc-action="decline-all"
                                aria-label="<?php echo esc_attr($args['labels']['decline_aria']); ?>">
                            <?php echo esc_html($args['labels']['decline']); ?>
                        </button>

                        <button type="button"
                                class="mdcc-popup__button mdcc-popup__button--primary"
                                data-mdcc-action="accept-all"
                                aria-label="<?php echo esc_attr($args['labels']['accept_aria']); ?>">
                            <?php echo esc_html($args['labels']['accept']); ?>
                        </button>
<?php else : ?>
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
<?php endif; ?>
                    </div>
                    
                </div>
            </div>
        </div>
        <?php
// The indentation and the trailing spaces above are kept on purpose. They make
// the output match versions before 1.11.0 byte for byte.
