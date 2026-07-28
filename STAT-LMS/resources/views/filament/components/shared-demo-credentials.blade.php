@php
    $accounts = $panel === 'admin'
        ? [
            ['role' => 'Committee', 'email' => 'committee@demo.lms'],
            ['role' => 'Staff / Custodian', 'email' => 'custodian@demo.lms'],
        ]
        : [
            ['role' => 'Student', 'email' => 'carlos.student@demo.lms'],
            ['role' => 'Faculty', 'email' => 'ricardo.faculty@demo.lms'],
        ];
@endphp

<div class="mb-6 rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
    <p class="font-semibold text-gray-950 dark:text-white">Seeded demo accounts</p>
    <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">
        Use <code class="font-semibold">password</code> for any account below, or sign in with Google to create your own student profile.
    </p>

    <dl class="mt-3 space-y-2">
        @foreach ($accounts as $account)
            <div>
                <dt class="font-medium">{{ $account['role'] }}</dt>
                <dd><code>{{ $account['email'] }}</code></dd>
            </div>
        @endforeach
    </dl>
</div>
