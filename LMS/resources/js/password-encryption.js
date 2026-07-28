(function () {
    'use strict';

    let keyPromise = null;

    function getPublicKeyMeta() {
        return document.querySelector('meta[name="pw-enc-key"]');
    }

    function getPublicKey() {
        if (keyPromise) {
            return keyPromise;
        }

        const meta = getPublicKeyMeta();
        if (!meta) {
            return Promise.resolve(null);
        }

        const pem = meta.getAttribute('content') ?? '';
        const b64 = pem.replace(/-----[^-]+-----/g, '').replace(/\s/g, '');
        const binary = atob(b64);
        const bytes = new Uint8Array(binary.length);

        for (let i = 0; i < binary.length; i += 1) {
            bytes[i] = binary.charCodeAt(i);
        }

        keyPromise = window.crypto.subtle.importKey(
            'spki',
            bytes.buffer,
            { name: 'RSA-OAEP', hash: 'SHA-1' },
            false,
            ['encrypt'],
        );

        return keyPromise;
    }

    async function encryptValue(key, plaintext) {
        const encoded = new TextEncoder().encode(plaintext);
        const encrypted = await window.crypto.subtle.encrypt(
            { name: 'RSA-OAEP' },
            key,
            encoded,
        );

        return `ENC:${btoa(String.fromCharCode(...new Uint8Array(encrypted)))}`;
    }

    function isPasswordKey(key) {
        return /password$/i.test(key);
    }

    // ── component.updates (wire:model bindings) ───────────────────────────────

    function hasPasswordField(components) {
        for (const component of components) {
            const updates = component.updates;
            if (!updates || typeof updates !== 'object') {
                continue;
            }

            for (const key of Object.keys(updates)) {
                if (isPasswordKey(key) && typeof updates[key] === 'string' && updates[key].length > 0) {
                    return true;
                }
            }
        }

        return false;
    }

    async function encryptPasswordsInUpdates(components, cryptoKey) {
        for (const component of components) {
            const updates = component.updates;
            if (!updates || typeof updates !== 'object') {
                continue;
            }

            for (const updateKey of Object.keys(updates)) {
                const value = updates[updateKey];
                if (isPasswordKey(updateKey) && typeof value === 'string' && value.length > 0) {
                    updates[updateKey] = await encryptValue(cryptoKey, value);
                }
            }
        }
    }

    // ── component.calls params (Filament Action form submissions) ─────────────

    function hasPasswordInValue(value) {
        if (typeof value !== 'object' || value === null) {
            return false;
        }
        if (Array.isArray(value)) {
            return value.some(hasPasswordInValue);
        }
        for (const [k, v] of Object.entries(value)) {
            if (isPasswordKey(k) && typeof v === 'string' && v.length > 0) {
                return true;
            }
            if (hasPasswordInValue(v)) {
                return true;
            }
        }

        return false;
    }

    function hasPasswordInCalls(components) {
        for (const component of components) {
            if (!Array.isArray(component.calls)) {
                continue;
            }
            for (const call of component.calls) {
                if (hasPasswordInValue(call.params)) {
                    return true;
                }
            }
        }

        return false;
    }

    async function encryptPasswordsInObject(obj, cryptoKey) {
        if (typeof obj !== 'object' || obj === null) {
            return;
        }
        if (Array.isArray(obj)) {
            for (const item of obj) {
                await encryptPasswordsInObject(item, cryptoKey);
            }

            return;
        }
        for (const k of Object.keys(obj)) {
            const v = obj[k];
            if (isPasswordKey(k) && typeof v === 'string' && v.length > 0 && !v.startsWith('ENC:')) {
                obj[k] = await encryptValue(cryptoKey, v);
            } else {
                await encryptPasswordsInObject(v, cryptoKey);
            }
        }
    }

    async function encryptPasswordsInCalls(components, cryptoKey) {
        for (const component of components) {
            if (!Array.isArray(component.calls)) {
                continue;
            }
            for (const call of component.calls) {
                if (Array.isArray(call.params)) {
                    await encryptPasswordsInObject(call.params, cryptoKey);
                }
            }
        }
    }

    // ── Failure notification ──────────────────────────────────────────────────

    function notifyEncryptionFailure(reason) {
        console.error('[pw-enc] Cannot encrypt password field:', reason);

        try {
            window.FilamentNotification
                ?.make?.()
                ?.title?.('Password submission blocked')
                ?.body?.(
                    'Encryption is temporarily unavailable. Your password was NOT sent. Please refresh the page and try again, or contact the administrator if the problem persists.',
                )
                ?.danger?.()
                ?.persistent?.()
                ?.send?.();
        } catch {
            alert(
                'Security error: password encryption is unavailable.\n' +
                    'Your password was NOT submitted. Please refresh and try again.',
            );
        }
    }

    // ── Fetch interceptor ─────────────────────────────────────────────────────

    const originalFetch = window.fetch.bind(window);

    window.fetch = async function patchedFetch(input, init) {
        const url = typeof input === 'string' ? input : input?.url ?? '';

        if (/\/livewire[^/]*\/update/.test(url) && init?.body) {
            let payload;
            try {
                payload = JSON.parse(init.body);
            } catch {
                return originalFetch(input, init);
            }

            if (Array.isArray(payload.components)) {
                const needsUpdates = hasPasswordField(payload.components);
                const needsCalls = hasPasswordInCalls(payload.components);

                if (needsUpdates || needsCalls) {
                    try {
                        const meta = getPublicKeyMeta();
                        if (!meta) {
                            throw new Error('[pw-enc] Public key meta tag not found.');
                        }

                        const cryptoKey = await getPublicKey();
                        if (!cryptoKey) {
                            throw new Error('[pw-enc] Public key could not be imported.');
                        }

                        if (needsUpdates) {
                            await encryptPasswordsInUpdates(payload.components, cryptoKey);
                        }
                        if (needsCalls) {
                            await encryptPasswordsInCalls(payload.components, cryptoKey);
                        }

                        init = { ...init, body: JSON.stringify(payload) };
                    } catch (err) {
                        notifyEncryptionFailure(err);

                        return Promise.reject(err);
                    }
                }
            }
        }

        return originalFetch(input, init);
    };

    // ── Login form submit handler ─────────────────────────────────────────────

    async function encryptPasswordForm(event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        if (form.dataset.passwordEncrypted !== 'true') {
            return;
        }

        const passwordField = form.querySelector('input[name="password"]');
        if (!(passwordField instanceof HTMLInputElement)) {
            return;
        }

        const value = passwordField.value ?? '';
        if (!value || value.startsWith('ENC:')) {
            return;
        }

        event.preventDefault();

        try {
            const key = await getPublicKey();
            if (!key) {
                throw new Error('Public key not found');
            }

            passwordField.value = await encryptValue(key, value);
            form.submit();
        } catch (err) {
            notifyEncryptionFailure(err);
        }
    }

    document.addEventListener('submit', (event) => {
        void encryptPasswordForm(event);
    }, true);
})();
