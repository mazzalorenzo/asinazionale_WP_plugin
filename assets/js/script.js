/**
 * Asinazionale Plugin - Frontend error messaging
 * Displays error messages with Material Design 3 styling and auto-dismiss after 5 seconds
 */


document.addEventListener("DOMContentLoaded", function() {
    console.log("Page loaded");
    const errors = document.querySelectorAll(".alert-warning");
    errors.forEach(error => {
        setTimeout(() => {
            error.remove();
        }, 5000);
    });
});