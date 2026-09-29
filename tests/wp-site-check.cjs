/**
 * WordPress site check: the popup as a real site renders it.
 *
 * The other popup tests run the plugin behind WordPress stubs. This one loads
 * pages from a running WordPress site in headless Chrome, so it also covers
 * what the stubs cannot: WordPress printing the styles and scripts, the theme
 * stylesheet, and other plugins on the page.
 *
 *   node tests/wp-site-check.cjs http://plugin-test.local <token>
 *
 * The site needs a temporary mu-plugin that, for requests carrying
 * ?mdcc_probe=<token>&mdcc_case=<case>, filters option_mdcc_settings to a
 * preset: "default" (mdcc_default_settings()), "compact" (regional model,
 * Compact, manage_url /sample-page/) and "design" (compact plus width 60,
 * primary #c8102e, background #111111, text #ffffff, button text #000000,
 * radius 12, theme font, label_accept "Accept all"). Delete the mu-plugin
 * after the run. Never install it on a production site.
 *
 * Needs Node 22 or newer and Chrome. No npm dependencies.
 */
'use strict';

const fs = require('fs');
const path = require('path');
const base = require('./popup-baseline.cjs');

const SITE = (process.argv[2] || '').replace(/\/$/, '');
const TOKEN = process.argv[3] || '';
if (!SITE || !TOKEN) {
  console.error('Usage: node tests/wp-site-check.cjs <site url> <token>');
  process.exit(1);
}

const ZONES = { optin: 'Europe/Berlin', optout: 'America/Los_Angeles', none: 'America/Chicago' };
const OPTOUT_LINK = 'Do Not Sell or Share My Personal Information';
const url = (testCase) => SITE + '/?mdcc_probe=' + TOKEN + '&mdcc_case=' + testCase;

let passed = 0;
let failed = 0;
function check(description, condition, detail) {
  if (condition) { passed++; return; }
  failed++;
  console.log('FAIL: ' + description + (detail !== undefined ? '\n      got: ' + JSON.stringify(detail) : ''));
}

// Runs in the page.
function inspect() {
  const popup = document.querySelector('.mdcc-popup');
  const shown = (el) => !!el && getComputedStyle(el).display !== 'none' && el.getClientRects().length > 0;
  const control = (selector) => {
    const el = popup && popup.querySelector(selector);
    if (!el) return null;
    const cs = getComputedStyle(el);
    const r = el.getBoundingClientRect();
    return {
      shown: shown(el), text: el.textContent.trim(), href: el.getAttribute('href'),
      color: cs.color, background: cs.backgroundColor, radius: cs.borderTopLeftRadius,
      font: cs.fontFamily, rect: [Math.round(r.left), Math.round(r.right), Math.round(r.width)]
    };
  };
  return JSON.stringify({
    mode: window.mdccConsent ? window.mdccConsent.bannerMode() : null,
    popupShown: shown(popup) && popup.classList.contains('mdcc-popup--visible'),
    bodyFont: getComputedStyle(document.body).fontFamily,
    title: control('#mdcc-popup-title'),
    message: control('#mdcc-popup-message'),
    content: control('.mdcc-popup__content'),
    container: control('.mdcc-popup__container'),
    close: control('.mdcc-popup__close'),
    manage: control('.mdcc-popup__manage'),
    decline: control('[data-mdcc-action="decline-all"]'),
    analytics: control('[data-mdcc-action="analytics-only"]'),
    accept: control('[data-mdcc-action="accept-all"]'),
    inlineStyle: (document.getElementById('mdcc-popup-inline-css') || {}).textContent || '',
    stylesheet: !!document.querySelector('link[href*="maxtdesign-cookie-consent/assets/css/popup"]'),
    script: !!document.querySelector('script[src*="maxtdesign-cookie-consent/assets/js/popup."]'),
    stored: window.mdccConsent ? window.mdccConsent.stored() : null,
    overflow: document.documentElement.scrollWidth > document.documentElement.clientWidth,
    viewport: document.documentElement.clientWidth
  });
}

