/**
 * Redcliff Municipality Fault Reporting System
 * Main JavaScript File
 */

// Global application object
const RedcliffApp = {
    config: {
        apiBaseUrl: '/api/',
        maxFileSize: 5242880, // 5MB
        allowedExtensions: ['jpg', 'jpeg', 'png', 'gif', 'pdf'],
        autoRefreshInterval: 300000 // 5 minutes
    },
    
    // Initialize the application
    init: function() {
        this.bindEvents();
        this.initTooltips();
        this.initDataTables();
        this.initFileUpload();
        this.initFormValidation();
        this.startAutoRefresh();
    },
    
    // Bind global event handlers
    bindEvents: function() {
        // Global AJAX error handler
        $(document).ajaxError(function(event, xhr, settings, thrownError) {
            console.error('AJAX Error:', thrownError);
            RedcliffApp.showAlert('error', 'An error occurred. Please try again.');
        });
        
        // Prevent double form submission
        $('form').on('submit', function() {
            const submitBtn = $(this).find('button[type="submit"]');
            submitBtn.prop('disabled', true);
            
            setTimeout(function() {
                submitBtn.prop('disabled', false);
            }, 3000);
        });
        
        // Auto-dismiss alerts
        $('.alert[data-auto-dismiss]').each(function() {
            const alert = $(this);
            const delay = alert.data('auto-dismiss') || 5000;
            
            setTimeout(function() {
                alert.fadeOut();
            }, delay);
        });
        
        // Confirm dialogs
        $(document).on('click', '[data-confirm]', function(e) {
            const message = $(this).data('confirm');
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
        
        // Toggle password visibility
        $(document).on('click', '.toggle-password', function() {
            const target = $($(this).data('target'));
            const icon = $(this).find('i');
            
            if (target.attr('type') === 'password') {
                target.attr('type', 'text');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                target.attr('type', 'password');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });
    },
    
    // Initialize Bootstrap tooltips
    initTooltips: function() {
        if (typeof bootstrap !== 'undefined') {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }
    },
    
    // Initialize DataTables
    initDataTables: function() {
        if ($.fn.DataTable) {
            $('.data-table').DataTable({
                responsive: true,
                pageLength: 25,
                order: [[0, 'desc']],
                language: {
                    search: "Search:",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                }
            });
        }
    },
    
    // Initialize file upload functionality
    initFileUpload: function() {
        // Drag and drop file upload
        $('.file-upload-area').on('dragover', function(e) {
            e.preventDefault();
            $(this).addClass('dragover');
        });
        
        $('.file-upload-area').on('dragleave', function(e) {
            e.preventDefault();
            $(this).removeClass('dragover');
        });
        
        $('.file-upload-area').on('drop', function(e) {
            e.preventDefault();
            $(this).removeClass('dragover');
            
            const files = e.originalEvent.dataTransfer.files;
            const input = $(this).find('input[type="file"]')[0];
            
            if (input) {
                input.files = files;
                RedcliffApp.handleFileSelection(input);
            }
        });
        
        // File input change event
        $(document).on('change', 'input[type="file"]', function() {
            RedcliffApp.handleFileSelection(this);
        });
    },
    
    // Handle file selection and validation
    handleFileSelection: function(input) {
        const files = input.files;
        const fileList = $(input).closest('.file-upload-container').find('.file-list');
        
        if (!fileList.length) return;
        
        fileList.empty();
        
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            const isValid = this.validateFile(file);
            
            const fileItem = $(`
                <div class="file-item ${isValid ? '' : 'invalid'}">
                    <div class="file-item-icon">
                        <i class="fas fa-file"></i>
                    </div>
                    <div class="file-item-info">
                        <div class="file-item-name">${file.name}</div>
                        <div class="file-item-size">${this.formatFileSize(file.size)}</div>
                        ${!isValid ? '<div class="text-danger">Invalid file type or size</div>' : ''}
                    </div>
                    <div class="file-item-remove" onclick="RedcliffApp.removeFile(this, ${i})">
                        <i class="fas fa-times"></i>
                    </div>
                </div>
            `);
            
            fileList.append(fileItem);
        }
    },
    
    // Validate file
    validateFile: function(file) {
        // Check file size
        if (file.size > this.config.maxFileSize) {
            return false;
        }
        
        // Check file extension
        const extension = file.name.split('.').pop().toLowerCase();
        if (!this.config.allowedExtensions.includes(extension)) {
            return false;
        }
        
        return true;
    },
    
    // Format file size
    formatFileSize: function(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    },
    
    // Remove file from selection
    removeFile: function(element, index) {
        const fileInput = $(element).closest('.file-upload-container').find('input[type="file"]')[0];
        
        if (fileInput && fileInput.files) {
            const dt = new DataTransfer();
            const files = fileInput.files;
            
            for (let i = 0; i < files.length; i++) {
                if (i !== index) {
                    dt.items.add(files[i]);
                }
            }
            
            fileInput.files = dt.files;
            this.handleFileSelection(fileInput);
        }
    },
    
    // Initialize form validation
    initFormValidation: function() {
        // Custom validation rules
        $.validator && $.validator.addMethod('filesize', function(value, element, param) {
            const files = element.files;
            if (!files || files.length === 0) return true;
            
            for (let i = 0; i < files.length; i++) {
                if (files[i].size > param) {
                    return false;
                }
            }
            return true;
        }, 'File size must be less than {0} bytes');
        
        $.validator && $.validator.addMethod('extension', function(value, element, param) {
            const files = element.files;
            if (!files || files.length === 0) return true;
            
            for (let i = 0; i < files.length; i++) {
                const extension = files[i].name.split('.').pop().toLowerCase();
                if (!param.includes(extension)) {
                    return false;
                }
            }
            return true;
        }, 'Invalid file extension');
        
        // Form validation
        $('form[data-validate]').each(function() {
            $(this).validate({
                errorClass: 'is-invalid',
                validClass: 'is-valid',
                errorElement: 'div',
                errorPlacement: function(error, element) {
                    error.addClass('invalid-feedback');
                    element.closest('.form-group, .mb-3').append(error);
                },
                highlight: function(element) {
                    $(element).addClass('is-invalid').removeClass('is-valid');
                },
                unhighlight: function(element) {
                    $(element).addClass('is-valid').removeClass('is-invalid');
                }
            });
        });
    },
    
    // Start auto-refresh for dashboards
    startAutoRefresh: function() {
        if ($('body').hasClass('dashboard-page')) {
            setInterval(function() {
                if (document.visibilityState === 'visible') {
                    RedcliffApp.refreshDashboard();
                }
            }, this.config.autoRefreshInterval);
        }
    },
    
    // Refresh dashboard data
    refreshDashboard: function() {
        // Refresh notification count
        this.updateNotificationCount();
        
        // Refresh recent activities if present
        if ($('#recent-activities').length) {
            this.loadRecentActivities();
        }
    },
    
    // Update notification count
    updateNotificationCount: function() {
        $.get('/api/notifications/count', function(data) {
            if (data.success) {
                $('.notification-count').text(data.count);
                $('.notification-count').toggle(data.count > 0);
            }
        });
    },
    
    // Load recent activities
    loadRecentActivities: function() {
        $.get('/api/activities/recent', function(data) {
            if (data.success) {
                $('#recent-activities').html(data.html);
            }
        });
    },
    
    // Show alert message
    showAlert: function(type, message, container = 'body') {
        const alertTypes = {
            'success': 'alert-success',
            'error': 'alert-danger',
            'warning': 'alert-warning',
            'info': 'alert-info'
        };
        
        const alertClass = alertTypes[type] || 'alert-info';
        const iconClass = {
            'success': 'fa-check-circle',
            'error': 'fa-exclamation-circle',
            'warning': 'fa-exclamation-triangle',
            'info': 'fa-info-circle'
        }[type] || 'fa-info-circle';
        
        const alert = $(`
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                <i class="fas ${iconClass} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `);
        
        $(container).prepend(alert);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            alert.fadeOut();
        }, 5000);
    },
    
    // Loading state management
    showLoading: function(element) {
        const $element = $(element);
        $element.prop('disabled', true);
        
        const originalHtml = $element.html();
        $element.data('original-html', originalHtml);
        $element.html('<i class="fas fa-spinner fa-spin me-2"></i>Loading...');
    },
    
    hideLoading: function(element) {
        const $element = $(element);
        $element.prop('disabled', false);
        
        const originalHtml = $element.data('original-html');
        if (originalHtml) {
            $element.html(originalHtml);
        }
    },
    
    // AJAX helpers
    ajax: function(url, options = {}) {
        const defaults = {
            url: url,
            type: 'GET',
            dataType: 'json',
            cache: false,
            beforeSend: function() {
                if (options.loadingElement) {
                    RedcliffApp.showLoading(options.loadingElement);
                }
            },
            complete: function() {
                if (options.loadingElement) {
                    RedcliffApp.hideLoading(options.loadingElement);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                RedcliffApp.showAlert('error', 'An error occurred: ' + error);
            }
        };
        
        return $.ajax($.extend(defaults, options));
    },
    
    // Utility functions
    utils: {
        // Format date
        formatDate: function(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
        },
        
        // Time ago
        timeAgo: function(dateString) {
            const now = new Date();
            const date = new Date(dateString);
            const seconds = Math.floor((now - date) / 1000);
            
            const intervals = {
                year: 31536000,
                month: 2592000,
                week: 604800,
                day: 86400,
                hour: 3600,
                minute: 60
            };
            
            for (const [unit, secondsInUnit] of Object.entries(intervals)) {
                const interval = Math.floor(seconds / secondsInUnit);
                if (interval >= 1) {
                    return interval + ' ' + unit + (interval === 1 ? '' : 's') + ' ago';
                }
            }
            
            return 'just now';
        },
        
        // Debounce function
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },
        
        // Throttle function
        throttle: function(func, limit) {
            let inThrottle;
            return function() {
                const args = arguments;
                const context = this;
                if (!inThrottle) {
                    func.apply(context, args);
                    inThrottle = true;
                    setTimeout(() => inThrottle = false, limit);
                }
            };
        },
        
        // Generate random ID
        generateId: function() {
            return 'id_' + Math.random().toString(36).substr(2, 9);
        },
        
        // Copy to clipboard
        copyToClipboard: function(text) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(function() {
                    RedcliffApp.showAlert('success', 'Copied to clipboard');
                });
            } else {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                RedcliffApp.showAlert('success', 'Copied to clipboard');
            }
        }
    }
};

