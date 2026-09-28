/**
 * Popup visitor modes test (plan phase 4).
 *
 * Loads a page built from the real plugin output in headless Chrome: the
 * consent runtime, the inline loader, popup.js and popup.css, served by a
 * local server. Chrome's timezone override selects the visitor mode under the
 * 'regional' consent model, the way a visitor's own time zone does.
 *
 *   Europe/Berlin        -> optin   (nothing is tracked until they accept)
 *   America/Los_Angeles  -> optout  (tracking on, may opt out)
 *   America/Chicago      -> none    (no popup)
 *
 * Asserts the controls in docs/plan-popup-design-system.md section 5, for the
 * Compact and the Standard layout, plus clicks, keyboard order and 0 console
 * errors. Needs PHP, Node 22 or newer and Chrome. No npm dependencies.
 *
 *   node tests/popup-visitor-modes.cjs
 */
'use strict';

const fs = require('fs');
const http = require('http');
const path = require('path');
const base = require('./popup-baseline.cjs');

const ZONES = { optin: 'Europe/Berlin', optout: 'America/Los_Angeles', none: 'America/Chicago' };
const OPTOUT_LINK = 'Do Not Sell or Share My Personal Information';
const COMPACT = { consent_model: 'regional', popup_buttons: 'compact', manage_url: '/cookie-settings/' };
const STANDARD = { consent_model: 'regional' };

let passed = 0;
let failed = 0;
function check(description, condition, detail) {
  if (condition) { passed++; return; }
  failed++;
  console.log('FAIL: ' + description + (detail !== undefined ? '\n      got: ' + JSON.stringify(detail) : ''));
}

function page(rendered) {
  const config = Object.assign({}, rendered.config, {
    scriptUrl: '/assets/js/popup.min.js',
    styleUrl: '/assets/css/popup.min.css'
  });
  return '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
    + '<meta name="viewport" content="width=device-width, initial-scale=1"><title>visitor modes</title>'
    + (rendered.inline_css ? '<style id="mdcc-popup-inline-css">' + rendered.inline_css + '</style>' : '')
    + '</head><body><main><h1>Page</h1><a href="/other/">A page link</a></main>'
    + rendered.html
    + '<script>var mdccConfig = ' + JSON.stringify(rendered.runtime_config) + ';</script>'
    + '<script src="/assets/js/consent-runtime.min.js"></script>'
    + '<script>var mdccPopupConfig = ' + JSON.stringify(config) + ';</script>'
    + '<script>' + rendered.inline_js + '</script>'
    + '</body></html>';
}

// Runs in the page: what a visitor can see and use.
function inspect() {
  const popup = document.querySelector('.mdcc-popup');
  const shown = (el) => !!el && getComputedStyle(el).display !== 'none' && el.getClientRects().length > 0;
  const control = (selector) => {
    const el = popup && popup.querySelector(selector);
    if (!el) return null;
    return { shown: shown(el), text: el.textContent.trim(), aria: el.getAttribute('aria-label'), href: el.getAttribute('href') };
  };
  return JSON.stringify({
    mode: window.mdccConsent ? window.mdccConsent.bannerMode() : null,
    popupShown: shown(popup) && popup.classList.contains('mdcc-popup--visible'),
    title: popup ? popup.querySelector('#mdcc-popup-title').textContent.trim() : null,
    manage: control('.mdcc-popup__manage'),
    decline: control('[data-mdcc-action="decline-all"]'),
    analytics: control('[data-mdcc-action="analytics-only"]'),
    accept: control('[data-mdcc-action="accept-all"]'),
    stylesheetLoaded: !!document.querySelector('link[href*="popup.min.css"]'),
    scriptLoaded: !!document.querySelector('script[src*="popup.min.js"]'),
    stored: window.mdccConsent ? window.mdccConsent.stored() : null,
    overflow: document.documentElement.scrollWidth > document.documentElement.clientWidth
  });
}

