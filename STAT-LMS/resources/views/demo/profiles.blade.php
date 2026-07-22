<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Choose a demo profile | INSTAT Reading Room</title>
    @vite(['resources/css/app.css'])
    <style>
        @keyframes demo-profile-spin {
            from { transform: translateY(-50%) rotate(0deg); }
            to { transform: translateY(-50%) rotate(360deg); }
        }
        .demo-profile-button[aria-busy="true"] { cursor: wait; opacity: 0.72; }
        .demo-profile-spinner { display: none; }
        .demo-profile-button[aria-busy="true"] .demo-profile-spinner {
            display: block;
            animation: demo-profile-spin 0.75s linear infinite;
        }
        .demo-profile-dialog {
            position: fixed;
            inset: 0;
            height: fit-content;
            margin: auto;
            max-height: calc(100dvh - 2rem);
        }
        .demo-profile-dialog::backdrop { background: rgb(15 23 42 / 0.58); }
    </style>
</head>
<body class="min-h-screen bg-slate-100 text-slate-950">
    <main class="mx-auto flex min-h-screen max-w-6xl flex-col justify-center px-6 py-12">
        <div class="mb-8 max-w-3xl">
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-[#8d1436]">LMS Laravel demo</p>
            <h1 class="mt-3 text-4xl font-bold tracking-tight">Choose a demo profile</h1>
            <p class="mt-3 text-lg text-slate-600">Each profile uses the real Filament panels, Livewire actions, policies, and shared browser-local SQLite data.</p>
        </div>

        @if ($profiles->isEmpty())
            <div class="rounded-xl border border-amber-300 bg-amber-50 p-5 text-amber-950">
                No active demo profiles are available. Reset the browser demo to restore the seed data.
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($profiles as $profile)
                    <form method="POST" action="{{ route('demo.profiles.select') }}" data-profile-id="{{ $profile->getKey() }}">
                        @csrf
                        <input type="hidden" name="profile_id" value="{{ $profile->getKey() }}">
                        <button type="submit" @if ($selectedProfileId === $profile->getKey()) aria-current="true" @endif class="demo-profile-button relative h-full w-full rounded-xl border {{ $selectedProfileId === $profile->getKey() ? 'border-[#8d1436] ring-2 ring-[#8d1436]/15' : 'border-slate-200' }} bg-white p-5 pr-16 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-[#8d1436] hover:shadow-md">
                            <span class="block text-xs font-semibold uppercase tracking-wider text-[#8d1436]">{{ $profile->role->getLabel() }}</span>
                            <span class="mt-2 block text-lg font-semibold">{{ $profile->name }}</span>
                            <span class="mt-1 block text-sm text-slate-500">{{ $profile->email }}</span>
                            <span class="demo-profile-spinner absolute right-5 top-1/2 size-6 -translate-y-1/2 rounded-full border-2 border-slate-200 border-t-[#8d1436]" aria-hidden="true"></span>
                        </button>
                    </form>
                @endforeach
            </div>
        @endif
    </main>
    <dialog id="profile-switch-dialog" class="demo-profile-dialog w-[min(30rem,calc(100%-2rem))] rounded-2xl border-0 p-0 shadow-2xl">
        <div class="p-6">
            <h2 class="text-xl font-bold">Switch demo profile?</h2>
            <p class="mt-2 text-slate-600">You are changing to <strong id="pending-profile-name"></strong>. Your browser-local records will remain shared between profiles.</p>
            <div class="mt-6 flex justify-end gap-3">
                <button id="cancel-profile-switch" type="button" class="rounded-lg bg-slate-200 px-4 py-2 font-semibold text-slate-800">Keep current profile</button>
                <button id="confirm-profile-switch" type="button" class="rounded-lg bg-[#8d1436] px-4 py-2 font-semibold text-white">Switch profile</button>
            </div>
        </div>
    </dialog>
    <script>
        const forms = [...document.querySelectorAll('form[data-profile-id]')];
        const dialog = document.querySelector('#profile-switch-dialog');
        const pendingName = document.querySelector('#pending-profile-name');
        let selectedProfileId = @json($selectedProfileId);
        let pendingForm = null;

        function markLoading(form) {
            selectedProfileId = form.dataset.profileId;
            forms.forEach((candidate) => candidate.querySelector('.demo-profile-button')?.removeAttribute('aria-busy'));
            const button = form.querySelector('.demo-profile-button');
            button?.setAttribute('aria-busy', 'true');
        }

        forms.forEach((form) => {
            form.addEventListener('submit', (event) => {
                if (selectedProfileId && selectedProfileId !== form.dataset.profileId && form.dataset.switchConfirmed !== 'true') {
                    event.preventDefault();
                    pendingForm = form;
                    pendingName.textContent = form.querySelector('.text-lg')?.textContent?.trim() ?? 'this profile';
                    dialog.showModal();
                    return;
                }

                delete form.dataset.switchConfirmed;
                markLoading(form);
            });
        });

        document.querySelector('#cancel-profile-switch').addEventListener('click', () => {
            pendingForm = null;
            dialog.close();
        });

        document.querySelector('#confirm-profile-switch').addEventListener('click', () => {
            if (! pendingForm) return;
            const form = pendingForm;
            pendingForm = null;
            dialog.close();
            form.dataset.switchConfirmed = 'true';
            form.requestSubmit();
        });
    </script>
</body>
</html>
