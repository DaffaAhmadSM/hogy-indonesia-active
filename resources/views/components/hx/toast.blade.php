<div id="toast-container" class="fixed top-5 right-5 z-50 flex flex-col items-end space-y-2"></div>


<script>
    // 1. HTMX Toast Handler
    document.body.addEventListener('htmx:afterRequest', function (event) {
        const triggerHeader = event.detail.xhr.getResponseHeader('HX-Trigger-toast');
        if (!triggerHeader) return;

        try {
            const triggers = JSON.parse(triggerHeader);
            if (triggers.showToast) {
                const { message, type = 'info' } = triggers.showToast;
                showToast(message, type);
            }
        } catch (e) {
            console.error("Could not parse HX-Trigger header:", e);
        }
    });

    function showToast(message, type = 'info') {
        const container = document.getElementById('toast-container');
        if (!container) return;

        const toast = document.createElement('div');

        const typeClasses = {
            success: 'bg-green-100 border-green-400 text-green-700',
            error: 'bg-red-100 border-red-400 text-red-700',
            info: 'bg-blue-100 border-blue-400 text-blue-700',
        };

        toast.className = `max-w-xs ${typeClasses[type] || typeClasses.info} border-l-4 p-4 rounded-md shadow-lg transition-all duration-300 transform translate-x-full opacity-0`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `<div class="flex"><div class="py-1"><svg class="fill-current h-6 w-6 mr-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm12.73-1.41A8 8 0 1 0 4.34 4.34a8 8 0 0 0 11.32 11.32zM9 11V9h2v6H9v-4zm0-6h2v2H9V5z"/></svg></div><div><p class="font-bold">${type.charAt(0).toUpperCase() + type.slice(1)}</p><p class="text-sm">${message}</p></div></div>`;

        container.appendChild(toast);

        requestAnimationFrame(() => {
            toast.classList.remove('translate-x-full', 'opacity-0');
        });

        setTimeout(() => {
            toast.classList.add('opacity-0', 'translate-x-full');
            toast.addEventListener('transitionend', () => toast.remove());
        }, 5000);
    }
</script>