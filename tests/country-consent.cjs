const fs = require('fs'), vm = require('vm'), assert = require('assert');
let checks = 0;
function check(value, message) { assert(value, message); checks++; }
function boot(file, {mode='regional', stored=null, gpc=false, endpoint='/mxo-consent-region/', blocked=false}={}) {
    let saved = stored && JSON.stringify(stored), request, calls=[], ready=0, events=[];
    const win = {navigator:{globalPrivacyControl:gpc}, mdccConfig:{storageKey:'test',consentModel:mode,countryEndpoint:endpoint,consentApi:true}, wp_set_consent:(...v)=>calls.push(v)};
    const doc = {cookie:'',dispatchEvent:e=>events.push(e.detail)};
    const context = {window:win, document:doc, console, Intl, CustomEvent:function(t,o){this.detail=o.detail;}, gtag:(...v)=>calls.push(v),localStorage:{getItem:()=>saved,setItem:(k,v)=>{if(blocked)throw Error();saved=v;},removeItem:()=>{saved=null;}},XMLHttpRequest:function(){request=this;this.open=(...args)=>{this.args=args;};this.send=()=>{};this.getResponseHeader=()=>this.mime||'application/json';}};
    vm.runInNewContext(fs.readFileSync(file,'utf8'),context);
    const api=win.mdccConsent;
    api.ready(()=>ready++);
    return {api,calls,events, win, get request(){return request;},get ready(){return ready;}, answer(body,status=200,mime){request.status=status;request.mime=mime;request.responseText=body;request.onload();}};
}
for (const suffix of ['', '.min']) {
    const file=__dirname+'/../assets/js/consent-runtime'+suffix+'.js';
    for (const [name,response,grant] of [['US','{"mode":"none"}',true],['nonUS','{"mode":"optin"}',false],['unknown','{}',false],['malformed','not json',false],['unrecognised','{"mode":"optout"}',false],['null','null',false]]) {
        const t=boot(file);
        check(!t.api.current().analytics && !t.ready,name+' initially denied/pending');
        let transitions=[];t.api.registerService('test',{category:'analytics',onGrant:()=>transitions.push(true),onRevoke:()=>transitions.push(false)});
        t.answer(response);
        check(t.api.current().analytics===grant,name+' effective state');
        check(t.api.bannerMode()===(grant?'none':'optin'),name+' banner');
        check(t.ready===1,name+' ready once');
        check(t.calls.filter(v=>v[0]==='statistics').at(-1)[1]===(grant?'allow':'deny'),name+' WP API');
        check(transitions.at(-1)===grant,name+' service transition');
        check(t.api.stored()===null,name+' never persists implied state');
    }
    for (const failure of ['ontimeout','onerror','onabort','http','mime']) {
        const t=boot(file);
        if(failure==='http')t.answer('{"mode":"none"}',500);
        else if(failure==='mime')t.answer('{"mode":"none"}',200,'text/html');
        else t.request[failure]();
        check(!t.api.current().analytics && t.ready===1,failure+' fails closed');
        t.answer('{"mode":"none"}');check(!t.api.current().analytics,failure+' ignores late response');
    }
    for(const stored of [null,{analytics:true,ads:true},{analytics:false,ads:false}]) {
        for(const mode of ['optin','regional','optout']) {
            const t=boot(file,{stored,gpc:true,mode});
            check(!t.request && t.ready===1,'GPC skips lookup');
            check(!t.api.current().analytics && !t.api.current().ads,'GPC effective denial');
            check(!t.calls.some(v=>v[0]==='statistics'&&v[1]==='allow'),'GPC never API grants');
            t.api.acceptAll();check(!t.api.current().analytics,'GPC overrides action');
            check(!t.events.at(-1).analytics,'GPC event denied');
        }
    }
    for(const value of [true,false]) {
        const t=boot(file,{stored:{analytics:value,ads:value}});
        check(!t.request && t.ready===1,'saved choice needs no lookup');
        check(t.api.current().analytics===value,'saved choice honored');
        t.api.reset();check(!!t.request && !t.api.current().analytics,'reset resolves country safely');
        t.answer('{"mode":"none"}');check(t.api.current().analytics,'reset US defaults');
    }
    const race=boot(file);race.api.declineAll();race.answer('{"mode":"none"}');check(!race.api.current().analytics,'late country cannot undo decline');
    const blocked=boot(file,{blocked:true});blocked.answer('{"mode":"optin"}');blocked.api.acceptAll();check(blocked.events.at(-1).analytics,'blocked storage still propagates action');
    check(blocked.api.current().analytics,'blocked storage retains choice for page');
    const blockedDecline=boot(file,{blocked:true});blockedDecline.api.declineAll();blockedDecline.answer('{"mode":"none"}');check(!blockedDecline.api.current().analytics,'blocked storage decline survives pending lookup');
    const optin=boot(file,{endpoint:'',mode:'optin'});check(!optin.api.current().analytics && !optin.request,'default model unchanged');
}
check(fs.statSync(__dirname+'/../assets/js/popup-loader.min.js').size<1024,'popup bootstrap under 1KB');
console.log(JSON.stringify({checks,passed:true}));
