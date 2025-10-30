<?php
/**
 * Plugin Name: Site Consent (MU)
 * Description: Minimal, robust consent manager for GA4/Ads with WordPress shortcodes and Elementor popup wiring. Shortcodes render HTML only; CSS/JS are enqueued to avoid editor sanitization issues.
 * Author: MaxtDesign
 * Version: 1.5.3
 * Requires at least: 5.8
 * License: GPLv2 or later
 */

if (!defined('ABSPATH')) exit;

/** ------------------------------------------------------------------------
 * Constants
 * --------------------------------------------------------------------- */
define('SCA_OPTION_GROUP', 'sca_site_consent');
define('SCA_OPTION_NAME',  'sca_site_consent_opts');

/** ------------------------------------------------------------------------
 * Default options
 * --------------------------------------------------------------------- */
function sca_default_options() {
    return array(
        'popup_id'     => '',        // Elementor Popup ID for close/re-prompt
        'reprompt_on'  => '0',       // "1" to re-prompt once per session when declined
    );
}

/** ------------------------------------------------------------------------
 * Settings: register + page under Tools
 * --------------------------------------------------------------------- */
add_action('admin_init', function () {
    register_setting(SCA_OPTION_GROUP, SCA_OPTION_NAME, array(
        'type'              => 'array',
        'sanitize_callback' => 'sca_sanitize_options',
        'default'           => sca_default_options(),
    ));

    add_settings_section('sca_main', 'Consent Behavior', function () {
        echo '<p>Configure popup wiring and session re-prompt behavior. Shortcodes render HTML only; CSS/JS are enqueued globally.</p>';
    }, SCA_OPTION_GROUP);

    add_settings_field('popup_id', 'Elementor Popup ID', function () {
        $opts = sca_get_options();
        echo '<input type="number" min="1" step="1" name="'.esc_attr(SCA_OPTION_NAME).'[popup_id]" value="'.esc_attr($opts['popup_id']).'" class="regular-text" />';
        echo '<p class="description">Set if you use an Elementor popup for consent. Used for reliable close &amp; re-prompt.</p>';
    }, SCA_OPTION_GROUP, 'sca_main');

    add_settings_field('reprompt_on', 'Re-prompt Declined (once per session)', function () {
        $opts = sca_get_options();
        $checked = $opts['reprompt_on'] === '1' ? 'checked' : '';
        echo '<label><input type="checkbox" name="'.esc_attr(SCA_OPTION_NAME).'[reprompt_on]" value="1" '.$checked.' /> Enable</label>';
        echo '<p class="description">If enabled and the user declines, show the popup again once per session.</p>';
    }, SCA_OPTION_GROUP, 'sca_main');
});

add_action('admin_menu', function () {
    add_management_page(
        'Site Consent Settings',
        'Site Consent Settings',
        'manage_options',
        'sca-site-consent-settings',
        'sca_render_settings_page'
    );
});

function sca_sanitize_options($input) {
    $out = sca_get_options();
    if (isset($input['popup_id']))   $out['popup_id']   = preg_replace('/[^0-9]/', '', (string)$input['popup_id']);
    $out['reprompt_on'] = (!empty($input['reprompt_on']) && $input['reprompt_on'] === '1') ? '1' : '0';
    return $out;
}

function sca_get_options() {
    $opts = get_option(SCA_OPTION_NAME, array());
    if (!is_array($opts)) $opts = array();
    return wp_parse_args($opts, sca_default_options());
}

function sca_render_settings_page() {
    if (!current_user_can('manage_options')) return;
    ?>
    <div class="wrap">
      <h1>Site Consent Settings</h1>
      <form method="post" action="options.php">
        <?php
          settings_fields(SCA_OPTION_GROUP);
          do_settings_sections(SCA_OPTION_GROUP);
          submit_button('Save Settings');
        ?>
      </form>
    </div>
    <?php
}

/** ------------------------------------------------------------------------
 * Enqueue CSS/JS (frontend only)
 *   - Minimal styling for chips + container (no button styles; Elementor handles buttons)
 *   - JS runtime + UI wiring (no inline <script>/<style> in content!)
 * --------------------------------------------------------------------- */
