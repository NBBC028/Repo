/**
 * NEUST Repository System
 * Main JavaScript File
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function(popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert-dismissible');
        alerts.forEach(function(alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);

    // Password visibility toggle
    const togglePasswordBtns = document.querySelectorAll('.toggle-password');
    togglePasswordBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            const passwordField = document.querySelector(this.getAttribute('data-target'));
            const icon = this.querySelector('i');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });

    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });

    // Research file upload validation
    const researchFileInput = document.getElementById('research_file');
    if (researchFileInput) {
        researchFileInput.addEventListener('change', function() {
            const fileSize = this.files[0].size / 1024 / 1024; // in MB
            const fileType = this.files[0].type;
            const validFileTypes = ['application/pdf'];
            const maxSize = 10; // 10MB
            
            let errorMessage = '';
            
            if (fileSize > maxSize) {
                errorMessage = `File size exceeds ${maxSize}MB limit.`;
            } else if (!validFileTypes.includes(fileType)) {
                errorMessage = 'Only PDF files are allowed.';
            }
            
            const feedbackElement = document.getElementById('file-feedback');
            if (errorMessage) {
                this.value = ''; // Clear the file input
                if (feedbackElement) {
                    feedbackElement.textContent = errorMessage;
                    feedbackElement.style.display = 'block';
                } else {
                    // Create feedback element if it doesn't exist
                    const newFeedback = document.createElement('div');
                    newFeedback.id = 'file-feedback';
                    newFeedback.className = 'invalid-feedback d-block';
                    newFeedback.textContent = errorMessage;
                    this.parentNode.appendChild(newFeedback);
                }
            } else if (feedbackElement) {
                feedbackElement.style.display = 'none';
            }
        });
    }

    // Password strength meter
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const passwordStrength = document.getElementById('password-strength');
    
    if (passwordInput && passwordStrength) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            let feedback = '';
            
            // Length check
            if (password.length >= 8) {
                strength += 1;
            }
            
            // Contains lowercase
            if (password.match(/[a-z]/)) {
                strength += 1;
            }
            
            // Contains uppercase
            if (password.match(/[A-Z]/)) {
                strength += 1;
            }
            
            // Contains number
            if (password.match(/[0-9]/)) {
                strength += 1;
            }
            
            // Contains special character
            if (password.match(/[^a-zA-Z0-9]/)) {
                strength += 1;
            }
            
            // Update strength meter
            switch (strength) {
                case 0:
                    passwordStrength.style.width = '0%';
                    passwordStrength.className = 'progress-bar';
                    feedback = 'No password entered';
                    break;
                case 1:
                    passwordStrength.style.width = '20%';
                    passwordStrength.className = 'progress-bar bg-danger';
                    feedback = 'Very weak';
                    break;
                case 2:
                    passwordStrength.style.width = '40%';
                    passwordStrength.className = 'progress-bar bg-warning';
                    feedback = 'Weak';
                    break;
                case 3:
                    passwordStrength.style.width = '60%';
                    passwordStrength.className = 'progress-bar bg-info';
                    feedback = 'Moderate';
                    break;
                case 4:
                    passwordStrength.style.width = '80%';
                    passwordStrength.className = 'progress-bar bg-primary';
                    feedback = 'Strong';
                    break;
                case 5:
                    passwordStrength.style.width = '100%';
                    passwordStrength.className = 'progress-bar bg-success';
                    feedback = 'Very strong';
                    break;
            }
            
            document.getElementById('password-feedback').textContent = feedback;
        });
    }
    
    // Password match validation
    if (passwordInput && confirmPasswordInput) {
        confirmPasswordInput.addEventListener('input', function() {
            if (this.value !== passwordInput.value) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
        
        passwordInput.addEventListener('input', function() {
            if (confirmPasswordInput.value !== '') {
                if (confirmPasswordInput.value !== this.value) {
                    confirmPasswordInput.setCustomValidity('Passwords do not match');
                } else {
                    confirmPasswordInput.setCustomValidity('');
                }
            }
        });
    }

    // Abstract modal functionality
    const abstractModal = document.getElementById('abstractModal');
    if (abstractModal) {
        abstractModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const title = button.getAttribute('data-title');
            const abstract = button.getAttribute('data-abstract');
            const keywords = button.getAttribute('data-keywords');
            const pdfLink = button.getAttribute('data-pdf');
            
            document.getElementById('modal-title').textContent = title;
            document.getElementById('modal-abstract').textContent = abstract;
            document.getElementById('modal-keywords').textContent = keywords;
            
            const pdfLinkElement = document.getElementById('modal-pdf-link');
            if (pdfLinkElement && pdfLink) {
                pdfLinkElement.href = pdfLink;
            }
        });
    }

    // Dashboard charts initialization
    const researchByDeptChart = document.getElementById('researchByDeptChart');
    if (researchByDeptChart) {
        const ctx = researchByDeptChart.getContext('2d');
        const chartData = JSON.parse(researchByDeptChart.getAttribute('data-chart'));
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartData.labels,
                datasets: [{
                    label: 'Research Papers',
                    data: chartData.values,
                    backgroundColor: 'rgba(0, 51, 102, 0.7)',
                    borderColor: 'rgba(0, 51, 102, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    }

    // Quick search functionality
    const quickSearchForm = document.getElementById('quickSearchForm');
    if (quickSearchForm) {
        quickSearchForm.addEventListener('submit', function(event) {
            const searchInput = document.getElementById('quickSearch');
            if (searchInput.value.trim() === '') {
                event.preventDefault();
                searchInput.focus();
            }
        });
    }

    // Add fade-in animation to cards
    const cards = document.querySelectorAll('.card');
    cards.forEach(function(card, index) {
        card.style.animationDelay = (index * 0.1) + 's';
        card.classList.add('fade-in');
    });
});