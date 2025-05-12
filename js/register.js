// js/register-alerts.js

// js/register.js

function showRegistrationError(message) {
    Swal.fire({
        title: 'Registration Failed',
        text: message,
        icon: 'error',
        confirmButtonText: 'Try Again'
    });
}

function showRegistrationSuccess() {
    Swal.fire({
        title: 'Registration Successful!',
        text: 'You can now log in to your account.',
        icon: 'success',
        confirmButtonText: 'Login Now'
    }).then(() => {
        window.location.href = 'login.php';
    });
}
