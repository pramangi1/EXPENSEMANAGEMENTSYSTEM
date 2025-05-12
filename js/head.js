document.addEventListener('DOMContentLoaded', function () {
    const successMessage = document.body.getAttribute('data-success');
    const alertMessage = document.body.getAttribute('data-alert-message');

    if (successMessage && successMessage.trim() !== "") {
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: successMessage,
            confirmButtonColor: '#1f6fb2'
        });
    }

    if (alertMessage && alertMessage.trim() !== "") {
        Swal.fire({
            icon: 'error',
            title: 'Budget Exceeded',
            text: alertMessage,
            confirmButtonColor: '#d33'
        });
    }
});
