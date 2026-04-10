{{--
  Client-side RSA-OAEP password encryption for Livewire payloads.
  Injected into every Filament panel page via the BODY_END render hook.

  Patches window.fetch so that any Livewire update request (/livewire/update)
  has its password-named fields encrypted before they leave the browser.
  The server-side DecryptLivewirePasswords middleware reverses this.

  The profile-page "Change Password" modal uses its own Alpine component and
  sends already-encrypted values via a direct Livewire method call (not through
  the model-binding updates map), so it is unaffected by this interceptor.
--}}
@php
    $keyPath = storage_path('app/keys/password_public.pem');
    $publicKey = file_exists($keyPath) ? trim(file_get_contents($keyPath)) : null;
@endphp

@if ($publicKey)
<meta name="pw-enc-key" content="{{ $publicKey }}">

<script>
(function () {
    'use strict';

    // ── 1. Import the RSA public key once, lazily ───────────────────────────

    let _keyPromise = null;

    function getPublicKey() {
        if (_keyPromise) return _keyPromise;

        const meta = document.querySelector('meta[name="pw-enc-key"]');
        if (! meta) return Promise.resolve(null);

        const pem = meta.getAttribute('content');
        const b64 = pem.replace(/-----[^-]+-----/g, '').replace(/\s/g, '');
        const binary = atob(b64);
        const bytes = new Uint8Array(binary.length);
        for (let i = 0; i < binary.length; i++) bytes[i] = binary.charCodeAt(i);

        _keyPromise = window.crypto.subtle.importKey(
            'spki',
            bytes.buffer,
            { name: 'RSA-OAEP', hash: 'SHA-1' },  // Must match PHP openssl OAEP default (SHA-1)
            false,
            ['encrypt']
        ); // Rejection propagates — callers must handle it

        return _keyPromise;
    }

    // ── 2. RSA-OAEP encrypt → Base64 ────────────────────────────────────────

    async function encryptValue(key, plaintext) {
        const encoded = new TextEncoder().encode(plaintext);
        const encrypted = await window.crypto.subtle.encrypt(
            { name: 'RSA-OAEP' },
            key,
            encoded
        );
        return 'ENC:' + btoa(String.fromCharCode(...new Uint8Array(encrypted)));
    }

    // ── 3. Walk the Livewire updates map, encrypt password-keyed fields ─────
    //
    // Throws if the key is unavailable or encryption fails so that the fetch
    // interceptor can abort the request rather than send plaintext passwords.

    function isPasswordKey(key) {
        return /password$/i.test(key);
    }

    function hasPasswordField(components) {
        for (const component of components) {
            const updates = component.updates;
            if (! updates || typeof updates !== 'object') continue;
            for (const k of Object.keys(updates)) {
                if (isPasswordKey(k) && typeof updates[k] === 'string' && updates[k].length > 0) {
                    return true;
                }
            }
        }
        return false;
    }

    async function encryptPasswordsInUpdates(components) {
        // Only touch the key if there is actually a password to protect.
        if (! hasPasswordField(components)) return;

        // This throws if the meta tag is missing or key import failed.
        const meta = document.querySelector('meta[name="pw-enc-key"]');
        if (! meta) throw new Error('[pw-enc] Public key meta tag not found.');

        const key = await getPublicKey(); // propagates rejection

        for (const component of components) {
            const updates = component.updates;
            if (! updates || typeof updates !== 'object') continue;

            for (const k of Object.keys(updates)) {
                const value = updates[k];
                if (isPasswordKey(k) && typeof value === 'string' && value.length > 0) {
                    updates[k] = await encryptValue(key, value); // propagates rejection
                }
            }
        }
    }

    // ── 4. User-visible error when encryption is unavailable ────────────────

    function notifyEncryptionFailure(reason) {
        console.error('[pw-enc] Cannot encrypt password field:', reason);

        // Show a Filament-style danger notification if the stack is ready,
        // otherwise fall back to a plain alert so the user is never left
        // staring at a spinner with no explanation.
        try {
            window.FilamentNotification
                ?.make?.()
                ?.title?.('Password submission blocked')
                ?.body?.('Encryption is temporarily unavailable. Your password was NOT sent. Please refresh the page and try again, or contact the administrator if the problem persists.')
                ?.danger?.()
                ?.persistent?.()
                ?.send?.();
        } catch {
            alert(
                'Security error: password encryption is unavailable.\n' +
                'Your password was NOT submitted. Please refresh and try again.'
            );
        }
    }

    // ── 5a. User-visible error when encryption is unavailable ───────────────
    //
    // Only requests that contain a password-named field are affected.
    // All other Livewire traffic (page updates, table interactions, etc.)
    // passes through without any modification or delay.

    const _originalFetch = window.fetch.bind(window);

    // ── 5b. Alpine component for profile change-password modal ──────────────
    //
    // Defined here (not in the modal blade) so it survives Livewire DOM morphing:
    // script tags inside Livewire-rendered modal content are not re-executed.

    window.passwordChangeForm = function () {
        return {
            currentPassword:  '',
            newPassword:      '',
            confirmPassword:  '',
            showCurrent:      false,
            showNew:          false,
            showConfirm:      false,
            publicKey:        null,
            submitting:       false,
            errors:           {},

            async init() {
                try {
                    this.publicKey = await getPublicKey();
                } catch (err) {
                    console.error('[pw-enc] Failed to import public key for modal:', err);
                }
            },

            async encrypt(plaintext) {
                const encoded   = new TextEncoder().encode(plaintext);
                const encrypted = await window.crypto.subtle.encrypt(
                    { name: 'RSA-OAEP' },
                    this.publicKey,
                    encoded
                );
                return btoa(String.fromCharCode(...new Uint8Array(encrypted)));
            },

            validate() {
                this.errors = {};
                if (! this.currentPassword) {
                    this.errors.currentPassword = 'Current password is required.';
                }
                if (! this.newPassword) {
                    this.errors.newPassword = 'New password is required.';
                } else if (this.newPassword.length < 8) {
                    this.errors.newPassword = 'Password must be at least 8 characters.';
                } else if (! /[a-z]/.test(this.newPassword) || ! /[A-Z]/.test(this.newPassword)) {
                    this.errors.newPassword = 'Password must contain both uppercase and lowercase letters.';
                } else if (! /[0-9]/.test(this.newPassword)) {
                    this.errors.newPassword = 'Password must contain at least one number.';
                } else if (! /[^a-zA-Z0-9]/.test(this.newPassword)) {
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
                if (! this.validate() || this.submitting) return;

                if (! this.publicKey) {
                    notifyEncryptionFailure('Public key not loaded — please refresh the page.');
                    return;
                }

                this.submitting = true;
                try {
                    const encCurrent = await this.encrypt(this.currentPassword);
                    const encNew     = await this.encrypt(this.newPassword);

                    // Prefix with ENC: to match server-side stripEncPrefix()
                    // $wire is an Alpine magic property — access via this.$wire
                    await this.$wire.submitEncryptedPasswordChange('ENC:' + encCurrent, 'ENC:' + encNew);

                    this.resetForm();
                } catch (err) {
                    console.error('[pw-enc] Modal encryption/submission error:', err);
                    notifyEncryptionFailure(err);
                } finally {
                    this.submitting = false;
                }
            },

            resetForm() {
                this.currentPassword  = '';
                this.newPassword      = '';
                this.confirmPassword  = '';
                this.errors           = {};
                this.submitting       = false;
            },
        };
    };

    // ── 6. Patch window.fetch ────────────────────────────────────────────────
    //
    // Only requests that contain a password-named field are affected.
    // All other Livewire traffic (page updates, table interactions, etc.)
    // passes through without any modification or delay.

    window.fetch = async function (input, init) {
        const url = typeof input === 'string' ? input : input?.url ?? '';

        if (/\/livewire[^/]*\/update/.test(url) && init?.body) {
            let payload;
            try {
                payload = JSON.parse(init.body);
            } catch {
                return _originalFetch(input, init); // not JSON — pass through
            }

            if (Array.isArray(payload.components) && hasPasswordField(payload.components)) {
                try {
                    await encryptPasswordsInUpdates(payload.components);
                    init = { ...init, body: JSON.stringify(payload) };
                } catch (err) {
                    // Encryption failed — show a clear message and BLOCK the
                    // request so no plaintext password leaves the browser.
                    notifyEncryptionFailure(err);
                    return Promise.reject(err);
                }
            }
        }

        return _originalFetch(input, init);
    };
})();
</script>
@endif
