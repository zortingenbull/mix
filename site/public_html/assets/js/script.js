/**
 * Custom JavaScript for DTF & T-Shirt Printing System
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize Dropzone if the element exists on the page
    if (typeof Dropzone !== 'undefined' && document.querySelector('#fileUpload')) {
        Dropzone.autoDiscover = false;
        
        new Dropzone("#fileUpload", {
            url: BASE_URL + "/files/upload",
            paramName: "file",
            maxFilesize: 10, // MB
            acceptedFiles: "image/jpeg,image/png,application/pdf",
            addRemoveLinks: true,
            dictDefaultMessage: "Drop files here or click to upload",
            dictRemoveFile: "Remove",
            dictFileTooBig: "File is too big ({{filesize}}MB). Max filesize: {{maxFilesize}}MB.",
            dictInvalidFileType: "You can't upload files of this type.",
            headers: {
                "X-CSRF-TOKEN": document.querySelector('input[name="csrf_token"]').value
            },
            init: function() {
                this.on("success", function(file, response) {
                    // Parse response if it's a string
                    if (typeof response === 'string') {
                        try {
                            response = JSON.parse(response);
                        } catch (e) {
                            console.error('Error parsing response:', e);
                        }
                    }
                    
                    if (response && response.success) {
                        // Add file ID to the file object
                        file.fileId = response.file_id;
                        
                        // Show success message
                        const alert = document.createElement('div');
                        alert.className = 'alert alert-success mt-3';
                        alert.innerHTML = '<i class="bi bi-check-circle-fill"></i> ' + response.message;
                        document.querySelector('.dropzone').insertAdjacentElement('afterend', alert);
                        
                        // Auto-hide alert after 3 seconds
                        setTimeout(function() {
                            alert.remove();
                        }, 3000);
                        
                        // Reload file list if available
                        if (document.getElementById('fileList')) {
                            loadFileList();
                        }
                    }
                });
                
                this.on("error", function(file, errorMessage) {
                    // Parse error message if it's a string
                    if (typeof errorMessage === 'string') {
                        try {
                            const response = JSON.parse(errorMessage);
                            errorMessage = response.error || errorMessage;
                        } catch (e) {
                            // Not JSON, use as is
                        }
                    }
                    
                    // Show error message
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-danger mt-3';
                    alert.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i> ' + errorMessage;
                    document.querySelector('.dropzone').insertAdjacentElement('afterend', alert);
                    
                    // Auto-hide alert after 5 seconds
                    setTimeout(function() {
                        alert.remove();
                    }, 5000);
                });
            }
        });
    }
    
    // Function to load file list
    window.loadFileList = function() {
        const fileListElement = document.getElementById('fileList');
        const orderId = document.querySelector('input[name="order_id"]')?.value;
        
        if (fileListElement && orderId) {
            fetch(BASE_URL + '/files/list/' + orderId)
                .then(response => response.text())
                .then(data => {
                    fileListElement.innerHTML = data;
                })
                .catch(error => {
                    console.error('Error fetching files:', error);
                    fileListElement.innerHTML = '<p class="text-danger">Error loading files</p>';
                });
        }
    };
    
    // Initialize Bootstrap tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize Bootstrap popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Auto-hide alerts after 5 seconds
    const autoHideAlerts = document.querySelectorAll('.alert-auto-hide');
    autoHideAlerts.forEach(function(alert) {
        setTimeout(function() {
            alert.classList.add('fade');
            setTimeout(function() {
                alert.remove();
            }, 500);
        }, 5000);
    });
});