// Fault-specific functions
const FaultManager = {
    // Submit fault report
    submitFault: function(formData, callback) {
        RedcliffApp.ajax('/api/submit_fault.php', {
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    RedcliffApp.showAlert('success', 'Fault submitted successfully!');
                    if (callback) callback(response);
                } else {
                    RedcliffApp.showAlert('error', response.message || 'Failed to submit fault');
                }
            }
        });
    },
    
    // Update fault status
    updateStatus: function(faultId, status, notes, callback) {
        RedcliffApp.ajax('/api/update_fault_status.php', {
            type: 'POST',
            data: {
                fault_id: faultId,
                status: status,
                notes: notes
            },
            success: function(response) {
                if (response.success) {
                    RedcliffApp.showAlert('success', 'Status updated successfully!');
                    if (callback) callback(response);
                } else {
                    RedcliffApp.showAlert('error', response.message || 'Failed to update status');
                }
            }
        });
    },
    
    // Load fault details
    loadDetails: function(faultId, callback) {
        RedcliffApp.ajax('/api/get_fault_details.php', {
            data: { id: faultId },
            success: function(response) {
                if (response.success && callback) {
                    callback(response);
                } else {
                    RedcliffApp.showAlert('error', 'Failed to load fault details');
                }
            }
        });
    },
    
    // Track fault progress
    trackProgress: function(referenceNumber, callback) {
        RedcliffApp.ajax('/api/get_fault_details.php', {
            data: { 
                ref: referenceNumber,
                track: true 
            },
            success: function(response) {
                if (response.success && callback) {
                    callback(response);
                } else {
                    RedcliffApp.showAlert('error', 'Failed to load tracking information');
                }
            }
        });
    }
};

