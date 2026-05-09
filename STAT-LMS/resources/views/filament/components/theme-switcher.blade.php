<div
    x-data="{
        theme: 'light',
        init() {
            this.theme = window.statLmsTheme?.get?.() ?? 'light';
            window.addEventListener('stat-lms-theme:changed', (event) => {
                this.theme = event.detail?.theme ?? 'system';
            });
        },
        setTheme(nextTheme) {
            window.statLmsTheme?.apply?.(nextTheme);
        },
        closeDropdown(event) {
            if (typeof close === 'function') {
                close(event);
            }
        },
        isActive(target) {
            return this.theme === target;
        },
    }"
    class="rr-theme-switcher"
>
    <div class="rr-theme-switcher-header">Theme</div>
    <div class="rr-theme-switcher-options" role="group" aria-label="Theme">
        <button
            type="button"
            class="rr-theme-switcher-btn"
            :class="{ 'rr-theme-switcher-btn-active': isActive('light') }"
            @click="setTheme('light'); closeDropdown($event)"
            aria-label="Light theme"
            title="Light"
        >
            {{ \Filament\Support\generate_icon_html(\Filament\Support\Icons\Heroicon::OutlinedSun, attributes: new \Illuminate\View\ComponentAttributeBag(['class' => 'rr-theme-switcher-icon'])) }}
            <span>Light</span>
        </button>
        <button
            type="button"
            class="rr-theme-switcher-btn"
            :class="{ 'rr-theme-switcher-btn-active': isActive('dark') }"
            @click="setTheme('dark'); closeDropdown($event)"
            aria-label="Dark theme"
            title="Dark"
        >
            {{ \Filament\Support\generate_icon_html(\Filament\Support\Icons\Heroicon::OutlinedMoon, attributes: new \Illuminate\View\ComponentAttributeBag(['class' => 'rr-theme-switcher-icon'])) }}
            <span>Dark</span>
        </button>
        <button
            type="button"
            class="rr-theme-switcher-btn"
            :class="{ 'rr-theme-switcher-btn-active': isActive('dark-oled') }"
            @click="setTheme('dark-oled'); closeDropdown($event)"
            aria-label="OLED theme"
            title="OLED"
        >
            {{ \Filament\Support\generate_icon_html(\Filament\Support\Icons\Heroicon::OutlinedSparkles, attributes: new \Illuminate\View\ComponentAttributeBag(['class' => 'rr-theme-switcher-icon'])) }}
            <span>OLED</span>
        </button>
        <button
            type="button"
            class="rr-theme-switcher-btn"
            :class="{ 'rr-theme-switcher-btn-active': isActive('system') }"
            @click="setTheme('system'); closeDropdown($event)"
            aria-label="System theme"
            title="System"
        >
            {{ \Filament\Support\generate_icon_html(\Filament\Support\Icons\Heroicon::OutlinedComputerDesktop, attributes: new \Illuminate\View\ComponentAttributeBag(['class' => 'rr-theme-switcher-icon'])) }}
            <span>System</span>
        </button>
    </div>
</div>
