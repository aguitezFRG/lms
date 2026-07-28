{{--
  Client-side RSA-OAEP password encryption for Livewire payloads.
  Rendered only on login/profile routes where password fields are present.

  Patches window.fetch so that any Livewire update request (/livewire/update)
  has its password-named fields encrypted before they leave the browser.
  The server-side DecryptLivewirePasswords middleware reverses this for both
  component.updates (wire:model bindings) and component.calls (Filament Action
  form submissions), including the profile-page "Change Password" modal.
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
