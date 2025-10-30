/**
 * MaxtDesign Lean Consent - Runtime
 * 
 * Lightweight consent manager with Google Consent Mode v2 implementation.
 * localStorage-based, zero server requests, vanilla JavaScript.
 * 
 * @package MaxtDesign_Lean_Consent
 * @since 1.6.0
 */

(function(window, document) {
    'use strict';
    
    // Prevent duplicate initialization
    if (window.mdlcConsent) {
        return;
    }
    
    // Configuration from WordPress
    var config = window.mdlcConfig || {
        storageKey: 'mdlc_consent',
        debug: false
    };
    
    /**
     * Log debug messages if WP_DEBUG enabled
     */
    function debug(message, data) {
        if (config.debug && console && console.log) {
            console.log('[MDLC] ' + message, data || '');
        }
    }
    
    /**
     * Read consent state from localStorage
     * 
     * @return {Object} Consent state {analytics: boolean, ads: boolean}
     */
    function readState() {
        try {
            var stored = localStorage.getItem(config.storageKey);
            if (stored) {
                var parsed = JSON.parse(stored);
                // Validate structure
                if (typeof parsed === 'object' && 
                    typeof parsed.analytics === 'boolean' && 
                    typeof parsed.ads === 'boolean') {
                    return parsed;
                }
            }
        } catch (e) {
            debug('Error reading consent state:', e);
        }
        
        // Default: deny all (most privacy-friendly)
        return {
            analytics: false,
            ads: false
        };
    }
    
    /**
     * Write consent state to localStorage
     * 
     * @param {Object} state Consent state to save
     */
    function writeState(state) {
        try {
            localStorage.setItem(config.storageKey, JSON.stringify(state));
            debug('Consent state saved:', state);
        } catch (e) {
            debug('Error saving consent state:', e);
        }
    }
    
    /**
     * Update Google Consent Mode v2 signals
     * 
     * Implements all four required GCM v2 consent types:
     * - analytics_storage: Google Analytics
     * - ad_storage: Ad cookies
     * - ad_user_data: User data for ads
     * - ad_personalization: Personalized advertising
     * 
     * @param {Object} state Current consent state
     */
    function updateGCM(state) {
        // Only run if gtag exists (Google tag loaded)
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
    
    /**
     * Dispatch custom event for UI updates
     * 
     * Other components (popup, shortcodes) listen for this event
     * to update their display when consent changes.
     * 
     * @param {Object} state Current consent state
     */
    function dispatchChangeEvent(state) {
        try {
            var event = new CustomEvent('mdlc:changed', {
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
    
    /**
     * Update consent state and trigger all updates
     * 
     * @param {Object} newState New consent state
     */
    function updateConsent(newState) {
        writeState(newState);
        updateGCM(newState);
        dispatchChangeEvent(newState);
    }
    
    /**
     * Public API
     */
    window.mdlcConsent = {
        
        /**
         * Get current consent state
         * 
         * @return {Object} Current state {analytics: boolean, ads: boolean}
         */
        current: function() {
            return readState();
        },
        
        /**
         * Accept all tracking (analytics + ads)
         */
        acceptAll: function() {
            updateConsent({
                analytics: true,
                ads: true
            });
        },
        
        /**
         * Accept analytics only (no ads)
         */
        acceptAnalyticsOnly: function() {
            updateConsent({
                analytics: true,
                ads: false
            });
        },
        
        /**
         * Decline all tracking
         */
        declineAll: function() {
            updateConsent({
                analytics: false,
                ads: false
            });
        },
        
        /**
         * Reset consent (remove from storage)
         * 
         * Sets GCM to denied and dispatches change event.
         * Used by "Reset preferences" functionality.
         */
        reset: function() {
            try {
                localStorage.removeItem(config.storageKey);
                debug('Consent state reset');
            } catch (e) {
                debug('Error resetting consent:', e);
            }
            
            var defaultState = {
                analytics: false,
                ads: false
            };
            
            updateGCM(defaultState);
            dispatchChangeEvent(defaultState);
        }
    };
    
    /**
     * Initialize on page load
     * 
     * Reads current consent state and applies GCM signals immediately.
     * This ensures gtag knows the consent state before any tracking fires.
     */
    function init() {
        var currentState = readState();
        updateGCM(currentState);
        debug('Consent manager initialized with state:', currentState);
    }
    
    // Initialize immediately (script loads in footer, after gtag)
    init();
    
})(window, document);

