# MaxtDesign Cookie Consent - JavaScript API Documentation

## Overview

Lightweight consent manager with Google Consent Mode v2 implementation. localStorage-based, zero server requests, vanilla JavaScript.

**Package:** MaxtDesign_Cookie_Consent  
**Since:** 1.6.0

---

## Configuration

The consent manager reads configuration from `window.mdccConfig`:

```javascript
window.mdccConfig = {
    storageKey: 'mdcc_consent',  // localStorage key for consent data
    debug: false                  // Enable debug logging
};
```

---

## Public API

The consent manager exposes a global object `window.mdccConsent` with the following methods:

### `current()`

Get current consent state.

**Returns:** `{Object}` Current state `{analytics: boolean, ads: boolean}`

**Example:**
```javascript
const state = window.mdccConsent.current();
console.log(state.analytics); // true or false
console.log(state.ads);       // true or false
```

---

### `stored()`

Get the raw stored consent choice, or `null` if the visitor has never made one.

**Returns:** `{Object|null}` Stored state `{analytics: boolean, ads: boolean}`, or `null` when nothing valid is stored.

**Behavior:**
- Reads and validates the localStorage entry (same validation as `current()`).
- Unlike `current()`, does **not** fall back to a default — returns `null` when no choice exists.
- Lets callers distinguish "no choice yet" (`null`) from "declined all" (`{analytics: false, ads: false}`). The popup uses this to decide whether to display on load.

**Example:**
```javascript
const choice = window.mdccConsent.stored();
if (choice === null) {
    // First-time visitor — no consent decision recorded yet
}
```

---

### `acceptAll()`

Accept all tracking (analytics + ads).

**Returns:** `void`

**Behavior:**
- Sets `analytics: true` and `ads: true`
- Saves to localStorage
- Updates Google Consent Mode v2 signals
- Dispatches `mdcc:changed` event

**Example:**
```javascript
window.mdccConsent.acceptAll();
```

---

### `acceptAnalyticsOnly()`

Accept analytics only (no ads).

**Returns:** `void`

**Behavior:**
- Sets `analytics: true` and `ads: false`
- Saves to localStorage
- Updates Google Consent Mode v2 signals
- Dispatches `mdcc:changed` event

**Example:**
```javascript
window.mdccConsent.acceptAnalyticsOnly();
```

---

### `declineAll()`

Decline all tracking.

**Returns:** `void`

**Behavior:**
- Sets `analytics: false` and `ads: false`
- Saves to localStorage
- Updates Google Consent Mode v2 signals
- Dispatches `mdcc:changed` event

**Example:**
```javascript
window.mdccConsent.declineAll();
```

---

### `reset()`

Reset consent (remove from storage).

**Returns:** `void`

**Behavior:**
- Removes consent from localStorage
- Sets GCM to denied (all false)
- Dispatches `mdcc:changed` event with default state
- Used by "Reset preferences" functionality

**Example:**
```javascript
window.mdccConsent.reset();
```

---

## Internal Functions

### `readState()`

Read consent state from localStorage.

**Returns:** `{Object}` Consent state `{analytics: boolean, ads: boolean}`

**Behavior:**
- Reads from localStorage using `config.storageKey`
- Validates structure (must have boolean `analytics` and `ads` properties)
- Returns default `{analytics: false, ads: false}` if:
  - No stored data exists
  - Stored data is invalid
  - localStorage access fails

**Error Handling:**
- Catches and logs localStorage access errors
- Returns default state on any error

---

### `writeState(state)`

Write consent state to localStorage.

**Parameters:**
- `state` `{Object}` Consent state to save `{analytics: boolean, ads: boolean}`

**Returns:** `void`

**Error Handling:**
- Catches and logs localStorage write errors
- Fails silently (does not throw)

---

### `updateGCM(state)`

Update Google Consent Mode v2 signals.

**Parameters:**
- `state` `{Object}` Current consent state `{analytics: boolean, ads: boolean}`

**Returns:** `void`

**GCM v2 Consent Types:**
- `analytics_storage`: Google Analytics (based on `state.analytics`)
- `ad_storage`: Ad cookies (based on `state.ads`)
- `ad_user_data`: User data for ads (based on `state.ads`)
- `ad_personalization`: Personalized advertising (based on `state.ads`)

