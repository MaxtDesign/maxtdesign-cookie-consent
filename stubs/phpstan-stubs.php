<?php
/**
 * Minimal WordPress + WP Consent API stubs for PHPStan analysis of this plugin.
 *
 * These mirror only the surface maxtdesign-cookie-consent actually touches and
 * are NEVER loaded at runtime — WordPress (and, for the consent bridge, the
 * separate WP Consent API plugin) provide the real implementations. Signatures
 * are kept permissive to avoid false positives at level 8; add a symbol here
 * when the plugin starts calling a new WP/consent-API function.
 *
 * Not shipped (excluded by .distignore + bin/build-zip.php's allow-list).
 *
 * @phpstan-ignore-file
 */

declare(strict_types=1);

/* -------------------------------------------------------------------------
 * Core constants
 * ---------------------------------------------------------------------- */
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', '/wordpress/' ); }
if ( ! defined( 'WPINC' ) ) { define( 'WPINC', 'wp-includes' ); }
if ( ! defined( 'WP_DEBUG' ) ) { define( 'WP_DEBUG', false ); }
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { define( 'WP_UNINSTALL_PLUGIN', 'maxtdesign-cookie-consent/maxtdesign-cookie-consent.php' ); }
if ( ! defined( 'MINUTE_IN_SECONDS' ) ) { define( 'MINUTE_IN_SECONDS', 60 ); }
if ( ! defined( 'HOUR_IN_SECONDS' ) ) { define( 'HOUR_IN_SECONDS', 3600 ); }
if ( ! defined( 'DAY_IN_SECONDS' ) ) { define( 'DAY_IN_SECONDS', 86400 ); }
if ( ! defined( 'WEEK_IN_SECONDS' ) ) { define( 'WEEK_IN_SECONDS', 604800 ); }

/* -------------------------------------------------------------------------
 * This plugin's own constants (defined via define() in the main file, which
 * PHPStan does not resolve cross-file). Declared here so class-file references
 * resolve; runtime values come from the main plugin file.
 * ---------------------------------------------------------------------- */
if ( ! defined( 'MDCC_VERSION' ) ) { define( 'MDCC_VERSION', '0.0.0' ); }
if ( ! defined( 'MDCC_PLUGIN_FILE' ) ) { define( 'MDCC_PLUGIN_FILE', '' ); }
if ( ! defined( 'MDCC_PLUGIN_DIR' ) ) { define( 'MDCC_PLUGIN_DIR', '' ); }
if ( ! defined( 'MDCC_PLUGIN_URL' ) ) { define( 'MDCC_PLUGIN_URL', '' ); }
if ( ! defined( 'MDCC_PLUGIN_BASENAME' ) ) { define( 'MDCC_PLUGIN_BASENAME', '' ); }
if ( ! defined( 'MDCC_TEXT_DOMAIN' ) ) { define( 'MDCC_TEXT_DOMAIN', 'maxtdesign-cookie-consent' ); }

/* -------------------------------------------------------------------------
 * Hooks / plugin API
 * ---------------------------------------------------------------------- */
function add_action( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): bool { return true; }
function add_filter( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): bool { return true; }
function remove_action( string $hook, callable $callback, int $priority = 10 ): bool { return true; }
function remove_filter( string $hook, callable $callback, int $priority = 10 ): bool { return true; }
function do_action( string $hook, mixed ...$args ): void {}
function apply_filters( string $hook, mixed $value, mixed ...$args ): mixed { return $value; }
function did_action( string $hook ): int { return 0; }
function doing_action( ?string $hook = null ): bool { return false; }
function has_action( string $hook, callable|false $callback = false ): bool|int { return false; }
function has_filter( string $hook, callable|false $callback = false ): bool|int { return false; }
function register_activation_hook( string $file, callable $callback ): void {}
function register_deactivation_hook( string $file, callable $callback ): void {}
function register_uninstall_hook( string $file, callable $callback ): void {}

/* -------------------------------------------------------------------------
 * Options / settings API
 * ---------------------------------------------------------------------- */
