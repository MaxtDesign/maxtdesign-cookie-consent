/**
 * Popup design settings test (plan phase 3).
 *
 * Renders the popup with design settings and asserts what the browser
 * computes: desktop width, colours, corner radius, button font, and that
 * hostile values never reach the page. Uses the helpers of
 * tests/popup-baseline.cjs. Needs PHP, Node 22 or newer and Chrome.
 *
 *   node tests/popup-design.cjs
 */
'use strict';

const fs = require('fs');
const path = require('path');
const base = require('./popup-baseline.cjs');

const THEME_CSS = 'body{font-family:Georgia,serif}';
const ALL_FIELDS = {
  popup_primary_color: '#C8102E',
  popup_bg_color: '#111',
  popup_text_color: '#ffffff',
  popup_button_text_color: '#000000',
  popup_radius: '24',
  popup_desktop_width: '60',
  popup_inherit_font: true
};

let passed = 0;
let failed = 0;
function check(description, condition, detail) {
  if (condition) { passed++; return; }
  failed++;
  console.log('FAIL: ' + description + (detail !== undefined ? '\n      got: ' + JSON.stringify(detail) : ''));
}

async function main() {
  const css = base.lf(fs.readFileSync(path.join(base.ROOT, 'assets/css/popup.min.css'), 'utf8'));

  /* ---- what PHP prints ------------------------------------------------------ */

  const none = base.render({ settings: {} });
  check('a default site prints no inline style', none.inline_css === '', none.inline_css);

  const saved = base.render({ settings: { popup_primary_color: '#0073aa', popup_desktop_width: 100, popup_radius: '', popup_bg_color: '' } });
  check('saved default values print no inline style', saved.inline_css === '', saved.inline_css);

  const all = base.render({ settings: ALL_FIELDS });
  check(
    'every field set prints 7 variables and the font rule',
    /^\.mdcc-popup\{[^{}]+\}\.mdcc-popup \.mdcc-popup__button\{font-family:inherit\}$/.test(all.inline_css) && all.inline_css.split(';').length === 7,
    all.inline_css
  );
  check('the font setting alone prints only the font rule', base.render({ settings: { popup_inherit_font: true } }).inline_css === '.mdcc-popup .mdcc-popup__button{font-family:inherit}');
  check('every field set stays under 512 B (' + Buffer.byteLength(all.inline_css) + ' B)', Buffer.byteLength(all.inline_css) < 512);
  check('three digit colour is expanded before the alpha suffix', base.render({ settings: { popup_primary_color: '#abc' } }).inline_css === '.mdcc-popup{--mdcc-primary:#aabbcc;--mdcc-hover:#aabbccdd}');
  check('radius 0 is printed, not dropped', base.render({ settings: { popup_radius: '0' } }).inline_css === '.mdcc-popup{--mdcc-r:0}');

  const hostile = base.render({ settings: {
    popup_primary_color: 'red;}body{display:none',
    popup_bg_color: '#12',
    popup_text_color: ['x'],
    popup_button_text_color: 'url(javascript:alert(1))',
    popup_radius: '999',
    popup_desktop_width: '61',
    popup_inherit_font: 0
  } });
  check('hostile values are dropped and the radius is clamped to 24', hostile.inline_css === '.mdcc-popup{--mdcc-r:24px}', hostile.inline_css);
  check('markup is the same with and without design settings', all.html === none.html);

  /* ---- what the browser computes --------------------------------------------- */

  const chrome = base.launchChrome();
  try {
    const browser = await base.connect(await chrome.ready);
    const { targetId } = await browser.send('Target.createTarget', { url: 'about:blank' });
    const { sessionId } = await browser.send('Target.attachToTarget', { targetId, flatten: true });
    const send = (method, params) => browser.send(method, params, sessionId);
    await send('Page.enable');
    await send('Runtime.enable');
    await send('DOM.enable');
    await send('CSS.enable');

    const shoot = async (settings, width, height) => {
      await send('Emulation.setDeviceMetricsOverride', { width, height: height || 900, deviceScaleFactor: 1, mobile: false });
      const { frameTree } = await send('Page.getFrameTree');
      await send('Page.setDocumentContent', { frameId: frameTree.frame.id, html: base.pageHtml(base.render({ settings }), css, THEME_CSS) });
      const result = await send('Runtime.evaluate', {
        expression: '(' + base.capture.toString() + ')(' + JSON.stringify(base.ELEMENTS) + ',' + JSON.stringify(base.PROPS) + ')',
        awaitPromise: true,
        returnByValue: true
      });
      if (result.exceptionDetails) throw new Error('Page script failed');
      return JSON.parse(result.result.value);
    };

    // Desktop width: 1025 px and wider, top and bottom only.
    for (const position of ['top', 'bottom']) {
      const settings = { popup_position: position, popup_desktop_width: 60 };
      let c = await shoot(settings, 1440);
      check(position + ' 60% @1440: 864 px wide, centered at x 288', c.container.rect[2] === 864 && c.container.rect[0] === 288, c.container.rect);
      c = await shoot(settings, 1025);
      check(position + ' 60% @1025: 615 px wide', c.container.rect[2] === 615, c.container.rect);
      for (const width of [1024, 768, 375]) {
        c = await shoot(settings, width);
        check(position + ' 60% @' + width + ': full width', c.container.rect[2] === width && c.container.rect[0] === 0, c.container.rect);
      }
    }
    for (const percent of [80, 50]) {
      const c = await shoot({ popup_desktop_width: percent }, 1440);
      check(percent + '% @1440: ' + (1440 * percent / 100) + ' px wide', c.container.rect[2] === 1440 * percent / 100, c.container.rect);
    }
    const centerDefault = await shoot({ popup_position: 'center' }, 1440);
    const centerWide = await shoot({ popup_position: 'center', popup_desktop_width: 60 }, 1440);
    check('center position ignores the desktop width', JSON.stringify(centerWide.container.rect) === JSON.stringify(centerDefault.container.rect), centerWide.container.rect);

    // Colours, radius and font, under every style preset.
    for (const style of ['minimal', 'modern', 'bold']) {
      const c = await shoot(Object.assign({ popup_style: style }, ALL_FIELDS), 1440);
      const s = (el, prop) => c[el].styles[prop];
      check(style + ': background', s('content', 'background-color') === 'rgb(17, 17, 17)', s('content', 'background-color'));
      for (const el of ['title', 'message', 'close', 'decline']) {
        check(style + ': ' + el + ' text colour', s(el, 'color') === 'rgb(255, 255, 255)', s(el, 'color'));
      }
      check(style + ': primary button background', s('accept', 'background-color') === 'rgb(200, 16, 46)', s('accept', 'background-color'));
      check(style + ': primary button border', s('accept', 'border-top-color') === 'rgb(200, 16, 46)', s('accept', 'border-top-color'));
      check(style + ': primary button text', s('accept', 'color') === 'rgb(0, 0, 0)', s('accept', 'color'));
      check(style + ': secondary button keeps its own colours', s('analytics', 'background-color') === 'rgb(255, 255, 255)' && s('analytics', 'color') === 'rgb(51, 51, 51)');
      for (const el of ['content', 'accept', 'analytics', 'decline']) {
        check(style + ': ' + el + ' radius 24px', s(el, 'border-top-left-radius') === '24px' && s(el, 'border-bottom-right-radius') === '24px', s(el, 'border-top-left-radius'));
      }
      for (const el of ['accept', 'analytics', 'decline']) {
        check(style + ': ' + el + ' uses the theme font', /^Georgia/.test(s(el, 'font-family')), s(el, 'font-family'));
      }
    }

    // Defaults: buttons keep the browser font even when the theme sets one.
    const plain = await shoot({}, 1440);
    check('default: buttons do not take the theme font', !/Georgia/.test(plain.accept.styles['font-family']), plain.accept.styles['font-family']);
    check('default: title takes the theme font, as before', /^Georgia/.test(plain.title.styles['font-family']), plain.title.styles['font-family']);

    // Hover on the primary button, default and custom.
    const hoverColor = async (settings) => {
      await shoot(settings, 1440);
      const { root } = await send('DOM.getDocument', { depth: 0 });
      const { nodeId } = await send('DOM.querySelector', { nodeId: root.nodeId, selector: '[data-mdcc-action="accept-all"]' });
      await send('CSS.forcePseudoState', { nodeId, forcedPseudoClasses: ['hover'] });
      // The button has a .2s transition, so wait for it to finish.
      const result = await send('Runtime.evaluate', {
        expression: 'new Promise(r => setTimeout(() => r(getComputedStyle(document.querySelector(\'[data-mdcc-action="accept-all"]\')).backgroundColor), 350))',
        awaitPromise: true,
        returnByValue: true
      });
      return result.result.value;
    };
    const defaultHover = await hoverColor({});
    check('default hover colour is #0073aadd, as in 1.10', /^rgba\(0, 115, 170, 0\.86\d*\)$/.test(defaultHover), defaultHover);
    const customHover = await hoverColor({ popup_primary_color: '#c8102e' });
    check('custom hover colour is the primary colour at alpha dd', /^rgba\(200, 16, 46, 0\.86\d*\)$/.test(customHover), customHover);

    browser.close();
  } finally {
    chrome.close();
  }

  console.log(JSON.stringify({ designChecks: passed + failed, passed: failed === 0, failed }));
  if (failed) process.exitCode = 1;
}

main().catch((error) => { console.error(error.message); process.exitCode = 1; });
