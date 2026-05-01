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

    async function encryptPasswordsInUpdates(components) {
        if (!hasPasswordField(components)) {
            return;
        }

        const meta = getPublicKeyMeta();
        if (!meta) {
            throw new Error('[pw-enc] Public key meta tag not found.');
        }

        const key = await getPublicKey();
        if (!key) {
            throw new Error('[pw-enc] Public key could not be imported.');
        }

        for (const component of components) {
            const updates = component.updates;
            if (!updates || typeof updates !== 'object') {
                continue;
            }

            for (const updateKey of Object.keys(updates)) {
                const value = updates[updateKey];
                if (isPasswordKey(updateKey) && typeof value === 'string' && value.length > 0) {
                    updates[updateKey] = await encryptValue(key, value);
                }
            }
        }
    }

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

    window.passwordChangeForm = function passwordChangeForm() {
        return {
            currentPassword: '',
            newPassword: '',
            confirmPassword: '',
            showCurrent: false,
            showNew: false,
            showConfirm: false,
            publicKey: null,
            submitting: false,
            errors: {},

            async init() {
                try {
                    this.publicKey = await getPublicKey();
                } catch (err) {
                    console.error('[pw-enc] Failed to import public key for modal:', err);
                }
            },

            async encrypt(plaintext) {
                const encoded = new TextEncoder().encode(plaintext);
                const encrypted = await window.crypto.subtle.encrypt(
                    { name: 'RSA-OAEP' },
                    this.publicKey,
                    encoded,
                );
                return btoa(String.fromCharCode(...new Uint8Array(encrypted)));
            },

            validate() {
                this.errors = {};

                if (!this.currentPassword) {
                    this.errors.currentPassword = 'Current password is required.';
                }

                if (!this.newPassword) {
                    this.errors.newPassword = 'New password is required.';
                } else if (this.newPassword.length < 8) {
                    this.errors.newPassword = 'Password must be at least 8 characters.';
                } else if (!/[a-z]/.test(this.newPassword) || !/[A-Z]/.test(this.newPassword)) {
                    this.errors.newPassword = 'Password must contain both uppercase and lowercase letters.';
                } else if (!/[0-9]/.test(this.newPassword)) {
                    this.errors.newPassword = 'Password must contain at least one number.';
                } else if (!/[^a-zA-Z0-9]/.test(this.newPassword)) {
                    this.errors.newPassword = 'Password must contain at least one symbol.';
                } else if (this.newPassword === this.currentPassword) {
                    this.errors.newPassword = 'New password must differ from the current one.';
                }

                if (this.newPassword && this.confirmPassword !== this.newPassword) {
                    this.errors.confirmPassword = 'Passwords do not match.';
                }

                return Object.keys(this.errors).length === 0;
            },

            async submit() {
                if (!this.validate() || this.submitting) {
                    return;
                }

                if (!this.publicKey) {
                    notifyEncryptionFailure('Public key not loaded; please refresh the page.');
                    return;
                }

                this.submitting = true;
                try {
                    const encCurrent = await this.encrypt(this.currentPassword);
                    const encNew = await this.encrypt(this.newPassword);

                    await this.$wire.submitEncryptedPasswordChange(`ENC:${encCurrent}`, `ENC:${encNew}`);
                    this.resetForm();
                } catch (err) {
                    console.error('[pw-enc] Modal encryption/submission error:', err);
                    notifyEncryptionFailure(err);
                } finally {
                    this.submitting = false;
                }
            },

            resetForm() {
                this.currentPassword = '';
                this.newPassword = '';
                this.confirmPassword = '';
                this.errors = {};
                this.submitting = false;
            },
        };
    };

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

            if (Array.isArray(payload.components) && hasPasswordField(payload.components)) {
                try {
                    await encryptPasswordsInUpdates(payload.components);
                    init = { ...init, body: JSON.stringify(payload) };
                } catch (err) {
                    notifyEncryptionFailure(err);
                    return Promise.reject(err);
                }
            }
        }

        return originalFetch(input, init);
    };

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
