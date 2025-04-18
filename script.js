/**
 * AgentConnect - Main JavaScript File
 * Contains client-side functionality for the AgentConnect platform
 */

$(document).ready(function() {
    // Initialize tooltips
    initializeTooltips();
    
    // Form validation
    initializeFormValidation();
    
    // Handle filter functionality on job listings
    initializeJobFilters();
    
    // Handle application status updates for agents
    initializeApplicationStatusUpdates();
    
    // Handle profile image uploads
    initializeImageUpload();
    
    // Handle message notifications
    initializeMessageNotifications();
});

/**
 * Initialize tooltips for better UX
 */
function initializeTooltips() {
    // Add tooltip functionality to elements with data-tooltip attribute
    $('[data-tooltip]').hover(function() {
        const tooltipText = $(this).attr('data-tooltip');
        
        if (tooltipText) {
            const tooltip = $('<div class="tooltip"></div>')
                .text(tooltipText)
                .css({
                    position: 'absolute',
                    background: 'rgba(0, 0, 0, 0.8)',
                    color: '#fff',
                    padding: '5px 10px',
                    borderRadius: '4px',
                    zIndex: 100,
                    top: $(this).offset().top + $(this).outerHeight(),
                    left: $(this).offset().left
                });
                
            $('body').append(tooltip);
        }
    }, function() {
        $('.tooltip').remove();
    });
}

/**
 * Initialize form validation
 */
function initializeFormValidation() {
    // Registration form validation
    $('#registration-form').on('submit', function(e) {
        const password = $('#password').val();
        const confirmPassword = $('#confirm_password').val();
        
        if (password !== confirmPassword) {
            e.preventDefault();
            alert('Passwords do not match!');
            return false;
        }
        
        if (password.length < 6) {
            e.preventDefault();
            alert('Password must be at least 6 characters long!');
            return false;
        }
        
        return true;
    });
    
    // Application form validation
    $('#application-form').on('submit', function(e) {
        const coverLetter = $('#cover_letter').val();
        
        if (coverLetter.length < 50) {
            e.preventDefault();
            alert('Please provide a more detailed cover letter (at least 50 characters).');
            return false;
        }
        
        return true;
    });
}

/**
 * Initialize job listing filters
 */
function initializeJobFilters() {
    $('#job-filter-form').on('submit', function(e) {
        e.preventDefault();
        
        const keyword = $('#filter-keyword').val();
        const location = $('#filter-location').val();
        const industry = $('#filter-industry').val();
        
        // AJAX request to filter jobs
        $.ajax({
            url: 'filter_jobs.php',
            type: 'POST',
            data: {
                keyword: keyword,
                location: location,
                industry: industry
            },
            success: function(response) {
                $('#job-listings-container').html(response);
            },
            error: function() {
                alert('Error filtering jobs. Please try again.');
            }
        });
    });
}

/**
 * Initialize application status updates for agents
 */
function initializeApplicationStatusUpdates() {
    $('.application-status-select').on('change', function() {
        const applicationId = $(this).data('application-id');
        const newStatus = $(this).val();
        
        // AJAX request to update application status
        $.ajax({
            url: 'update_application_status.php',
            type: 'POST',
            data: {
                application_id: applicationId,
                status: newStatus
            },
            success: function(response) {
                if (response === 'success') {
                    // Show success message
                    const statusMessage = $('<div class="status-update-message bg-green-100 text-green-700 p-2 rounded text-sm"></div>')
                        .text('Status updated successfully!')
                        .insertAfter($(this).parent());
                    
                    // Remove message after 3 seconds
                    setTimeout(function() {
                        statusMessage.fadeOut(function() {
                            $(this).remove();
                        });
                    }, 3000);
                } else {
                    alert('Error updating status. Please try again.');
                }
            }.bind(this),
            error: function() {
                alert('Error updating status. Please try again.');
            }
        });
    });
}

/**
 * Initialize profile image upload
 */
function initializeImageUpload() {
    $('#profile-image-upload').on('change', function() {
        const file = this.files[0];
        
        if (file) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                $('#profile-image-preview').attr('src', e.target.result);
            };
            
            reader.readAsDataURL(file);
            
            // Auto-submit the form when file is selected
            $('#profile-image-form').submit();
        }
    });
}

/**
 * Initialize message notifications
 */
function initializeMessageNotifications() {
    // Check for new messages every 60 seconds
    setInterval(function() {
        $.ajax({
            url: 'check_messages.php',
            type: 'GET',
            success: function(response) {
                const data = JSON.parse(response);
                
                if (data.unread_count > 0) {
                    // Update notification badge
                    $('#message-notification-badge')
                        .text(data.unread_count)
                        .removeClass('hidden');
                    
                    // Show notification if enabled
                    if (data.show_notification) {
                        const notification = $('<div class="message-notification"></div>')
                            .html(`<strong>New Message:</strong> ${data.latest_message}`)
                            .css({
                                position: 'fixed',
                                bottom: '20px',
                                right: '20px',
                                background: '#fff',
                                border: '1px solid #ddd',
                                borderLeft: '4px solid #3b82f6',
                                padding: '10px 15px',
                                borderRadius: '4px',
                                boxShadow: '0 2px 5px rgba(0,0,0,0.1)',
                                zIndex: 1000
                            })
                            .appendTo('body');
                        
                        // Remove notification after 5 seconds
                        setTimeout(function() {
                            notification.fadeOut(function() {
                                $(this).remove();
                            });
                        }, 5000);
                    }
                } else {
                    // Hide notification badge if no unread messages
                    $('#message-notification-badge').addClass('hidden');
                }
            }
        });
    }, 60000);
}