// Chart helpers
const ChartManager = {
    // Default chart options
    defaultOptions: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    },
    
    // Create line chart
    createLineChart: function(ctx, data, options = {}) {
        return new Chart(ctx, {
            type: 'line',
            data: data,
            options: $.extend(true, {}, this.defaultOptions, options)
        });
    },
    
    // Create bar chart
    createBarChart: function(ctx, data, options = {}) {
        return new Chart(ctx, {
            type: 'bar',
            data: data,
            options: $.extend(true, {}, this.defaultOptions, options)
        });
    },
    
    // Create pie chart
    createPieChart: function(ctx, data, options = {}) {
        return new Chart(ctx, {
            type: 'pie',
            data: data,
            options: $.extend(true, {}, this.defaultOptions, options)
        });
    },
    
    // Create doughnut chart
    createDoughnutChart: function(ctx, data, options = {}) {
        return new Chart(ctx, {
            type: 'doughnut',
            data: data,
            options: $.extend(true, {}, this.defaultOptions, options)
        });
    }
};

// Landing page enhancements
const LandingPageEnhancements = {
    init: function() {
        this.initScrollAnimations();
        this.initSmoothScrolling();
        this.initParallaxEffects();
        this.initCounterAnimations();
        this.initTypingEffect();
    },
    
    // Initialize scroll animations
    initScrollAnimations: function() {
        // Intersection Observer for scroll animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animated');
                    
                    // Ensure cards are visible and add stagger effect
                    const cards = entry.target.querySelectorAll('.feature-card, .category-card, .testimonial-card, .step-card');
                    cards.forEach((card, index) => {
                        // Ensure card is visible
                        card.style.opacity = '1';
                        card.style.visibility = 'visible';
                        card.style.display = 'block';
                        
                        setTimeout(() => {
                            card.style.animationDelay = `${index * 0.1}s`;
                            card.classList.add('animate-on-scroll');
                        }, index * 100);
                    });
                }
            });
        }, observerOptions);
        
        // Observe all animation elements
        document.querySelectorAll('.animate-on-scroll').forEach(el => {
            observer.observe(el);
        });
        
        // Observe sections for animations
        document.querySelectorAll('.features-section, .how-it-works-section, .categories-section, .testimonials-section').forEach(section => {
            observer.observe(section);
        });
    },
    
    // Initialize smooth scrolling
    initSmoothScrolling: function() {
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    },
    
    // Initialize parallax effects
    initParallaxEffects: function() {
        let ticking = false;
        
        function updateParallax() {
            const scrollTop = window.pageYOffset;
            
            // Hero parallax
            const heroSection = document.querySelector('.hero-section');
            if (heroSection) {
                const heroOffset = scrollTop * 0.5;
                heroSection.style.transform = `translateY(${heroOffset}px)`;
            }
            
            // Floating shapes
            const shapes = document.querySelectorAll('.shape');
            shapes.forEach((shape, index) => {
                const speed = (index + 1) * 0.1;
                const yPos = scrollTop * speed;
                shape.style.transform = `translateY(${yPos}px)`;
            });
            
            ticking = false;
        }
        
        function requestTick() {
            if (!ticking) {
                requestAnimationFrame(updateParallax);
                ticking = true;
            }
        }
        
        window.addEventListener('scroll', requestTick);
    },
    
    // Initialize counter animations
    initCounterAnimations: function() {
        const counters = document.querySelectorAll('.stat-item h3');
        const speed = 200; // Animation speed
        
        const observerOptions = {
            threshold: 0.5
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const counter = entry.target;
                    const target = counter.getAttribute('data-target') || counter.textContent;
                    const count = target.replace(/[^0-9]/g, '');
                    const suffix = target.replace(/[0-9]/g, '');
                    
                    if (count) {
                        this.animateCounter(counter, 0, parseInt(count), speed, suffix);
                    }
                }
            });
        }, observerOptions);
        
        counters.forEach(counter => {
            observer.observe(counter);
        });
    },
    
    // Animate counter
    animateCounter: function(element, start, end, duration, suffix = '') {
        const range = end - start;
        const increment = range / (duration / 16);
        let current = start;
        
        const timer = setInterval(() => {
            current += increment;
            if (current >= end) {
                current = end;
                clearInterval(timer);
            }
            
            element.textContent = Math.floor(current) + suffix;
        }, 16);
    },
    
    // Initialize typing effect
    initTypingEffect: function() {
        const typingElements = document.querySelectorAll('.typing-effect');
        
        typingElements.forEach(element => {
            const text = element.textContent;
            element.textContent = '';
            
            let i = 0;
            const typeWriter = () => {
                if (i < text.length) {
                    element.textContent += text.charAt(i);
                    i++;
                    setTimeout(typeWriter, 100);
                }
            };
            
            // Start typing when element is visible
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        typeWriter();
                        observer.unobserve(element);
                    }
                });
            });
            
            observer.observe(element);
        });
    }
};