async function main() {
  /* ---- what WordPress prints ------------------------------------------------- */

  const response = await fetch(url('default'));
  const html = base.lf(await response.text());
  check('site answers 200', response.status === 200, response.status);
  check('no PHP error text in the page', !/<b>(Fatal error|Warning|Notice|Deprecated)<\/b>:/.test(html));
  // A site with a privacy policy page prints the link. Only its address
  // differs from the fixture, so that one attribute is set aside.
  const withLink = html.includes('mdcc-popup__privacy-link');
  const fixture = base.lf(fs.readFileSync(path.join(__dirname, 'fixtures/popup-baseline/markup/' + (withLink ? 'privacy-link' : 'fresh-install') + '.html'), 'utf8')).replace(/\s+$/, '');
  const start = html.indexOf('        <div class="mdcc-popup ');
  const end = '</div>\n            </div>\n        </div>';
  const printed = start < 0 ? '' : html.slice(start, html.indexOf(end, start) + end.length);
  const setAside = (markup) => markup.replace(/(<p class="mdcc-popup__privacy-link">\s*<a href=")[^"]*"/, '$1"');
  check(
    'default settings: WordPress prints the popup markup byte for byte as the baseline (' + Buffer.byteLength(printed) + ' B' + (withLink ? ', privacy link address set aside' : '') + ')',
    printed !== '' && setAside(printed) === setAside(fixture)
  );
  check('default settings: no inline style block for the popup', !html.includes('mdcc-popup-inline-css'));
  check('default settings: no stylesheet or popup script tag in the HTML', !/<link[^>]+cookie-consent\/assets\/css\/popup/.test(html) && !/<script[^>]+cookie-consent\/assets\/js\/popup\./.test(html));

  const designHtml = base.lf(await (await fetch(url('design'))).text());
  const inline = (designHtml.match(/<style id=.mdcc-popup-inline-css.[^>]*>([\s\S]*?)<\/style>/) || [])[1] || '';
  const inlineHead = designHtml.indexOf('mdcc-popup-inline-css') < designHtml.indexOf('</head>');
  check('design settings: WordPress prints the inline block in the head', inline !== '' && inlineHead, inline);
  check('design settings: inline block holds the variables and both rules',
    /^\s*\.mdcc-popup\{--mdcc-primary:#c8102e;--mdcc-hover:#c8102edd;--mdcc-btn-fg:#000000;--mdcc-bg:#111111;--mdcc-fg:#ffffff;--mdcc-r:12px;--mdcc-w:60%\}\.mdcc-popup \.mdcc-popup__button\{font-family:inherit\}\.mdcc-popup \.mdcc-popup__manage\{box-sizing:border-box;text-align:center\}/.test(inline), inline);

  /* ---- what the browser shows ------------------------------------------------- */

  const chrome = base.launchChrome();
  try {
    const browser = await base.connect(await chrome.ready);
    const problems = [];
    const requests = [];
    browser.onEvent((method, params) => {
      if (method === 'Runtime.exceptionThrown') problems.push('exception: ' + (params.exceptionDetails.exception && params.exceptionDetails.exception.description || params.exceptionDetails.text) + ' @ ' + (params.exceptionDetails.url || ''));
      if (method === 'Runtime.consoleAPICalled' && params.type === 'error') problems.push('console.error: ' + params.args.map((a) => a.value || a.description).join(' '));
      if (method === 'Log.entryAdded' && params.entry.level === 'error') problems.push('log: ' + params.entry.text + ' ' + (params.entry.url || ''));
      if (method === 'Network.requestWillBeSent' && /maxtdesign-cookie-consent/.test(params.request.url)) requests.push(params.request.url.replace(SITE, '').replace(/\?.*$/, ''));
    });

    const visit = async (testCase, zone, width) => {
      requests.length = 0;
      const { browserContextId } = await browser.send('Target.createBrowserContext');
      const { targetId } = await browser.send('Target.createTarget', { url: 'about:blank', browserContextId });
      const { sessionId } = await browser.send('Target.attachToTarget', { targetId, flatten: true });
      const send = (method, params) => browser.send(method, params, sessionId);
      await send('Page.enable');
      await send('Runtime.enable');
      await send('Log.enable');
      await send('Network.enable');
      await send('Emulation.setTimezoneOverride', { timezoneId: zone });
      await send('Emulation.setDeviceMetricsOverride', { width: width || 1440, height: 900, deviceScaleFactor: 1, mobile: false });
      await send('Page.navigate', { url: url(testCase) });
      const evaluate = async (expression) => {
        const result = await send('Runtime.evaluate', { expression, awaitPromise: true, returnByValue: true });
        if (result.exceptionDetails) throw new Error('Page script failed: ' + JSON.stringify(result.exceptionDetails.exception || result.exceptionDetails.text));
        return result.result.value;
      };
      // Wait for the load event, then for popup.js (510 ms reveal, 300 ms transition).
      for (let i = 0; i < 100; i++) {
        if (await evaluate('document.readyState') === 'complete') break;
        await new Promise((r) => setTimeout(r, 200));
      }
      await evaluate('new Promise(r => setTimeout(r, 2000))');
      return {
        state: async () => JSON.parse(await evaluate('(' + inspect.toString() + ')()')),
        evaluate,
        requests: () => requests.slice(),
        close: () => browser.send('Target.disposeBrowserContext', { browserContextId })
      };
    };

    // Default settings, opt-in everywhere.
    let v = await visit('default', ZONES.none);
    let s = await v.state();
    check('default: mode is optin whatever the time zone', s.mode === 'optin', s.mode);
    check('default: popup is shown', s.popupShown === true);
    check('default: the three standard buttons are shown', s.accept && s.accept.shown && s.analytics && s.analytics.shown && s.decline && s.decline.shown && s.manage === null, s);
    check('default: primary button is #0073aa', s.accept.background === 'rgb(0, 115, 170)', s.accept.background);
    check('default: banner is full width', s.container.rect[2] === s.viewport, [s.container.rect, s.viewport]);
    // Not a check: many themes set the font of every button themselves.
    console.log('Note: default button font is ' + (s.accept.font === s.bodyFont ? 'the theme font, set by the theme' : 'not the theme font') + '.');
    check('default: popup CSS and JS are requested once each, after the runtime', JSON.stringify(v.requests().filter((r) => /popup\./.test(r))) === JSON.stringify(['/wp-content/plugins/maxtdesign-cookie-consent/assets/css/popup.min.css', '/wp-content/plugins/maxtdesign-cookie-consent/assets/js/popup.min.js']), v.requests());
    await v.evaluate('document.querySelector(\'[data-mdcc-action="accept-all"]\').click()');
    await v.evaluate('new Promise(r => setTimeout(r, 500))');
    s = await v.state();
    check('default: Accept stores a granted choice and closes the popup', s.stored && s.stored.analytics === true && s.popupShown === false, s.stored);
    await v.close();

    // Compact, the three visitor modes.
    v = await visit('compact', ZONES.optin);
    s = await v.state();
    check('compact optin: Manage options, Decline and Accept are shown', s.mode === 'optin' && s.popupShown && s.manage && s.manage.shown && s.manage.text === 'Manage options' && s.decline.shown && s.accept.shown && s.analytics === null, s);
    check('compact optin: the link goes to the page on this site', s.manage && s.manage.href === SITE + '/sample-page/', s.manage && s.manage.href);
    check('compact optin: the link is centered text inside the popup', s.manage && s.manage.rect[0] >= s.content.rect[0] && s.manage.rect[1] <= s.content.rect[1], [s.manage && s.manage.rect, s.content.rect]);
    await v.close();

    v = await visit('compact', ZONES.optout);
    s = await v.state();
    check('compact optout: the link carries the opt-out wording, Got it is shown, decline is hidden', s.mode === 'optout' && s.popupShown && s.manage && s.manage.text === OPTOUT_LINK && s.accept.text === 'Got it' && s.decline.shown === false, s);
    await v.close();

    v = await visit('compact', ZONES.none);
    s = await v.state();
    check('compact none: no popup, and no popup CSS or JS requested', s.mode === 'none' && s.popupShown === false && v.requests().filter((r) => /popup\./.test(r)).length === 0, v.requests());
    await v.close();

    // Design settings with the theme's stylesheet on the page.
    v = await visit('design', ZONES.optin, 1440);
    s = await v.state();
    check('design @1440: banner is 60% wide and centered', s.container.rect[2] === Math.round(s.viewport * 0.6) && Math.abs(s.container.rect[0] - (s.viewport - s.container.rect[1])) <= 1, [s.container.rect, s.viewport]);
    check('design: background #111111', s.content.background === 'rgb(17, 17, 17)', s.content.background);
    check('design: title, message, close and link text #ffffff', [s.title, s.message, s.close, s.manage].every((c) => c && c.color === 'rgb(255, 255, 255)'), [s.title.color, s.message.color, s.close.color, s.manage && s.manage.color]);
    check('design: primary button #c8102e with #000000 text', s.accept.background === 'rgb(200, 16, 46)' && s.accept.color === 'rgb(0, 0, 0)', s.accept);
    check('design: radius 12px on the popup and the buttons', s.content.radius === '12px' && s.accept.radius === '12px' && s.decline.radius === '12px', [s.content.radius, s.accept.radius, s.decline.radius]);
    check('design: buttons use the theme font', s.accept.font === s.bodyFont && s.decline.font === s.bodyFont, [s.accept.font, s.bodyFont]);
    check('design: custom label is shown', s.accept.text === 'Accept all', s.accept.text);
    await v.close();

    for (const width of [1024, 768, 375]) {
      v = await visit('design', ZONES.optin, width);
      s = await v.state();
      check('design @' + width + ': banner is full width and the page does not scroll sideways', s.popupShown && s.container.rect[2] === s.viewport && s.overflow === false, [s.container.rect, s.viewport, s.overflow]);
      check('design @' + width + ': the link stays inside the popup', s.manage.rect[0] >= s.content.rect[0] && s.manage.rect[1] <= s.content.rect[1], [s.manage.rect, s.content.rect]);
      await v.close();
    }

    const ours = problems.filter((p) => /mdcc|cookie-consent/i.test(p));
    check('0 console errors or exceptions from this plugin', ours.length === 0, ours);
    if (problems.length > ours.length) {
      console.log('Note: ' + (problems.length - ours.length) + ' error(s) from other code on the site, first: ' + problems.filter((p) => !ours.includes(p))[0].slice(0, 200));
    }
    browser.close();
  } finally {
    chrome.close();
  }

  console.log(JSON.stringify({ site: SITE, siteChecks: passed + failed, passed: failed === 0, failed }));
  if (failed) process.exitCode = 1;
}

main().catch((error) => { console.error(error.message); process.exitCode = 1; });
