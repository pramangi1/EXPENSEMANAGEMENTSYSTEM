document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('addExpenseForm');
    const referenceBox = document.getElementById('referenceBox');
    const referenceMsg = document.getElementById('referenceMessage');

    if (!form) return;

    form.addEventListener('submit', function (event) {
        event.preventDefault();

        const formData = new FormData(form);

        fetch('addexpense.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'error') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message,
                    confirmButtonColor: '#d33'
                });

                // Show reference if available
                if (data.reference) {
                    referenceMsg.textContent = data.reference;
                    referenceBox.style.display = "block";
                }

            } else if (data.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: data.message,
                    confirmButtonColor: '#3085d6'
                }).then(() => {
                    window.location.href = 'list.php'; // ✅ Redirect to list.php
                });

                form.reset();

                // Optional: update reference box after success (not shown in alert)
                if (data.reference) {
                    referenceMsg.textContent = data.reference;
                    referenceBox.style.display = "block";
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error', 'Something went wrong while submitting the form.', 'error');
        });
    });
});