// Enhanced category card interactions
const CategoryInteractions = {
    init: function() {
        this.initCategoryCards();
        this.initTestimonialCarousel();
        this.initFeatureHovers();
    },
    
    initCategoryCards: function() {
        const categoryCards = document.querySelectorAll('.category-card');
        
        categoryCards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-10px) scale(1.05)';
                this.style.boxShadow = '0 20px 40px rgba(0, 0, 0, 0.15)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
                this.style.boxShadow = '0 2px 4px rgba(0, 0, 0, 0.1)';
            });
            
            card.addEventListener('click', function() {
                // Add click animation
                this.style.transform = 'translateY(-10px) scale(0.95)';
                setTimeout(() => {
                    this.style.transform = 'translateY(-10px) scale(1.05)';
                }, 150);
            });
        });
    },
    
    initTestimonialCarousel: function() {
        const testimonialCards = document.querySelectorAll('.testimonial-card');
        let currentIndex = 0;
        
        function showNextTestimonial() {
            testimonialCards.forEach((card, index) => {
                card.style.opacity = index === currentIndex ? '1' : '0.7';
                card.style.transform = index === currentIndex ? 'scale(1)' : 'scale(0.95)';
            });
            
            currentIndex = (currentIndex + 1) % testimonialCards.length;
        }
        
        // Auto-rotate testimonials
        setInterval(showNextTestimonial, 5000);
    },
    
    initFeatureHovers: function() {
        const featureCards = document.querySelectorAll('.feature-card');
        
        featureCards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                const icon = this.querySelector('.icon-circle');
                if (icon) {
                    icon.style.transform = 'scale(1.1) rotate(5deg)';
                }
            });
            
            card.addEventListener('mouseleave', function() {
                const icon = this.querySelector('.icon-circle');
                if (icon) {
                    icon.style.transform = 'scale(1) rotate(0deg)';
                }
            });
        });
    }
};

