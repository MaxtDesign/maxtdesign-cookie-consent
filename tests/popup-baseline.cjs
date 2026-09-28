/**
 * Popup baseline: record and compare what a site renders.
 *
 * Proves the rule in docs/plan-popup-design-system.md section 2: a site that
 * upgrades and changes no settings renders the same popup as before.
 *
 *   node tests/popup-baseline.cjs            compare against tests/fixtures/popup-baseline
 *   node tests/popup-baseline.cjs --update   rewrite the fixtures from the current code
 *   node tests/popup-baseline.cjs --no-browser   markup, inline CSS and config only
 *
 * Markup, inline CSS and config come from tests/popup-render.php (the real
 * plugin classes behind WordPress stubs). Computed styles come from headless
 * Chrome driven over the DevTools Protocol. No npm dependencies: needs PHP,
 * Node 22 or newer (global WebSocket) and Chrome. Set CHROME_PATH to override
 * the browser location.
 *
 * The page mirrors the 1.11.0 document order: the inline <style> block is in
 * the head first, and popup.min.css comes after it, because popup-loader.js
 * appends the stylesheet link at the end of the head.
 */
'use strict';

const fs = require('fs');
const os = require('os');
const path = require('path');
const { execFileSync, spawn } = require('child_process');

const ROOT = path.join(__dirname, '..');
const FIXTURES = path.join(__dirname, 'fixtures', 'popup-baseline');
const UPDATE = process.argv.includes('--update');
const NO_BROWSER = process.argv.includes('--no-browser');

const STYLES = ['minimal', 'modern', 'bold'];
const POSITIONS = ['top', 'bottom', 'center'];
const VIEWPORTS = [
  { width: 1440, height: 900 },
  { width: 1024, height: 768 },
  { width: 768, height: 1024 },
  { width: 375, height: 812 }
];

// Every case is a settings override on top of mdcc_default_settings().
// "viewports" limits a case to some widths; the default is all four.
const CASES = [];
STYLES.forEach((style) => POSITIONS.forEach((position) => {
  CASES.push({ name: style + '-' + position, settings: { popup_style: style, popup_position: position } });
}));
CASES.push({ name: 'fresh-install', settings: {}, viewports: [1440] });
CASES.push({ name: 'privacy-link', settings: { popup_style: 'minimal' }, privacyUrl: 'https://example.test/privacy-policy/', viewports: [1440, 375] });
CASES.push({ name: 'custom-primary', settings: { popup_primary_color: '#c8102e' }, viewports: [1440] });
CASES.push({ name: 'animation-none', settings: { popup_animation: 'none' }, viewports: [1440] });
CASES.push({ name: 'model-regional', settings: { consent_model: 'regional' }, viewports: [1440] });

const ELEMENTS = {
  root: '.mdcc-popup',
  overlay: '.mdcc-popup__overlay',
  container: '.mdcc-popup__container',
  content: '.mdcc-popup__content',
  close: '.mdcc-popup__close',
  title: '#mdcc-popup-title',
  message: '#mdcc-popup-message',
  privacy: '.mdcc-popup__privacy-link a',
  actions: '.mdcc-popup__actions',
  accept: '[data-mdcc-action="accept-all"]',
  analytics: '[data-mdcc-action="analytics-only"]',
  decline: '[data-mdcc-action="decline-all"]',
  manage: '.mdcc-popup__manage'
};

const PROPS = [
  'display', 'position', 'top', 'right', 'bottom', 'left', 'width', 'height',
  'min-width', 'max-width', 'min-height', 'z-index', 'opacity', 'transform', 'pointer-events',
  'margin-top', 'margin-right', 'margin-bottom', 'margin-left',
  'padding-top', 'padding-right', 'padding-bottom', 'padding-left',
  'border-top-width', 'border-right-width', 'border-bottom-width', 'border-left-width',
  'border-top-style', 'border-left-style',
  'border-top-color', 'border-right-color', 'border-bottom-color', 'border-left-color',
  'border-top-left-radius', 'border-top-right-radius', 'border-bottom-right-radius', 'border-bottom-left-radius',
  'background-color', 'color', 'box-shadow',
  'font-family', 'font-size', 'font-weight', 'line-height', 'letter-spacing',
  'text-transform', 'text-decoration-line', 'text-align',
  'flex-direction', 'flex-wrap', 'flex-grow', 'flex-shrink', 'flex-basis',
  'row-gap', 'column-gap', 'align-items', 'justify-content', 'cursor'
];