function get_option( string $option, mixed $default_value = false ): mixed { return $default_value; }
function add_option( string $option, mixed $value = '', string $deprecated = '', string|bool $autoload = 'yes' ): bool { return true; }
function update_option( string $option, mixed $value, string|bool|null $autoload = null ): bool { return true; }
function delete_option( string $option ): bool { return true; }
function register_setting( string $option_group, string $option_name, array|callable $args = array() ): void {}
function unregister_setting( string $option_group, string $option_name ): void {}
function add_settings_section( string $id, string $title, ?callable $callback, string $page, array $args = array() ): void {}
function add_settings_field( string $id, string $title, callable $callback, string $page, string $section = 'default', array $args = array() ): void {}
function do_settings_sections( string $page ): void {}
function settings_fields( string $option_group ): void {}
function add_options_page( string $page_title, string $menu_title, string $capability, string $menu_slug, callable|string $callback = '', int|float|null $position = null ): string|false { return false; }
function add_settings_link( mixed ...$args ): mixed { return null; }

/* -------------------------------------------------------------------------
 * Escaping / i18n / formatting
 * ---------------------------------------------------------------------- */
function esc_attr( string $text ): string { return $text; }
function esc_html( string $text ): string { return $text; }
function esc_textarea( string $text ): string { return $text; }
function esc_url( string $url, ?array $protocols = null, string $_context = 'display' ): string { return $url; }
function esc_url_raw( string $url, ?array $protocols = null ): string { return $url; }
function esc_js( string $text ): string { return $text; }
function esc_attr__( string $text, string $domain = 'default' ): string { return $text; }
function esc_html__( string $text, string $domain = 'default' ): string { return $text; }
function esc_attr_e( string $text, string $domain = 'default' ): void {}
function esc_html_e( string $text, string $domain = 'default' ): void {}
function esc_html_x( string $text, string $context, string $domain = 'default' ): string { return $text; }
function __( string $text, string $domain = 'default' ): string { return $text; }
function _e( string $text, string $domain = 'default' ): void {}
function _x( string $text, string $context, string $domain = 'default' ): string { return $text; }
function _n( string $single, string $plural, int $number, string $domain = 'default' ): string { return 1 === $number ? $single : $plural; }
function wp_kses( string $content, array $allowed_html, array $allowed_protocols = array() ): string { return $content; }
function wp_kses_post( string $content ): string { return $content; }
function sanitize_text_field( string $str ): string { return $str; }
function sanitize_textarea_field( string $str ): string { return $str; }
function sanitize_hex_color( string $color ): ?string { return $color; }
function sanitize_key( string $key ): string { return $key; }
function absint( mixed $maybeint ): int { return (int) abs( (float) $maybeint ); }
function checked( mixed $checked, mixed $current = true, bool $display = true ): string { return ''; }
function selected( mixed $selected, mixed $current = true, bool $display = true ): string { return ''; }
function disabled( mixed $disabled, mixed $current = true, bool $display = true ): string { return ''; }
function number_format_i18n( float $number, int $decimals = 0 ): string { return (string) $number; }

/* -------------------------------------------------------------------------
 * Scripts / styles enqueue
 * ---------------------------------------------------------------------- */
function wp_enqueue_script( string $handle, string $src = '', array $deps = array(), string|bool|null $ver = false, bool|array $args = array() ): void {}
function wp_enqueue_style( string $handle, string $src = '', array $deps = array(), string|bool|null $ver = false, string $media = 'all' ): void {}
function wp_register_script( string $handle, string|false $src, array $deps = array(), string|bool|null $ver = false, bool|array $args = array() ): bool { return true; }
function wp_register_style( string $handle, string|false $src, array $deps = array(), string|bool|null $ver = false, string $media = 'all' ): bool { return true; }
function wp_add_inline_script( string $handle, string $data, string $position = 'after' ): bool { return true; }
function wp_add_inline_style( string $handle, string $data ): bool { return true; }
function wp_localize_script( string $handle, string $object_name, array $l10n ): bool { return true; }
function wp_script_is( string $handle, string $status = 'enqueued' ): bool { return false; }
function wp_style_is( string $handle, string $status = 'enqueued' ): bool { return false; }
function wp_print_inline_script_tag( string $data, array $attributes = array() ): void {}
function wp_get_inline_script_tag( string $data, array $attributes = array() ): string { return ''; }
function wp_json_encode( mixed $data, int $options = 0, int $depth = 512 ): string|false { return ''; }