async function main() {
  let html = '';
  const server = http.createServer((req, res) => {
    const url = req.url.split('?')[0];
    if (url === '/') {
      res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
      res.end(html);
      return;
    }
    const file = path.join(base.ROOT, url);
    if (/^\/assets\/(css|js)\/[a-z.-]+$/.test(url) && fs.existsSync(file)) {
      res.writeHead(200, { 'Content-Type': url.endsWith('.css') ? 'text/css' : 'application/javascript' });
      res.end(fs.readFileSync(file));
      return;
    }
    res.writeHead(url === '/favicon.ico' ? 204 : 404);
    res.end();
  });
  await new Promise((resolve) => server.listen(0, '127.0.0.1', resolve));
  const origin = 'http://127.0.0.1:' + server.address().port;

  const chrome = base.launchChrome();
  try {
    const browser = await base.connect(await chrome.ready);
    const problems = [];
    browser.onEvent((method, params) => {
      if (method === 'Runtime.exceptionThrown') problems.push('exception: ' + (params.exceptionDetails.exception && params.exceptionDetails.exception.description || params.exceptionDetails.text));
      if (method === 'Runtime.consoleAPICalled' && (params.type === 'error' || params.type === 'warning')) problems.push('console.' + params.type + ': ' + params.args.map((a) => a.value || a.description).join(' '));
      if (method === 'Log.entryAdded' && params.entry.level === 'error') problems.push('log: ' + params.entry.text + ' ' + (params.entry.url || ''));
    });

    // A fresh browser context per visit: no cookies or stored consent carry over.
    const visit = async (settings, zone, width) => {
      html = page(base.render({ settings }));
      const { browserContextId } = await browser.send('Target.createBrowserContext');
      const { targetId } = await browser.send('Target.createTarget', { url: 'about:blank', browserContextId });
      const { sessionId } = await browser.send('Target.attachToTarget', { targetId, flatten: true });
      const send = (method, params) => browser.send(method, params, sessionId);
      await send('Page.enable');
      await send('Runtime.enable');
      await send('Log.enable');
      await send('Emulation.setTimezoneOverride', { timezoneId: zone });
      await send('Emulation.setDeviceMetricsOverride', { width: width || 1440, height: 900, deviceScaleFactor: 1, mobile: false });
      await send('Page.navigate', { url: origin + '/' });
      const evaluate = async (expression) => {
        const result = await send('Runtime.evaluate', { expression, awaitPromise: true, returnByValue: true });
        if (result.exceptionDetails) throw new Error('Page script failed: ' + JSON.stringify(result.exceptionDetails.exception || result.exceptionDetails.text));
        return result.result.value;
      };
      // popup.js reveals the popup 510 ms after load and focuses 100 ms later.
      const wait = (ms) => evaluate('new Promise(r => setTimeout(r, ' + ms + '))');
      await wait(1500);
      return {
        state: async () => JSON.parse(await evaluate('(' + inspect.toString() + ')()')),
        evaluate,
        wait,
        key: async (key, shift) => {
          const event = { key, code: key, windowsVirtualKeyCode: key === 'Tab' ? 9 : 27, modifiers: shift ? 8 : 0 };
          await send('Input.dispatchKeyEvent', Object.assign({ type: 'keyDown' }, event));
          await send('Input.dispatchKeyEvent', Object.assign({ type: 'keyUp' }, event));
        },
        focused: () => evaluate('(function(){var e=document.activeElement;return e ? (e.getAttribute("data-mdcc-action") || e.className || e.tagName) : null})()'),
        close: () => browser.send('Target.disposeBrowserContext', { browserContextId })
      };
    };

    /* ---- Compact: the three visitor modes (plan section 5) --------------------- */

    let v = await visit(COMPACT, ZONES.optin);
    let s = await v.state();
    check('compact optin: mode is optin', s.mode === 'optin', s.mode);
    check('compact optin: popup is shown', s.popupShown === true);
    check('compact optin: Manage options link is shown', s.manage && s.manage.shown && s.manage.text === 'Manage options', s.manage);
    check('compact optin: link goes to the cookie settings page', s.manage && s.manage.href === 'https://example.test/cookie-settings/', s.manage);
    check('compact optin: Decline is shown (D1a)', s.decline && s.decline.shown && s.decline.text === 'Decline All', s.decline);
    check('compact optin: Accept is shown', s.accept && s.accept.shown && s.accept.text === 'Accept All', s.accept);
    check('compact optin: no Analytics Only control', s.analytics === null, s.analytics);
    check('compact optin: title is the opt-in title', s.title === 'Cookie Consent', s.title);

    // Keyboard: focus starts on Close and stays inside the popup.
    check('compact optin: focus starts on the close button', (await v.focused()) === 'mdcc-popup__close', await v.focused());
    const order = [];
    for (let i = 0; i < 4; i++) { await v.key('Tab'); order.push(await v.focused()); }
    check('compact optin: Tab order is manage, decline, accept, then wraps to close',
      /mdcc-popup__manage/.test(order[0]) && order[1] === 'decline-all' && order[2] === 'accept-all' && order[3] === 'mdcc-popup__close', order);
    await v.key('Tab', true);
    check('compact optin: Shift+Tab from close wraps to accept', (await v.focused()) === 'accept-all', await v.focused());

    await v.evaluate('document.querySelector(\'[data-mdcc-action="decline-all"]\').click()');
    await v.wait(500);
    s = await v.state();
    check('compact optin: Decline stores a denied choice', s.stored && s.stored.analytics === false && s.stored.ads === false, s.stored);
    check('compact optin: popup closes after Decline', s.popupShown === false);
    await v.close();

    v = await visit(COMPACT, ZONES.optin);
    await v.evaluate('document.querySelector(\'[data-mdcc-action="accept-all"]\').click()');
    await v.wait(500);
    s = await v.state();
    check('compact optin: Accept stores a granted choice', s.stored && s.stored.analytics === true && s.stored.ads === true, s.stored);
    await v.close();

    v = await visit(COMPACT, ZONES.optin);
    await v.key('Escape');
    await v.wait(500);
    s = await v.state();
    check('compact optin: Escape closes the popup and stores nothing', s.popupShown === false && s.stored === null, s.stored);
    await v.close();

    v = await visit(COMPACT, ZONES.optout);
    s = await v.state();
    check('compact optout: mode is optout', s.mode === 'optout', s.mode);
    check('compact optout: popup is shown', s.popupShown === true);
    check('compact optout: the link carries the opt-out wording (D2)', s.manage && s.manage.shown && s.manage.text === OPTOUT_LINK && s.manage.aria === OPTOUT_LINK, s.manage);
    check('compact optout: the link still goes to the cookie settings page', s.manage && s.manage.href === 'https://example.test/cookie-settings/', s.manage);
    check('compact optout: no separate opt-out button (D2)', s.decline && s.decline.shown === false, s.decline);
    check('compact optout: decline keeps its own label while hidden', s.decline && s.decline.text === 'Decline All', s.decline);
    check('compact optout: Got it is shown', s.accept && s.accept.shown && s.accept.text === 'Got it', s.accept);
    check('compact optout: title is the opt-out title', s.title === 'Your privacy choices', s.title);
    const optoutOrder = [];
    for (let i = 0; i < 3; i++) { await v.key('Tab'); optoutOrder.push(await v.focused()); }
    check('compact optout: Tab order is link, Got it, then wraps to close',
      /mdcc-popup__manage/.test(optoutOrder[0]) && optoutOrder[1] === 'accept-all' && optoutOrder[2] === 'mdcc-popup__close', optoutOrder);
    await v.close();

    v = await visit(COMPACT, ZONES.none);
    s = await v.state();
    check('compact none: mode is none', s.mode === 'none', s.mode);
    check('compact none: no popup', s.popupShown === false);
    check('compact none: popup stylesheet and script are not requested', s.stylesheetLoaded === false && s.scriptLoaded === false, s);
    await v.close();

    /* ---- Compact on a phone: nothing overflows ---------------------------------- */

    for (const mode of ['optin', 'optout']) {
      v = await visit(COMPACT, ZONES[mode], 375);
      s = await v.state();
      check('compact ' + mode + ' @375: popup is shown', s.popupShown === true);
      check('compact ' + mode + ' @375: the page does not scroll sideways', s.overflow === false);
      const box = JSON.parse(await v.evaluate('JSON.stringify([".mdcc-popup__manage",".mdcc-popup__content"].map(function(q){var r=document.querySelector(q).getBoundingClientRect();return [r.left,r.right]}))'));
      check('compact ' + mode + ' @375: the link stays inside the popup', box[0][0] >= box[1][0] && box[0][1] <= box[1][1], box);
      await v.close();
    }

    /* ---- Custom labels ----------------------------------------------------------- */

    v = await visit(Object.assign({}, COMPACT, { label_accept: 'Accept all', label_manage: 'Cookie options', label_decline: 'No thanks' }), ZONES.optin);
    s = await v.state();
    check('custom labels are shown', s.accept.text === 'Accept all' && s.manage.text === 'Cookie options' && s.decline.text === 'No thanks', s);
    check('custom label is also the accessible name', s.accept.aria === 'Accept all' && s.decline.aria === 'No thanks', s);
    await v.close();

    /* ---- Compact without a page falls back to Standard -------------------------- */

    v = await visit({ consent_model: 'regional', popup_buttons: 'compact' }, ZONES.optin);
    s = await v.state();
    check('compact without a page: the three standard buttons are shown', s.manage === null && s.accept.shown && s.analytics.shown && s.decline.shown, s);
    await v.close();

    /* ---- Standard layout: unchanged behaviour ------------------------------------ */

    v = await visit(STANDARD, ZONES.optin);
    s = await v.state();
    check('standard optin: Accept All, Analytics Only, Decline All are shown',
      s.accept.shown && s.accept.text === 'Accept All' && s.analytics.shown && s.analytics.text === 'Analytics Only' && s.decline.shown && s.decline.text === 'Decline All' && s.manage === null, s);
    await v.close();

    v = await visit(STANDARD, ZONES.optout);
    s = await v.state();
    check('standard optout: Got it and the opt-out button are shown, Analytics Only is hidden',
      s.accept.text === 'Got it' && s.decline.shown && s.decline.text === OPTOUT_LINK && s.analytics.shown === false, s);
    await v.evaluate('document.querySelector(\'[data-mdcc-action="decline-all"]\').click()');
    await v.wait(500);
    s = await v.state();
    check('standard optout: the opt-out button stores a denied choice', s.stored && s.stored.analytics === false && s.stored.ads === false, s.stored);
    await v.close();

    v = await visit(STANDARD, ZONES.none);
    s = await v.state();
    check('standard none: no popup', s.popupShown === false && s.stylesheetLoaded === false);
    await v.close();

    check('0 console errors, warnings and exceptions across all visits', problems.length === 0, problems);
    browser.close();
  } finally {
    chrome.close();
    server.close();
  }

  console.log(JSON.stringify({ visitorModeChecks: passed + failed, passed: failed === 0, failed }));
  if (failed) process.exitCode = 1;
}

main().catch((error) => { console.error(error.message); process.exitCode = 1; });