/* ---- helpers --------------------------------------------------------------- */

function lf(text) {
  return String(text).replace(/\r\n/g, '\n');
}

function render(testCase) {
  const args = [path.join(__dirname, 'popup-render.php'), JSON.stringify(testCase.settings)];
  if (testCase.privacyUrl) args.push(testCase.privacyUrl);
  const out = execFileSync('php', args, { cwd: ROOT, encoding: 'utf8', maxBuffer: 8 * 1024 * 1024 });
  return JSON.parse(out);
}

function findChrome() {
  const candidates = [
    process.env.CHROME_PATH,
    'C:/Program Files/Google/Chrome/Application/chrome.exe',
    'C:/Program Files (x86)/Google/Chrome/Application/chrome.exe',
    'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',
    '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
    '/usr/bin/google-chrome',
    '/usr/bin/chromium',
    '/usr/bin/chromium-browser'
  ].filter(Boolean);
  const found = candidates.find((p) => fs.existsSync(p));
  if (!found) throw new Error('Chrome not found. Set CHROME_PATH.');
  return found;
}

function launchChrome() {
  const profile = fs.mkdtempSync(path.join(os.tmpdir(), 'mdcc-baseline-'));
  const child = spawn(findChrome(), [
    '--headless=new', '--remote-debugging-port=0', '--user-data-dir=' + profile,
    '--no-first-run', '--no-default-browser-check', '--disable-gpu', '--hide-scrollbars',
    '--force-device-scale-factor=1', 'about:blank'
  ], { stdio: ['ignore', 'ignore', 'pipe'] });

  const ready = new Promise((resolve, reject) => {
    let buffer = '';
    const timer = setTimeout(() => reject(new Error('Chrome did not report a DevTools endpoint within 20 s')), 20000);
    child.stderr.on('data', (chunk) => {
      buffer += chunk;
      const match = buffer.match(/DevTools listening on (ws:\/\/\S+)/);
      if (match) { clearTimeout(timer); resolve(match[1]); }
    });
    child.on('exit', (code) => { clearTimeout(timer); reject(new Error('Chrome exited early with code ' + code)); });
  });

  const close = () => {
    try { child.kill(); } catch (e) { /* already gone */ }
    setTimeout(() => { try { fs.rmSync(profile, { recursive: true, force: true }); } catch (e) { /* locked, left for the OS */ } }, 300);
  };
  return { ready, close };
}

function connect(url) {
  return new Promise((resolve, reject) => {
    const ws = new WebSocket(url);
    const pending = new Map();
    let nextId = 1;
    ws.onerror = () => reject(new Error('Cannot connect to the DevTools endpoint'));
    ws.onmessage = (event) => {
      const msg = JSON.parse(event.data);
      if (!msg.id || !pending.has(msg.id)) return;
      const handler = pending.get(msg.id);
      pending.delete(msg.id);
      if (msg.error) handler.reject(new Error(msg.error.message));
      else handler.resolve(msg.result);
    };
    ws.onopen = () => resolve({
      send(method, params, sessionId) {
        const id = nextId++;
        const payload = { id, method, params: params || {} };
        if (sessionId) payload.sessionId = sessionId;
        return new Promise((res, rej) => { pending.set(id, { resolve: res, reject: rej }); ws.send(JSON.stringify(payload)); });
      },
      close() { ws.close(); }
    });
  });
}

function pageHtml(rendered, css, themeCss) {
  return '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
    + '<meta name="viewport" content="width=device-width, initial-scale=1">'
    + '<title>popup baseline</title>'
    + (themeCss ? '<style id="theme">' + themeCss + '</style>' : '')
    + '<style id="mdcc-popup-inline-css">' + rendered.inline_css + '</style>'
    + '<style id="mdcc-popup-stylesheet">' + css + '</style>'
    + '</head><body><main><h1>Page</h1><p>Content.</p></main>' + rendered.html + '</body></html>';
}