/* -------------------------------------------------------------------------
 * URLs / paths / site info
 * ---------------------------------------------------------------------- */
function plugin_dir_path( string $file ): string { return rtrim( dirname( $file ), '/\\' ) . '/'; }
function plugin_dir_url( string $file ): string { return 'https://example.com/wp-content/plugins/' . basename( dirname( $file ) ) . '/'; }
function plugins_url( string $path = '', string $plugin = '' ): string { return 'https://example.com/wp-content/plugins/' . ltrim( $path, '/' ); }
function plugin_basename( string $file ): string { return basename( dirname( $file ) ) . '/' . basename( $file ); }
function admin_url( string $path = '', string $scheme = 'admin' ): string { return 'https://example.com/wp-admin/' . ltrim( $path, '/' ); }
function home_url( string $path = '', ?string $scheme = null ): string { return 'https://example.com/' . ltrim( $path, '/' ); }
function site_url( string $path = '', ?string $scheme = null ): string { return 'https://example.com/' . ltrim( $path, '/' ); }
function get_bloginfo( string $show = '', string $filter = 'raw' ): string { return ''; }
function get_admin_page_title(): string { return ''; }
function trailingslashit( string $value ): string { return rtrim( $value, '/\\' ) . '/'; }
function untrailingslashit( string $value ): string { return rtrim( $value, '/\\' ); }

/* -------------------------------------------------------------------------
 * Context / capabilities / multisite
 * ---------------------------------------------------------------------- */
function current_user_can( string $capability, mixed ...$args ): bool { return true; }
function is_admin(): bool { return false; }
function is_page( int|string|array $page = '' ): bool { return false; }
function is_multisite(): bool { return false; }
function get_current_blog_id(): int { return 1; }
/**
 * @return array<int, mixed>
 */
function get_sites( array|string $args = array() ): array { return array(); }

/* -------------------------------------------------------------------------
 * Transients
 * ---------------------------------------------------------------------- */
function get_transient( string $transient ): mixed { return false; }
function set_transient( string $transient, mixed $value, int $expiration = 0 ): bool { return true; }
function delete_transient( string $transient ): bool { return true; }

/* -------------------------------------------------------------------------
 * Privacy / text domain
 * ---------------------------------------------------------------------- */
function wp_add_privacy_policy_content( string $plugin_name, string $policy_text ): void {}
function get_privacy_policy_url(): string { return ''; }
function load_plugin_textdomain( string $domain, string|false $deprecated = false, string|false $plugin_rel_path = false ): bool { return true; }

/* -------------------------------------------------------------------------
 * Shortcodes
 * ---------------------------------------------------------------------- */
function add_shortcode( string $tag, callable $callback ): void {}
function do_shortcode( string $content, bool $ignore_html = false ): string { return $content; }
/**
 * @param array<string, mixed> $defaults
 * @param array<string, mixed> $atts
 * @return array<string, mixed>
 */
function shortcode_atts( array $defaults, array $atts, string $shortcode = '' ): array { return array_merge( $defaults, $atts ); }

/* -------------------------------------------------------------------------
 * Misc
 * ---------------------------------------------------------------------- */
function wp_die( string|object $message = '', string|int $title = '', string|array|int $args = array() ): void {}
function wp_unslash( mixed $value ): mixed { return $value; }
function current_time( string $type, int|bool $gmt = 0 ): mixed { return 0; }
function WP_Filesystem( array|false $args = false, string|false $context = false, bool $allow_relaxed_file_ownership = false ): ?bool { return true; }
function submit_button( ?string $text = null, string $type = 'primary', string $name = 'submit', bool $wrap = true, string|array $other_attributes = '' ): void {}
function wpautop( string $text, bool $br = true ): string { return $text; }
function switch_to_blog( int $new_blog_id, bool $deprecated = false ): bool { return true; }
function restore_current_blog(): bool { return true; }

/* -------------------------------------------------------------------------
 * WP Consent API (provided by the separate WP Consent API plugin — the bridge
 * is guarded by function_exists(); stub so PHPStan can analyse the bridge path)
 * ---------------------------------------------------------------------- */
function wp_has_consent( string $category ): bool { return false; }
function wp_set_consent( string $category, string $value ): void {}