**Behavior:**
- Only runs if `gtag` function exists (Google tag loaded)
- Updates all four GCM v2 consent types
- Fails silently if `gtag` is not available

**Error Handling:**
- Checks for `gtag` availability before calling
- Catches and logs GCM update errors

---

### `dispatchChangeEvent(state)`

Dispatch custom event for UI updates.

**Parameters:**
- `state` `{Object}` Current consent state `{analytics: boolean, ads: boolean}`

**Returns:** `void`

**Event Details:**
- Event name: `mdcc:changed`
- Event type: `CustomEvent`
- Event detail: `state` object
- Bubbles: `true`
- Cancelable: `false`

**Purpose:**
Other components (popup, shortcodes) listen for this event to update their display when consent changes.

**Error Handling:**
- Catches and logs event dispatch errors

---

### `updateConsent(newState)`

Update consent state and trigger all updates.

**Parameters:**
- `newState` `{Object}` New consent state `{analytics: boolean, ads: boolean}`

**Returns:** `void`

**Behavior:**
Calls in sequence:
1. `writeState(newState)` - Save to localStorage
2. `updateGCM(newState)` - Update Google Consent Mode
3. `dispatchChangeEvent(newState)` - Notify UI components

---

### `init()`

Initialize on page load.

**Returns:** `void`

**Behavior:**
- Reads current consent state from localStorage
- Applies GCM signals immediately
- Ensures `gtag` knows the consent state before any tracking fires

**Timing:**
Runs immediately when script loads (script loads in footer, after gtag).

---

## localStorage Keys

### `mdcc_consent`

Stores user consent choices.

**Format:** JSON object
```json
{
  "analytics": true,
  "ads": false
}
```

**Structure:**
- `analytics` `{boolean}` - User consent for analytics tracking
- `ads` `{boolean}` - User consent for advertising tracking

---

## Events Fired

### `mdcc:changed`

Fired when consent state changes (via any consent action).

**Event Type:** `CustomEvent`

**Event Detail:**
```javascript
{
  analytics: boolean,
  ads: boolean
}
```

**Listeners:**
- Popup system (hides popup after consent)
- Shortcode components (update displayed consent status)

**Example:**
```javascript
document.addEventListener('mdcc:changed', function(event) {
    const state = event.detail;
    console.log('Consent changed:', state);
});
```

---

## Google Consent Mode Integration

### Implementation

The consent manager implements Google Consent Mode v2 with all four required consent types:

1. **analytics_storage** - Controls Google Analytics cookies
   - `granted` when `state.analytics === true`
   - `denied` when `state.analytics === false`

2. **ad_storage** - Controls ad cookies
   - `granted` when `state.ads === true`
   - `denied` when `state.ads === false`

3. **ad_user_data** - Controls user data collection for ads
   - `granted` when `state.ads === true`
   - `denied` when `state.ads === false`

4. **ad_personalization** - Controls personalized advertising
   - `granted` when `state.ads === true`
   - `denied` when `state.ads === false`

### GCM Update Call

```javascript
gtag('consent', 'update', {
    'analytics_storage': 'granted' | 'denied',
    'ad_storage': 'granted' | 'denied',
    'ad_user_data': 'granted' | 'denied',
    'ad_personalization': 'granted' | 'denied'
});
```

### Timing

GCM signals are updated:
- Immediately on page load (via `init()`)
- After any consent action (`acceptAll`, `acceptAnalyticsOnly`, `declineAll`)
- After reset (`reset()`)

---

## Debug Mode

Debug logging is controlled by `config.debug` (from `window.mdccConfig`).

**When enabled:**
- Logs consent state reads/writes
- Logs GCM update attempts
- Logs event dispatches
- Logs errors

**Usage:**
```javascript
window.mdccConfig = {
    debug: true
};
```

---

## Error Handling

All functions include error handling:

- **localStorage access:** Wrapped in try-catch, fails gracefully
- **JSON parsing:** Validates structure before use
- **GCM updates:** Checks for `gtag` availability before calling
- **Event dispatch:** Wrapped in try-catch

