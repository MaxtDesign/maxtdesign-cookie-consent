/**
 * MaxtDesign Cookie Consent — popup behavior
 *
 * Source of truth for the consent popup's client-side logic. This file is
 * minified to popup.min.js by `npm run build:popup-js` (terser) and the result
 * is inlined into the page footer by MDCC_Popup_System::get_popup_javascript().
 *
 * It is inlined (not enqueued as a URL) on purpose: the consent gate is
 * render-critical and show-once, so an inline block avoids an extra HTTP
 * request that could never benefit from caching (the popup stops emitting the
 * moment a choice is stored). Edit THIS file, never the generated .min.js.
 *
 * @package MaxtDesign_Cookie_Consent
 * @since 1.6.0
 */
(function(window, document) {
    'use strict';

    var config = window.mdccPopupConfig || {};
    var popup = null;
    var closeBtn = null;
    var firstFocusable = null;
    var lastFocusable = null;

    /**
     * Read a cookie value by name (returns null if absent)
     */
    function getCookie(name) {
        var prefix = name + '=';
        var parts = document.cookie ? document.cookie.split(';') : [];
        for (var i = 0; i < parts.length; i++) {
            var part = parts[i].replace(/^\s+/, '');
            if (part.indexOf(prefix) === 0) {
                return part.substring(prefix.length);
            }
        }
        return null;
    }

    /**
     * Decide whether the popup should be shown on this page load.
     *
     * This mirrors the server-side should_show_popup() gate, but runs in the
     * browser so it stays correct under full-page caching. With a page cache,
     * the cached HTML (popup markup + this script) is served to every visitor
     * regardless of their cookies, so the PHP gate never re-evaluates — without
     * this client-side check the popup would reappear on every load even after
     * the visitor consented. Precedence:
     *
     *   - Stored consent exists (visitor made a choice):
     *       - declined all + "Re-prompt on Decline" ON  -> show (re-prompt)
     *       - otherwise (accepted something, or reprompt OFF) -> don't show
     *   - No stored consent:
     *       - "popup shown" cookie present (dismissed without choosing) -> don't show
     *       - otherwise (genuine first visit) -> show
     */
    function shouldShow() {
        var stored = (window.mdccConsent && typeof window.mdccConsent.stored === 'function')
            ? window.mdccConsent.stored()
            : null;

        if (stored) {
            var declinedAll = !stored.analytics && !stored.ads;
            // Re-prompt only when the visitor declined everything AND the site
            // owner enabled re-prompting. Any acceptance, or reprompt OFF, means
            // we honor the stored choice and stay hidden.
            return declinedAll && !!config.repromptDecline;
        }

        // No choice recorded yet — respect the "already shown" cookie so a
        // dismissal (close button / overlay) isn't undone by a cached page.
        if (config.cookieName && getCookie(config.cookieName) !== null) {
            return false;
        }

        return true;
    }

    /**
     * Set cookie to remember popup shown
     */
    function setCookie() {
        var days = config.cookieDuration || 7;
        var date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        var expires = 'expires=' + date.toUTCString();
        document.cookie = config.cookieName + '=1;' + expires + ';path=/;SameSite=Lax';
    }

    /**
     * Close popup
     */
    function closePopup() {
        if (!popup) return;

        popup.classList.add('mdcc-popup--closing');

        setTimeout(function() {
            popup.style.display = 'none';
            popup.classList.remove('mdcc-popup--closing');
            document.body.classList.remove('mdcc-popup-open');
        }, 300); // Match animation duration

        // Set cookie so popup doesn't show again
        setCookie();
    }

    /**
     * Re-show the popup once per session after a decline (if enabled).
     *
     * Driven directly from the popup's own Decline All button rather than a
     * global 'mdcc:changed' listener: that event also fires for reset() and the
     * [mdcc_manage_consent] shortcode, which would consume the once-per-session
     * one-shot without ever re-showing. We only arm here, and we only consume
     * the session flag on a *successful* re-show. The delay outlasts closePopup's
     * 300ms hide animation, and the display check confirms the visitor actually
     * dismissed the popup before we bring it back.
     */
    function scheduleRepromptOnDecline() {
        if (!config.repromptDecline) return;

        var repromptKey = 'mdcc_reprompted_session';
        if (sessionStorage.getItem(repromptKey)) return;

        setTimeout(function() {
            if (popup && popup.style.display === 'none') {
                sessionStorage.setItem(repromptKey, '1');
                popup.style.display = 'block';
                popup.classList.add('mdcc-popup--visible');
                document.body.classList.add('mdcc-popup-open');
                setupFocusTrap();
            }
        }, 2000);
    }

    /**
     * Handle consent button clicks
     */
    function handleConsentAction(action) {
        if (!window.mdccConsent) return;

        switch(action) {
            case 'accept-all':
                mdccConsent.acceptAll();
                break;
            case 'analytics-only':
                mdccConsent.acceptAnalyticsOnly();
                break;
            case 'decline-all':
                mdccConsent.declineAll();
                break;
        }

        closePopup();

        // Re-prompt only when the visitor declined everything via the popup.
        if (action === 'decline-all') {
            scheduleRepromptOnDecline();
        }
    }

    /**
     * Setup focus trap for accessibility
     */
    function setupFocusTrap() {
        if (!popup) return;

        var focusableElements = popup.querySelectorAll(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );

        if (focusableElements.length > 0) {
            firstFocusable = focusableElements[0];
            lastFocusable = focusableElements[focusableElements.length - 1];

            // Focus first button when popup opens
            setTimeout(function() {
                if (firstFocusable) {
                    firstFocusable.focus();
                }
            }, 100);
        }
    }

    /**
     * Handle keyboard navigation
     */
    function handleKeyboard(e) {
        if (!popup || popup.style.display === 'none') return;

        // ESC key closes popup
        if (e.key === 'Escape' || e.keyCode === 27) {
            e.preventDefault();
            closePopup();
            return;
        }

        // TAB key - trap focus within popup
        if (e.key === 'Tab' || e.keyCode === 9) {
            if (e.shiftKey) {
                // Shift + Tab
                if (document.activeElement === firstFocusable) {
                    e.preventDefault();
                    lastFocusable.focus();
                }
            } else {
                // Tab
                if (document.activeElement === lastFocusable) {
                    e.preventDefault();
                    firstFocusable.focus();
                }
            }
        }
    }

    /**
     * Initialize popup behavior
     */
    function init() {
        popup = document.querySelector('.mdcc-popup');
        if (!popup) return;

        // Bail before showing if the visitor has already consented (or was
        // already prompted). Keeps the popup hidden on cached pages where the
        // server-side gate couldn't run for this visitor.
        if (!shouldShow()) {
            popup.style.display = 'none';
            return;
        }

        closeBtn = popup.querySelector('.mdcc-popup__close');

        // Show popup with animation
        setTimeout(function() {
            popup.style.display = 'block';
            document.body.classList.add('mdcc-popup-open');
            setTimeout(function() {
                popup.classList.add('mdcc-popup--visible');
                setupFocusTrap();
            }, 10);
        }, 500); // Delay initial appearance

        // Close button
        if (closeBtn) {
            closeBtn.addEventListener('click', function(e) {
                e.preventDefault();
                closePopup();
            });
        }

        // Consent button clicks (event delegation)
        popup.addEventListener('click', function(e) {
            var btn = e.target.closest('[data-mdcc-action]');
            if (btn) {
                e.preventDefault();
                var action = btn.getAttribute('data-mdcc-action');
                handleConsentAction(action);
            }
        });

        // Keyboard navigation
        document.addEventListener('keydown', handleKeyboard);

    }

    // Initialize after full page load (ensures wp_footer has rendered popup HTML)
    // Using 'load' instead of 'DOMContentLoaded' because popup is rendered via wp_footer
    // which may execute after DOMContentLoaded, causing a race condition
    window.addEventListener('load', init);

})(window, document);
