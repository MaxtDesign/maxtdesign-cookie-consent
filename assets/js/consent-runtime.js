(function (window, document) {
    'use strict';

    if (window.mdccConsent) {
        return;
    }

    var config = window.mdccConfig || {
        storageKey: 'mdcc_consent',
        debug: false
    };

    function debug(msg, data) {
        if (config.debug && console && console.log) {
            console.log(`[MDCC] ${msg}`, data || '');
        }
    }

    function readStoredState() {
        try {
            var stored = localStorage.getItem(config.storageKey);
            if (stored) {
                var parsed = JSON.parse(stored);
                if (typeof parsed === 'object' &&
                    typeof parsed.analytics === 'boolean' &&
                    typeof parsed.ads === 'boolean') {
                    return parsed;
                }
            }
        } catch (e) {
            debug('Error reading consent state:', e);
        }
        return null;
    }

    function readState() {
        return readStoredState() || { analytics: false, ads: false };
    }

    function writeState(state) {
        try {
            localStorage.setItem(config.storageKey, JSON.stringify(state));
            debug('Consent state saved:', state);
        } catch (e) {
            debug('Error saving consent state:', e);
        }
    }

    function updateGCM(state) {
        if (typeof gtag !== 'function') {
            debug('gtag not available, skipping GCM update');
            return;
        }

        try {
            gtag('consent', 'update', {
                'analytics_storage': state.analytics ? 'granted' : 'denied',
                'ad_storage': state.ads ? 'granted' : 'denied',
                'ad_user_data': state.ads ? 'granted' : 'denied',
                'ad_personalization': state.ads ? 'granted' : 'denied'
            });

            debug('GCM v2 updated:', {
                analytics_storage: state.analytics ? 'granted' : 'denied',
                ad_storage: state.ads ? 'granted' : 'denied'
            });
        } catch (e) {
            debug('Error updating GCM:', e);
        }
    }

    // Mirror the visitor's choice onto the WordPress Consent API so other
    // consent-aware plugins (WooCommerce, etc.) respect the same decision.
    // One-way by design: this plugin is the consent provider / source of truth.
    //
    //   functional  -> always 'allow' (strictly-necessary; consent-exempt)
    //   statistics  -> analytics choice
    //   marketing   -> ads choice
    //
    // Guarded twice: config.consentApi is only true when the admin toggle is on
    // AND the WP Consent API plugin is active, and we still confirm the function
    // exists at call time (it is defined by wp-consent-api.js). Absent either,
    // this is a no-op and the plugin keeps its normal client-only behavior.
    function syncConsentAPI(state) {
        if (!config.consentApi || typeof window.wp_set_consent !== 'function') {
            return;
        }
        try {
            window.wp_set_consent('functional', 'allow');
            window.wp_set_consent('statistics', state.analytics ? 'allow' : 'deny');
            window.wp_set_consent('marketing', state.ads ? 'allow' : 'deny');
            debug('WP Consent API synced:', state);
        } catch (e) {
            debug('Error syncing WP Consent API:', e);
        }
    }

    function dispatchChangeEvent(state) {
        try {
            var event = new CustomEvent('mdcc:changed', {
                detail: state,
                bubbles: true,
                cancelable: false
            });
            document.dispatchEvent(event);
            debug('Consent change event dispatched:', state);
        } catch (e) {
            debug('Error dispatching event:', e);
        }
    }

    function updateConsent(s) {
        writeState(s);
        updateGCM(s);
        syncConsentAPI(s);
        dispatchChangeEvent(s);
    }

    window.mdccConsent = {
        current: function () {
            return readState();
        },

        // Returns the raw stored consent object, or null if the visitor has
        // never made a choice. Unlike current(), this distinguishes "no choice
        // yet" (null) from "declined all" ({analytics:false, ads:false}) — the
        // popup needs that distinction to decide whether to show on load.
        stored: function () {
            return readStoredState();
        },

        acceptAll: function () {
            updateConsent({
                analytics: true,
                ads: true
            });
        },

        acceptAnalyticsOnly: function () {
            updateConsent({
                analytics: true,
                ads: false
            });
        },

        declineAll: function () {
            updateConsent({
                analytics: false,
                ads: false
            });
        },

        reset: function () {
            try {
                localStorage.removeItem(config.storageKey);
                // Also clear the "popup shown" cookie so the popup can reappear
                // after a user-initiated reset. Without this, reset is a no-op
                // visually (state cleared, but popup stays hidden).
                document.cookie = 'mdcc_popup_shown=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; SameSite=Lax';
                debug('Consent state reset');
            } catch (e) {
                debug('Error resetting consent:', e);
            }

            var d = { analytics: false, ads: false };
            updateGCM(d);
            syncConsentAPI(d);
            dispatchChangeEvent(d);
        }
    };

    function init() {
        // Only fire gtag('consent', 'update', ...) if the user has previously
        // made a choice. On a first-time visit there is no stored state, so we
        // leave the inline default state (denied + wait_for_update:500) in
        // place — this preserves GCM v2's wait window and avoids emitting a
        // redundant update:denied that short-circuits cookieless pings.
        var stored = readStoredState();
        if (stored) {
            updateGCM(stored);
            // Re-assert the API cookie on load so server-side wp_has_consent()
            // stays correct even on full-page-cached responses.
            syncConsentAPI(stored);
            debug('Consent manager initialized with stored state:', stored);
        } else {
            debug('No stored consent — letting inline default state ride');
        }
    }

    init();

})(window, document);
