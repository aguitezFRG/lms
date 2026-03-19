<div x-data="logViewTracker('{{ $wireId }}')"></div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('logViewTracker', (wireId) => ({
        logged: false,
        timer: null,
        init() {
            const wire = Livewire.find(wireId);
            if (!wire) return;

            this.timer = setTimeout(() => {
                if (!this.logged) {
                    this.logged = true;
                    wire.dispatch('logView');
                    // console.log('Logged view after delay');
                }
            }, 3000);

            document.addEventListener('visibilitychange', () => {
                if (document.hidden) clearTimeout(this.timer);
            });
        }
    }));
});
</script>