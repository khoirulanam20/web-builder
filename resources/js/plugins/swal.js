import Swal from 'sweetalert2';

// Toast notification helper
export const showToast = (message, type = 'success') => {
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        customClass: {
            popup: 'swal2-toast',
        },
        didOpen: (toast) => {
            toast.onmouseenter = Swal.stopTimer;
            toast.onmouseleave = Swal.resumeTimer;
        },
    });

    const config = {
        success: {
            icon: 'success',
            title: message,
            background: '#10b981',
            color: '#ffffff',
            iconColor: '#ffffff',
        },
        error: {
            icon: 'error',
            title: message,
            background: '#ef4444',
            color: '#ffffff',
            iconColor: '#ffffff',
        },
        warning: {
            icon: 'warning',
            title: message,
            background: '#f59e0b',
            color: '#ffffff',
            iconColor: '#ffffff',
        },
        info: {
            icon: 'info',
            title: message,
            background: '#3b82f6',
            color: '#ffffff',
            iconColor: '#ffffff',
        },
    };

    Toast.fire(config[type] || config.success);
};

// Confirmation dialog helper
export const showConfirm = (options = {}) => {
    const defaultOptions = {
        title: options.title || 'Apakah Anda yakin?',
        text: options.text || 'Tindakan ini tidak dapat dibatalkan.',
        icon: options.icon || 'warning',
        showCancelButton: true,
        confirmButtonColor: options.confirmColor || '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: options.confirmText || 'Ya, lanjutkan',
        cancelButtonText: 'Batal',
        reverseButtons: true,
    };

    return Swal.fire({ ...defaultOptions, ...options });
};

// Make it globally available
window.showToast = showToast;
window.showConfirm = showConfirm;

export default Swal;

