@php
    use App\Enums\UserRole;
    use App\Support\RoleViewMode;
    use Filament\Support\Icons\Heroicon;
    use Illuminate\View\ComponentAttributeBag;

    $user = auth()->user();
    $selected = RoleViewMode::selectedRole();
    $hiddenForDemoSuperAdmin = config('demo.enabled') && $user?->role === UserRole::SUPER_ADMIN;

    $options = [
        'actual' => ['label' => 'Actual', 'icon' => Heroicon::OutlinedIdentification],
        UserRole::STUDENT->value => ['label' => 'Student', 'icon' => Heroicon::OutlinedAcademicCap],
        UserRole::FACULTY->value => ['label' => 'Faculty', 'icon' => Heroicon::OutlinedUserGroup],
        UserRole::RR->value => ['label' => 'RR Staff', 'icon' => Heroicon::OutlinedBookOpen],
    ];
@endphp

@if (RoleViewMode::canUse($user) && ! $hiddenForDemoSuperAdmin)
    <div class="rr-role-view-switcher">
        <div class="rr-role-view-switcher-header">View as</div>
        <div class="rr-role-view-switcher-options" role="group" aria-label="View as">
            @foreach ($options as $roleValue => $option)
                @php
                    $isActive = $roleValue === 'actual' ? $selected === null : $selected?->value === $roleValue;
                @endphp

                <form method="POST" action="{{ route('role-view-mode.update') }}">
                    @csrf

                    <input type="hidden" name="role" value="{{ $roleValue }}">

                    <button
                        type="submit"
                        class="rr-role-view-switcher-btn {{ $isActive ? 'rr-role-view-switcher-btn-active' : '' }}"
                        aria-pressed="{{ $isActive ? 'true' : 'false' }}"
                        title="{{ $option['label'] }}"
                    >
                        {{ \Filament\Support\generate_icon_html($option['icon'], attributes: new ComponentAttributeBag(['class' => 'rr-role-view-switcher-icon'])) }}
                        <span>{{ $option['label'] }}</span>
                    </button>
                </form>
            @endforeach
        </div>
    </div>
@endif
