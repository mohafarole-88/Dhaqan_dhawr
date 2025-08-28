// Toast Notification System
function showToast(message, type = 'info', duration = 5000) {
    const container = document.getElementById('toastContainer');
    if (!container) return;
    
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    const icons = {
        success: '✅',
        error: '❌',
        warning: '⚠️',
        info: 'ℹ️'
    };
    
    toast.innerHTML = `
        <div class="toast-content">
            <span class="toast-icon">${icons[type] || icons.info}</span>
            <span class="toast-message">${message}</span>
        </div>
        <button class="toast-close" onclick="removeToast(this.parentElement)">&times;</button>
        <div class="toast-progress"></div>
    `;
    
    container.appendChild(toast);
    
    // Show toast with animation
    setTimeout(() => toast.classList.add('show'), 100);
    
    // Auto remove after duration
    setTimeout(() => removeToast(toast), duration);
    
    return toast;
}

function removeToast(toast) {
    if (!toast) return;
    toast.classList.remove('show');
    setTimeout(() => {
        if (toast.parentElement) {
            toast.parentElement.removeChild(toast);
        }
    }, 300);
}

// Popup Management
function openLoginPopup() {
    document.getElementById('loginPopup').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function openSignupPopup() {
    document.getElementById('signupPopup').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closePopup(popupId) {
    document.getElementById(popupId).classList.remove('active');
    document.body.style.overflow = 'auto';
}

function switchToSignup() {
    closePopup('loginPopup');
    openSignupPopup();
}

function switchToLogin() {
    closePopup('signupPopup');
    openLoginPopup();
}

// Close popup when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('popup-overlay')) {
        closePopup(e.target.id);
    }
});

// Close popup with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const activePopup = document.querySelector('.popup-overlay.active');
        if (activePopup) {
            closePopup(activePopup.id);
        }
    }
});

// Auto-open popups based on URL parameters and show alerts
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    
    // Check if user is logged in before opening popups
    const isLoggedIn = document.querySelector('.user-menu') || document.querySelector('[href*="logout"]');
    
    // Only handle URL parameters for opening popups if user is not logged in
    if (!isLoggedIn) {
        if (urlParams.get('show_login') === '1') {
            openLoginPopup();
        } else if (urlParams.get('show_signup') === '1') {
            openSignupPopup();
        }
    }
    
    // Always clean URL parameters to prevent duplicate handling
    if (urlParams.get('error') || urlParams.get('success') || urlParams.get('show_login') || urlParams.get('show_signup')) {
        window.history.replaceState({}, document.title, window.location.pathname);
    }
    
    // Form validation and AJAX submission
    const forms = document.querySelectorAll('.popup-form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent default form submission
            
            const submitButton = form.querySelector('button[type="submit"]');
            
            // Prevent multiple submissions
            if (submitButton.disabled) {
                return false;
            }
            
            // Password validation for signup
            const passwords = form.querySelectorAll('input[type="password"]');
            if (passwords.length === 2) {
                if (passwords[0].value !== passwords[1].value) {
                    showToast('Passwords do not match!', 'error', 4000);
                    return false;
                }
            }
            
            // Submit form via AJAX
            const formData = new FormData(form);
            const originalText = submitButton.textContent;
            
            // Show loading state
            submitButton.textContent = 'Please wait...';
            submitButton.disabled = true;
            
            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    if (data.type === 'login') {
                        showToast('Login Successful! Welcome back!', 'success', 3000);
                        closePopup('loginPopup');
                    } else if (data.type === 'signup') {
                        showToast('Signup Successful! Welcome to Dhaqan Dhowr!', 'success', 3000);
                        closePopup('signupPopup');
                    }
                    // Clear form fields and reload page on success
                    form.reset();
                    // Clear URL parameters before reload to prevent popup reopening
                    window.history.replaceState({}, document.title, window.location.pathname);
                    setTimeout(() => window.location.reload(), 2000);
                } else if (data.status === 'error') {
                    // Show error message but keep form open
                    showToast(data.message, 'error', 5000);
                    // Only reset password fields on error, keep other data
                    const passwordFields = form.querySelectorAll('input[type="password"]');
                    passwordFields.forEach(field => field.value = '');
                    // Focus on first input for easy retry
                    const firstInput = form.querySelector('input');
                    if (firstInput) firstInput.focus();
                } else {
                    showToast('Something went wrong. Please try again.', 'error', 5000);
                    // Only reset password fields on unknown error
                    const passwordFields = form.querySelectorAll('input[type="password"]');
                    passwordFields.forEach(field => field.value = '');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Network error. Please check your connection and try again.', 'error', 5000);
                // Only reset password fields on network error, keep other data
                const passwordFields = form.querySelectorAll('input[type="password"]');
                passwordFields.forEach(field => field.value = '');
            })
            .finally(() => {
                // Restore button state
                submitButton.textContent = originalText;
                submitButton.disabled = false;
            });
        });
    });
    
    // Auto-hide flash messages
    const flashMessages = document.querySelectorAll('.flash-message');
    flashMessages.forEach(message => {
        setTimeout(() => {
            message.style.opacity = '0';
            setTimeout(() => {
                message.remove();
            }, 300);
        }, 5000);
    });
});
