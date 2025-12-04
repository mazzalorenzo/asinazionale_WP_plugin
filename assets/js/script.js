/**
 * Asinazionale Plugin - Frontend error messaging
 * Displays error messages with Material Design 3 styling and auto-dismiss after 5 seconds
 */

(function() {
    'use strict';

    /**
     * Display error message with auto-dismiss after 5 seconds
     * @param {string} message - Error message to display
     * @param {string} container - CSS selector for container (default: form wrapper)
     */
    window.asinazionaleShowError = function(message, container = '.asinazionale') {
        const formContainer = document.querySelector(container);
        if (!formContainer) return;

        // Remove any existing error messages
        const existingError = formContainer.querySelector('.asinazionale-error');
        if (existingError) {
            existingError.remove();
        }

        // Create error element
        const errorDiv = document.createElement('div');
        errorDiv.className = 'asinazionale-error';
        errorDiv.textContent = message;
        errorDiv.setAttribute('role', 'alert');
        errorDiv.setAttribute('aria-live', 'polite');

        // Insert before form
        formContainer.parentNode.insertBefore(errorDiv, formContainer);

        // Auto-remove after 5.3 seconds (animation + buffer)
        setTimeout(function() {
            if (errorDiv.parentNode) {
                errorDiv.remove();
            }
        }, 5300);
    };

    /**
     * Hide any displayed error messages
     */
    window.asinazionaleHideError = function() {
        const errorMessages = document.querySelectorAll('.asinazionale-error');
        errorMessages.forEach(function(msg) {
            msg.remove();
        });
    };
})();
