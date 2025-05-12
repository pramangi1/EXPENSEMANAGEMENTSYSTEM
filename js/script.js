function openBudgetForm() {
    document.getElementById("budget-modal").style.display = "block";
}

// Close budget form modal
function closeBudgetForm() {
    document.getElementById("budget-modal").style.display = "none";
}

// Close modal when clicking outside
window.onclick = function(event) {
    let modal = document.getElementById("budget-modal");
    if (event.target === modal) {
        closeBudgetForm(); // Use the closeBudgetForm function here for consistency
    }
};

// Close modal when pressing the "Esc" key
window.onkeydown = function(event) {
    if (event.key === "Escape") {
        closeBudgetForm();
    }
};





//$(document).ready(function() {
    // $('#budget-form').on('submit', function(e) {
    //     e.preventDefault(); // Prevent the form from submitting normally

    //     var formData = $(this).serialize(); // Serialize form data

    //     $.ajax({
    //         type: 'POST',
    //         url: 'dashboard.php', // The PHP file that will handle the form submission
    //         data: formData,
    //         success: function(response) {
    //             // Handle the response
    //             var data = JSON.parse(response);
    //             if (data) {
    //                 // Display success message
    //                 $('#budget-message').text(data.message).fadeIn().delay().fadeOut();

    //                 // Update the budget values on the dashboard
    //                 $('#total-budget').text('NRS ' + data.total_budget.toFixed(2));
    //                 $('#total-expenses').text('NRS ' + data.total_expenses.toFixed(2));
    //                 $('#remaining-budget').text('NRS ' + data.remaining_budget.toFixed(2));
    //             }
    //         },
    //         error: function() {
    //             alert('Error saving budget.');
    //         }
    //     });
    // });