add_action('wp_enqueue_scripts', function () {
    if (is_admin()) return;

    wp_register_style('sca-consent-inline', false);
    wp_enqueue_style('sca-consent-inline');

    $css = <<<CSS
.sca-consent-chip{display:inline-flex;align-items:center;gap:.4rem;padding:.25rem .5rem;border:1px solid #ccd0d4;border-radius:999px;background:#fff;font-size:.9375rem;line-height:1.2;}
.sca-on{color:#155724;border-color:#c3e6cb;background:#d4edda;}
.sca-off{color:#721c24;border-color:#f5c6cb;background:#f8d7da;}
.sca-manage-consent{border:1px solid #e2e8f0;padding:16px;border-radius:8px;background:#fff;}
.sca-manage-consent h2{margin-top:0;}
.sca-controls{display:flex;flex-wrap:wrap;gap:8px;margin:8px 0 12px;}
.sca-current{display:flex;align-items:center;flex-wrap:wrap;gap:8px;}
/* Reset link inherits global link styles */
.sca-reset{background:none;border:0;padding:0;text-decoration:underline;cursor:pointer;}
CSS;

    wp_add_inline_style('sca-consent-inline', $css);

    wp_register_script('sca-consent-inline', false, array(), null, true);
    wp_enqueue_script('sca-consent-inline');

    $opts = sca_get_options();
    $popup_id    = (string) $opts['popup_id'];
    $reprompt_on = $opts['reprompt_on'] === '1' ? '1' : '0';

    $js = <<<JS
(function(){
  // --- Light consent runtime (only define if not already present) ---
  if (!window.__Consent) {
    var KEY = 'sca_consent';
    function read(){ try{ var v = localStorage.getItem(KEY); return v ? JSON.parse(v) : {analytics:false, ads:false}; }catch(e){ return {analytics:false, ads:false}; } }
    function write(st){ try{ localStorage.setItem(KEY, JSON.stringify(st)); }catch(e){} }
    function dispatch(st){ try{ document.dispatchEvent(new CustomEvent('consent:changed', { detail: st })); }catch(e){} }
    function updateGCM(st){
      try{
        if (typeof gtag === 'function') {
          gtag('consent', 'update', {
            'analytics_storage': st.analytics ? 'granted' : 'denied',
            'ad_storage':       st.ads       ? 'granted' : 'denied',
            'ad_user_data':     st.ads       ? 'granted' : 'denied',
            'ad_personalization': st.ads     ? 'granted' : 'denied'
          });
        }
      }catch(e){}
    }
    window.__Consent = {
      current: function(){ return read(); },
      acceptAll: function(){ var st = {analytics:true, ads:true}; write(st); updateGCM(st); dispatch(st); },
      acceptAnalyticsOnly: function(){ var st = {analytics:true, ads:false}; write(st); updateGCM(st); dispatch(st); },
      declineAll: function(){ var st = {analytics:false, ads:false}; write(st); updateGCM(st); dispatch(st); },
      reset: function(){ try{ localStorage.removeItem(KEY); }catch(e){} var st = {analytics:false, ads:false}; updateGCM(st); dispatch(st); }
    };
    (function(){ var st = read(); updateGCM(st); })();
  }

  // --- UI: update chips ---
  function updateChips(){
    try{
      var st = (window.__Consent && window.__Consent.current) ? window.__Consent.current() : {analytics:false, ads:false};
      var a = document.querySelector('.sca-analytics-chip');
      var d = document.querySelector('.sca-ads-chip');
      if (a){
        a.classList.remove('sca-on','sca-off');
        a.classList.add(st.analytics ? 'sca-on' : 'sca-off');
        var as = a.querySelector('strong'); if (as) as.textContent = st.analytics ? 'On' : 'Off';
      }
      if (d){
        d.classList.remove('sca-on','sca-off');
        d.classList.add(st.ads ? 'sca-on' : 'sca-off');
        var ds = d.querySelector('strong'); if (ds) ds.textContent = st.ads ? 'On' : 'Off';
      }
    }catch(e){}
  }
  document.addEventListener('consent:changed', updateChips);
  if (document.readyState === 'complete') updateChips(); else window.addEventListener('load', updateChips);

  // --- UI: delegated click handling ---
  document.addEventListener('click', function(e){
    var el = e.target.closest('.sca-accept, .sca-analytics, .sca-decline, .sca-reset');
    if (!el) return;
    if (document.body && document.body.classList.contains('elementor-editor-active')) return; // skip editor

    e.preventDefault();
    try{
      if (el.classList.contains('sca-reset')) {
        if (window.__Consent && __Consent.reset) __Consent.reset();
        return;
      }
      if (el.classList.contains('sca-accept')) {
        if (window.__Consent && __Consent.acceptAll) __Consent.acceptAll();
        closePopupIfPresent();
        return;
      }
      if (el.classList.contains('sca-analytics')) {
        if (window.__Consent && __Consent.acceptAnalyticsOnly) __Consent.acceptAnalyticsOnly();
        closePopupIfPresent();
        return;
      }
      if (window.__Consent && __Consent.declineAll) {
        __Consent.declineAll();
        closePopupIfPresent();
      }
    }catch(ex){}
  }, true);

  // --- Popup close by ID (double rAF) ---
  var POPUP_ID = '{$popup_id}';
  function closePopupIfPresent(){
    try{
      if (!POPUP_ID) return;
      var tryClose = function(){
        try{
          if (window.elementorProFrontend && elementorProFrontend.modules && elementorProFrontend.modules.popup){
            elementorProFrontend.modules.popup.closePopup({ id: Number(POPUP_ID) });
          } else {
            var sel = '[data-elementor-id="'+POPUP_ID+'"] .dialog-close-button, [data-elementor-id="'+POPUP_ID+'"] .eicon-close';
            var btn = document.querySelector(sel);
            if (btn) btn.click();
          }
        }catch(e){}
      };
      requestAnimationFrame(function(){ requestAnimationFrame(tryClose); });
    }catch(e){}
  }

  // --- Re-prompt declined once per session ---
  var REPROMPT_ON = {$reprompt_on};
  if (REPROMPT_ON && POPUP_ID) {
    try{
      var SS_KEY = 'sca_reprompted_'+String(POPUP_ID);
      function maybeReprompt(){
        try{
          if (document.body && document.body.classList.contains('elementor-editor-active')) return;
          var st = (window.__Consent && __Consent.current) ? __Consent.current() : {analytics:false, ads:false};
          if (!st.analytics && !st.ads) {
            if (!sessionStorage.getItem(SS_KEY)) {
              var open = function(){
                try{
                  if (window.elementorProFrontend && elementorProFrontend.modules && elementorProFrontend.modules.popup){
                    elementorProFrontend.modules.popup.showPopup({ id: Number(POPUP_ID) });
                    sessionStorage.setItem(SS_KEY, '1');
                  }
                }catch(e){}
              };
              requestAnimationFrame(function(){ requestAnimationFrame(open); });
            }
          }
        }catch(e){}
      }
      if (document.readyState === 'complete') maybeReprompt(); else window.addEventListener('load', maybeReprompt);
    }catch(e){}
  }

})();
JS;

    wp_add_inline_script('sca-consent-inline', $js);
});

/** ------------------------------------------------------------------------
 * Shortcodes (HTML-only output; JS/CSS are enqueued above)
 * - Reset is a plain link .sca-reset (inherits Elementor link styles)
 * - Other actions use Elementor global button classes
 * --------------------------------------------------------------------- */
function sca_consent_status_shortcode() {
    return '<span class="sca-consent-chip sca-analytics-chip" aria-live="polite">Analytics: <strong>Checking…</strong></span> ' .
           '<span class="sca-consent-chip sca-ads-chip" aria-live="polite">Ads: <strong>Checking…</strong></span>';
}
add_shortcode('sca_consent_status', 'sca_consent_status_shortcode');

function sca_manage_consent_shortcode($atts = array()) {
    $a = shortcode_atts(array(
        'title' => 'Your Privacy Choices',
        'desc'  => 'Choose how we use cookies and similar technologies.'
    ), $atts, 'sca_manage_consent');

    ob_start(); ?>
    <div class="sca-manage-consent">
      <h3><?php echo esc_html($a['title']); ?></h3>
      <p><?php echo esc_html($a['desc']); ?></p>

      <div class="sca-controls">
        <a href="#" class="elementor-button elementor-button-link sca-accept" role="button" aria-label="Accept all">Accept all</a>
        <a href="#" class="elementor-button elementor-button-link sca-analytics" role="button" aria-label="Enable analytics only">Analytics only</a>
        <a href="#" class="elementor-button elementor-button-link sca-decline" role="button" aria-label="Decline all">Decline</a>
        <a href="#" class="sca-reset" aria-label="Reset preferences">Reset preferences</a>
      </div>

      <div class="sca-current">
        <span class="sca-consent-chip sca-analytics-chip" aria-live="polite">Analytics: <strong>Checking…</strong></span>
        <span class="sca-consent-chip sca-ads-chip" aria-live="polite">Ads: <strong>Checking…</strong></span>
      </div>
    </div>
    <?php
    return trim(ob_get_clean());
}
add_shortcode('sca_manage_consent', 'sca_manage_consent_shortcode');