// Initialize when document is ready
$(document).ready(function() {
    RedcliffApp.init();
    LandingPageEnhancements.init();
    CategoryInteractions.init();
    
    // Ensure all cards are visible on load
    $('.card, .feature-card, .category-card, .testimonial-card, .step-card').each(function() {
        $(this).css({
            'opacity': '1',
            'visibility': 'visible',
            'display': 'block'
        });
    });
    
    // Add loading animation
    $('body').addClass('loaded');
    
    // Add smooth page transitions
    $('a').not('[href^="#"]').not('[href^="javascript:"]').click(function(e) {
        const href = $(this).attr('href');
        if (href && !e.ctrlKey && !e.metaKey) {
            e.preventDefault();
            $('body').addClass('page-transition');
            setTimeout(() => {
                window.location.href = href;
            }, 300);
        }
    });
});

// Global functions for backward compatibility
function viewFaultDetails(faultId) {
    FaultManager.loadDetails(faultId, function(response) {
        $('#faultDetailsContent').html(response.html);
        const modal = new bootstrap.Modal(document.getElementById('faultDetailsModal'));
        modal.show();
    });
}

function trackFault(referenceNumber) {
    FaultManager.trackProgress(referenceNumber, function(response) {
        $('#trackingContent').html(response.html);
        const modal = new bootstrap.Modal(document.getElementById('trackingModal'));
        modal.show();
    });
}

function updateFaultStatus(faultId) {
    // Implementation depends on the modal structure
    const modal = new bootstrap.Modal(document.getElementById('statusModal'));
    $('#statusFaultId').val(faultId);
    modal.show();
}

function assignFault(faultId) {
    // Implementation depends on the modal structure
    const modal = new bootstrap.Modal(document.getElementById('assignModal'));
    $('#assignFaultId').val(faultId);
    modal.show();
}

function exportData(format) {
    const params = new URLSearchParams(window.location.search);
    params.set('export', format);
    
    const exportUrl = '/api/export_faults.php?' + params.toString();
    window.open(exportUrl, '_blank');
}

// Export for use in modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { RedcliffApp, FaultManager, ChartManager };
}
