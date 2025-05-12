<?php include APP_PATH . '/views/layouts/header.php'; ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Upload Files</h2>
                </div>
                <div class="card-body">
                    <?php if (isset($flash)): ?>
                        <div class="alert alert-<?php echo $flash['type']; ?>" role="alert">
                            <?php echo $flash['message']; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="<?php echo BASE_URL; ?>/files/upload" method="post" enctype="multipart/form-data" id="uploadForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        
                        <?php if (isset($order)): ?>
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Order</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($order['order_number'] . ' - ' . $order['customer_name']); ?>" readonly>
                            </div>
                        <?php else: ?>
                            <div class="mb-3">
                                <label for="order_id" class="form-label">Order</label>
                                <select class="form-select" id="order_id" name="order_id" required>
                                    <option value="">Select an order</option>
                                    <?php foreach ($orders as $orderItem): ?>
                                        <option value="<?php echo $orderItem['id']; ?>">
                                            <?php echo htmlspecialchars($orderItem['order_number'] . ' - ' . $orderItem['customer_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="file_type" class="form-label">File Type</label>
                            <select class="form-select" id="file_type" name="file_type" required>
                                <option value="<?php echo FILE_TYPE_ARTWORK; ?>">Customer Artwork</option>
                                <option value="<?php echo FILE_TYPE_MOCKUP; ?>">Mockup</option>
                                <option value="<?php echo FILE_TYPE_FINAL; ?>">Final Print</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="file" class="form-label">File</label>
                            <input class="form-control" type="file" id="file" name="file" required>
                            <div class="form-text">Allowed file types: JPG, PNG, PDF. Maximum size: 10MB.</div>
                        </div>
                        
                        <div id="uploadProgress" class="progress mb-3 d-none">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="<?php echo isset($order) ? BASE_URL . '/orders/' . $order['id'] : BASE_URL . '/files'; ?>" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Upload</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Recently Uploaded Files -->
            <?php if (isset($order)): ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <h3 class="card-title">Recently Uploaded Files</h3>
                    </div>
                    <div class="card-body">
                        <div id="fileList">
                            <p class="text-center">Loading files...</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('uploadForm');
        const progressBar = document.querySelector('#uploadProgress .progress-bar');
        const progressContainer = document.getElementById('uploadProgress');
        
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(form);
            const xhr = new XMLHttpRequest();
            
            xhr.open('POST', form.action, true);
            
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const percent = Math.round((e.loaded / e.total) * 100);
                    progressContainer.classList.remove('d-none');
                    progressBar.style.width = percent + '%';
                    progressBar.textContent = percent + '%';
                }
            });
            
            xhr.addEventListener('load', function() {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        
                        if (response.success) {
                            // Show success message
                            const alert = document.createElement('div');
                            alert.className = 'alert alert-success';
                            alert.innerHTML = '<i class="bi bi-check-circle-fill"></i> ' + response.message;
                            form.insertAdjacentElement('beforebegin', alert);
                            
                            // Reset form
                            form.reset();
                            progressContainer.classList.add('d-none');
                            
                            // Reload file list if available
                            if (document.getElementById('fileList')) {
                                loadFileList();
                            }
                            
                            // Auto-hide alert after 3 seconds
                            setTimeout(function() {
                                alert.remove();
                            }, 3000);
                        } else {
                            // Show error message
                            const alert = document.createElement('div');
                            alert.className = 'alert alert-danger';
                            alert.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i> ' + response.error;
                            form.insertAdjacentElement('beforebegin', alert);
                            
                            progressContainer.classList.add('d-none');
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                    }
                } else {
                    // Show error message
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-danger';
                    alert.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i> Upload failed. Please try again.';
                    form.insertAdjacentElement('beforebegin', alert);
                    
                    progressContainer.classList.add('d-none');
                }
            });
            
            xhr.addEventListener('error', function() {
                // Show error message
                const alert = document.createElement('div');
                alert.className = 'alert alert-danger';
                alert.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i> Network error. Please try again.';
                form.insertAdjacentElement('beforebegin', alert);
                
                progressContainer.classList.add('d-none');
            });
            
            xhr.send(formData);
        });
        
        // Load file list if on order page
        if (document.getElementById('fileList')) {
            loadFileList();
        }
        
        function loadFileList() {
            const orderId = <?php echo isset($order) ? $order['id'] : 'null'; ?>;
            
            if (orderId) {
                fetch('<?php echo BASE_URL; ?>/files/list/' + orderId)
                    .then(response => response.text())
                    .then(data => {
                        document.getElementById('fileList').innerHTML = data;
                    })
                    .catch(error => {
                        console.error('Error fetching files:', error);
                        document.getElementById('fileList').innerHTML = '<p class="text-danger">Error loading files</p>';
                    });
            }
        }
    });
</script>

<?php include APP_PATH . '/views/layouts/footer.php'; ?>