// Runs in the page. Reveals the popup the way popup.js init() does, waits for
// the 300 ms transitions, then reads computed styles and boxes.
function capture(elements, props) {
  return new Promise((resolve) => {
    const popup = document.querySelector('.mdcc-popup');
    popup.style.display = 'block';
    document.body.classList.add('mdcc-popup-open');
    setTimeout(() => {
      popup.classList.add('mdcc-popup--visible');
      setTimeout(() => {
        const out = {};
        Object.keys(elements).forEach((key) => {
          const el = document.querySelector(elements[key]);
          if (!el) return;
          const cs = getComputedStyle(el);
          const rect = el.getBoundingClientRect();
          const styles = {};
          props.forEach((p) => { styles[p] = cs.getPropertyValue(p); });
          out[key] = {
            rect: [rect.x, rect.y, rect.width, rect.height].map((n) => Math.round(n * 100) / 100),
            text: el.children.length ? undefined : el.textContent.trim(),
            styles
          };
        });
        resolve(JSON.stringify(out));
      }, 450);
    }, 20);
  });
}

function diffObjects(expected, actual, trail, out) {
  const keys = new Set(Object.keys(expected || {}).concat(Object.keys(actual || {})));
  keys.forEach((key) => {
    const a = expected ? expected[key] : undefined;
    const b = actual ? actual[key] : undefined;
    const here = trail ? trail + ' > ' + key : key;
    if (a && b && typeof a === 'object' && typeof b === 'object' && !Array.isArray(a)) {
      diffObjects(a, b, here, out);
    } else if (JSON.stringify(a) !== JSON.stringify(b)) {
      out.push(here + ': expected ' + JSON.stringify(a) + ', got ' + JSON.stringify(b));
    }
  });
}

// computed.json stores each element's styles as an array in "props" order, one
// case and width per line, so the fixture stays small and diffs stay readable.
function packComputed(product, computed) {
  const lines = [];
  Object.keys(computed).forEach((name) => Object.keys(computed[name]).forEach((width) => {
    const packed = {};
    Object.keys(computed[name][width]).forEach((key) => {
      const el = computed[name][width][key];
      packed[key] = { rect: el.rect, text: el.text, styles: PROPS.map((p) => el.styles[p]) };
    });
    lines.push('  ' + JSON.stringify(name + '@' + width) + ': ' + JSON.stringify(packed));
  }));
  return '{\n "browser": ' + JSON.stringify(product)
    + ',\n "recorded": ' + JSON.stringify(new Date().toISOString().slice(0, 10))
    + ',\n "props": ' + JSON.stringify(PROPS)
    + ',\n "cases": {\n' + lines.join(',\n') + '\n }\n}\n';
}

function unpackComputed(stored) {
  const cases = {};
  Object.keys(stored.cases).forEach((id) => {
    const at = id.lastIndexOf('@');
    const name = id.slice(0, at);
    const width = id.slice(at + 1);
    cases[name] = cases[name] || {};
    cases[name][width] = {};
    Object.keys(stored.cases[id]).forEach((key) => {
      const el = stored.cases[id][key];
      const styles = {};
      stored.props.forEach((p, i) => { styles[p] = el.styles[i]; });
      cases[name][width][key] = { rect: el.rect, text: el.text, styles };
    });
  });
  return { browser: stored.browser, cases };
}

/* ---- main ------------------------------------------------------------------ */

