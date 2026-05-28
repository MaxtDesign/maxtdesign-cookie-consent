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
        dispatchChangeEvent(s);
    }

    window.mdccConsent = {
        current: function () {
            return readState();
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
            debug('Consent manager initialized with stored state:', stored);
        } else {
            debug('No stored consent — letting inline default state ride');
        }
    }

    init();

})(window, document);
