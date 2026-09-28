/* Load presentation only when this visitor needs the consent dialog. */
(function (w, d) {
    var c = w.mdccConsent, p = w.mdccPopupConfig || {};
    function load() {
        if (!c || c.bannerMode() === 'none') return;
        var s = c.stored();
        if (s ? (s.analytics || s.ads || !p.repromptDecline) :
            (d.cookie.split(';').some(function (v) { return v.trim().indexOf(p.cookieName + '=') === 0; }))) return;
        var css = d.createElement('link');
        css.rel = 'stylesheet'; css.href = p.styleUrl;
        css.onload = function () {
            var js = d.createElement('script');
            js.src = p.scriptUrl; d.head.appendChild(js);
        };
        d.head.appendChild(css);
    }
    if (c && c.ready) c.ready(load); else load();
})(window, document);
