/**
 * Main JavaScript for GCSE Tracker
 */

// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Topic expansion functionality
    const topicHeaders = document.querySelectorAll('.topic-header');
    if (topicHeaders) {
        topicHeaders.forEach(header => {
            header.addEventListener('click', function() {
                const topicContent = this.nextElementSibling;
                const icon = this.querySelector('.topic-toggle-icon');
                
                // Toggle the content visibility
                if (topicContent.style.maxHeight) {
                    topicContent.style.maxHeight = null;
                    icon.classList.replace('fa-chevron-down', 'fa-chevron-right');
                } else {
                    topicContent.style.maxHeight = topicContent.scrollHeight + "px";
                    icon.classList.replace('fa-chevron-right', 'fa-chevron-down');
                }
            });
        });
    }
    
    // Progress update functionality
    const progressForms = document.querySelectorAll('.progress-update-form');
    if (progressForms) {
        progressForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const subtopicId = this.dataset.subtopicId;
                
                // AJAX request to update progress
                fetch('/includes/update_progress.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update UI elements
                        const statusBadge = document.querySelector(`#status-badge-${subtopicId}`);
                        const confidenceIndicator = document.querySelector(`#confidence-indicator-${subtopicId}`);
                        
                        if (statusBadge) {
                            statusBadge.className = `status-badge status-${data.status}`;
                            statusBadge.textContent = data.status_text;
                        }
                        
                        if (confidenceIndicator) {
                            confidenceIndicator.className = `confidence-indicator confidence-${data.confidence}`;
                        }
                        
                        // Show success message
                        showAlert('Progress updated successfully!', 'success');
                    } else {
                        // Show error message
                        showAlert('Error updating progress: ' + data.message, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('An error occurred while updating progress.', 'danger');
                });
            });
        });
    }
    
    // Function to show alerts
    function showAlert(message, type = 'info') {
        const alertContainer = document.getElementById('alert-container');
        if (!alertContainer) return;
        
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show`;
        alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        alertContainer.appendChild(alert);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            alert.classList.remove('show');
            setTimeout(() => {
                alertContainer.removeChild(alert);
            }, 150);
        }, 5000);
    }
    
    // Search functionality
    const searchForm = document.querySelector('form.d-flex');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const searchTerm = this.querySelector('input[type="search"]').value.trim();
            
            if (searchTerm.length > 0) {
                window.location.href = `/pages/search.php?q=${encodeURIComponent(searchTerm)}`;
            }
        });
    }
});