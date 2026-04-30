{{--
  Client-side RSA-OAEP password encryption for Livewire payloads.
  Rendered only on login/profile routes where password fields are present.

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
    $shouldLoadScript = request()->is('admin/login', 'app/login', 'admin/profile', 'app/profile');
@endphp

@if ($publicKey && $shouldLoadScript)
<meta name="pw-enc-key" content="{{ $publicKey }}">
@vite('resources/js/password-encryption.js')
@endif