async function main() {
  const css = lf(fs.readFileSync(path.join(ROOT, 'assets/css/popup.min.css'), 'utf8'));
  const failures = [];
  let checks = 0;

  const textFixture = (relative, content) => {
    const file = path.join(FIXTURES, relative);
    if (UPDATE) {
      fs.mkdirSync(path.dirname(file), { recursive: true });
      fs.writeFileSync(file, content);
      return;
    }
    checks++;
    if (!fs.existsSync(file)) { failures.push(relative + ': fixture missing'); return; }
    const expected = lf(fs.readFileSync(file, 'utf8'));
    if (expected !== content) {
      const a = expected.split('\n');
      const b = content.split('\n');
      let line = 0;
      while (line < a.length && line < b.length && a[line] === b[line]) line++;
      failures.push(relative + ': differs at line ' + (line + 1) + '\n      expected: ' + JSON.stringify(a[line]) + '\n      got:      ' + JSON.stringify(b[line]));
    }
  };

  const rendered = {};
  const summary = {};
  CASES.forEach((testCase) => {
    const r = render(testCase);
    rendered[testCase.name] = r;
    textFixture('markup/' + testCase.name + '.html', r.html);
    textFixture('inline-css/' + testCase.name + '.css', r.inline_css);
    summary[testCase.name] = {
      settings: testCase.settings,
      privacyUrl: testCase.privacyUrl || '',
      markupBytes: Buffer.byteLength(r.html),
      inlineCssBytes: Buffer.byteLength(r.inline_css),
      inlineLoaderBytes: r.inline_js_bytes,
      config: r.config
    };
  });
  textFixture('summary.json', JSON.stringify(summary, null, 2) + '\n');

  if (!NO_BROWSER) {
    const chrome = launchChrome();
    try {
      const browser = await connect(await chrome.ready);
      const { targetId } = await browser.send('Target.createTarget', { url: 'about:blank' });
      const { sessionId } = await browser.send('Target.attachToTarget', { targetId, flatten: true });
      const send = (method, params) => browser.send(method, params, sessionId);
      await send('Page.enable');
      await send('Runtime.enable');
      const version = await browser.send('Browser.getVersion');
      const computed = {};

      for (const testCase of CASES) {
        computed[testCase.name] = {};
        for (const viewport of VIEWPORTS) {
          if (testCase.viewports && !testCase.viewports.includes(viewport.width)) continue;
          await send('Emulation.setDeviceMetricsOverride', { width: viewport.width, height: viewport.height, deviceScaleFactor: 1, mobile: false });
          const { frameTree } = await send('Page.getFrameTree');
          await send('Page.setDocumentContent', { frameId: frameTree.frame.id, html: pageHtml(rendered[testCase.name], css) });
          const result = await send('Runtime.evaluate', {
            expression: '(' + capture.toString() + ')(' + JSON.stringify(ELEMENTS) + ',' + JSON.stringify(PROPS) + ')',
            awaitPromise: true,
            returnByValue: true
          });
          if (result.exceptionDetails) throw new Error('Page script failed: ' + JSON.stringify(result.exceptionDetails));
          computed[testCase.name][viewport.width] = JSON.parse(result.result.value);
        }
      }
      browser.close();

      const file = path.join(FIXTURES, 'computed.json');
      if (UPDATE) {
        fs.writeFileSync(file, packComputed(version.product, computed));
      } else if (!fs.existsSync(file)) {
        failures.push('computed.json: fixture missing');
      } else {
        const expected = unpackComputed(JSON.parse(fs.readFileSync(file, 'utf8')));
        if (expected.browser !== version.product) {
          console.log('Note: fixture recorded with ' + expected.browser + ', running ' + version.product);
        }
        Object.keys(computed).forEach((name) => Object.keys(computed[name]).forEach((width) => {
          checks++;
          const diffs = [];
          diffObjects((expected.cases[name] || {})[width], computed[name][width], '', diffs);
          if (diffs.length) failures.push('computed ' + name + ' @' + width + ': ' + diffs.length + ' difference(s)\n      ' + diffs.slice(0, 8).join('\n      '));
        }));
      }
    } finally {
      chrome.close();
    }
  }

  if (UPDATE) {
    console.log('Baseline written to tests/fixtures/popup-baseline (' + CASES.length + ' cases' + (NO_BROWSER ? ', no browser' : '') + ')');
    return;
  }
  if (failures.length) {
    console.log('FAIL: ' + failures.length + ' of ' + checks + ' baseline checks differ');
    failures.forEach((f) => console.log('  - ' + f));
    process.exitCode = 1;
    return;
  }
  console.log(JSON.stringify({ baselineChecks: checks, passed: true, browser: !NO_BROWSER }));
}

if (require.main === module) {
  main().catch((error) => { console.error(error.message); process.exitCode = 1; });
}

// Shared with tests/popup-design.cjs.
module.exports = { ROOT, ELEMENTS, PROPS, VIEWPORTS, lf, render, launchChrome, connect, pageHtml, capture };
