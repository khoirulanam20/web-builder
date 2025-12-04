import { watch, nextTick, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { showToast } from '@/plugins/swal';

let processedFlashKeys = new Set();

export function useFlashMessages() {
    const page = usePage();

    const handleFlash = () => {
        const flash = page.props.flash;
        if (!flash) return;

        // Get actual values
        const success = flash.success;
        const error = flash.error;
        const warning = flash.warning;
        const info = flash.info;

        // Skip if no flash messages
        if (!success && !error && !warning && !info) return;

        // Create unique key for each message
        const messages = [];
        if (success) messages.push(`success:${success}`);
        if (error) messages.push(`error:${error}`);
        if (warning) messages.push(`warning:${warning}`);
        if (info) messages.push(`info:${info}`);
        
        const flashKey = messages.join('|');
        
        // Check if we've already processed this exact flash message
        if (processedFlashKeys.has(flashKey)) return;
        processedFlashKeys.add(flashKey);

        // Clear old keys after 5 seconds to allow same message to show again if needed
        setTimeout(() => {
            processedFlashKeys.delete(flashKey);
        }, 5000);

        // Small delay to ensure DOM is ready
        nextTick(() => {
            if (success) {
                showToast(success, 'success');
            }
            if (error) {
                showToast(error, 'error');
            }
            if (warning) {
                showToast(warning, 'warning');
            }
            if (info) {
                showToast(info, 'info');
            }
        });
    };

    // Handle on mount
    onMounted(() => {
        handleFlash();
    });

    // Watch for flash changes
    watch(
        () => page.props.flash,
        (newFlash) => {
            if (newFlash) {
                handleFlash();
            }
        },
        { deep: true, immediate: true }
    );
}