The consent manager never throws errors - all errors are logged (if debug enabled) and handled gracefully.

---

## Browser Compatibility

- **localStorage:** Required (IE8+)
- **CustomEvent:** Required (IE11+, all modern browsers)
- **JSON.parse/stringify:** Required (IE8+)
- **Optional chaining (`?.`):** Not used (maintains IE11 compatibility)

---

## Initialization

The consent manager initializes automatically when the script loads:

1. Prevents duplicate initialization (checks `window.mdccConsent`)
2. Reads configuration from `window.mdccConfig`
3. Reads current consent state from localStorage
4. Applies GCM signals immediately
5. Exposes public API via `window.mdccConsent`

---

## Popup Template Contract (1.11.0)

The popup markup lives in `templates/popup.php`. A theme overrides it with `yourtheme/maxtdesign-cookie-consent/popup.php` (child theme first), or a site returns a path from the `mdcc_popup_template` filter. The markup is rendered once and cached for every visitor, so a template must not print anything that depends on who is visiting.

`popup.js` finds elements by the items below and by nothing else. An override must keep them.

| Item | Selector `popup.js` uses | Needed for |
|---|---|---|
| Root element, printed with `style="display: none;"` | `.mdcc-popup` | show, hide, focus trap, click delegation |
| Title | `#mdcc-popup-title` | opt-out title swap |
| Message | `#mdcc-popup-message` | opt-out message swap |
| Accept control | `[data-mdcc-action="accept-all"]` | `mdccConsent.acceptAll()`, "Got it" label for opt-out visitors |
| Decline control | `[data-mdcc-action="decline-all"]` | `mdccConsent.declineAll()`, opt-out wording in the Standard layout |
| Analytics control, Standard layout only | `[data-mdcc-action="analytics-only"]` | `mdccConsent.acceptAnalyticsOnly()`, hidden for opt-out visitors |
| Close control | `.mdcc-popup__close` | close without a choice |
| Manage link, Compact layout only | `.mdcc-popup__manage` | its presence tells `popup.js` the layout is Compact |

A missing control does not throw. The behaviour tied to it stops.

### What `popup.js` changes per visitor

`mdccConsent.bannerMode()` decides. The template prints the `optin` version.

| Visitor | Standard layout | Compact layout |
|---|---|---|
| `optin` | as printed | as printed: Manage options, Decline, Accept |
| `optout` | title, message, "Got it", decline gets the opt-out wording, analytics hidden | title, message, "Got it", the manage link gets the opt-out wording and `aria-label`, decline hidden |
| `none` | popup stays hidden, its CSS and JS are not requested | same |

### Template arguments

`$args` holds `classes` (string[]), `title`, `message`, `privacy_url`, `buttons` (`standard` or `compact`), `manage_url`, `labels` (`close`, `accept`, `accept_aria`, `analytics`, `analytics_aria`, `decline`, `decline_aria`, `manage`) and `settings`. The template escapes what it prints.

### CSS variables

Set on `.mdcc-popup`. Each fallback is the value used before 1.11.0.

| Variable | Styles | Fallback |
|---|---|---|
| `--mdcc-primary` | primary button background and border | `#0073aa` |
| `--mdcc-hover` | primary button background and border on hover and focus | `#0073aadd` |
| `--mdcc-btn-fg` | primary button text | `#fff` |
| `--mdcc-bg` | popup background | `#fff` |
| `--mdcc-fg` | title, message, close and decline text | per element |
| `--mdcc-r` | corner radius of the popup and its buttons | per style preset |
| `--mdcc-w` | width from 1025px up, Top and Bottom positions | `100%` |

Do not set a variable to `inherit`, `initial` or `unset`. Those are CSS-wide keywords: the variable takes the keyword's meaning itself, and the fallback is used.

---

## Version History

- **1.11.0** - Popup template contract and CSS variables documented. `popup.js` handles the Compact layout. Runtime API unchanged by the design system
- **1.7.0** - Build system, minified assets, consent-runtime optimization; API unchanged
- **1.6.0** - Initial release with GCM v2 support
