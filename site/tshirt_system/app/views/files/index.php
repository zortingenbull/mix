<?php include APP_PATH . '/views/layouts/header.php'; ?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>File Management</h1>
        <a href="<?php echo BASE_URL; ?>/files/create" class="btn btn-primary">
            <i class="bi bi-cloud-upload"></i> Upload New File
        </a>
    </div>
    
    <?php if (isset($flash)): ?>
        <div class="alert alert-<?php echo $flash['type']; ?>" role="alert">
            <?php echo $flash['message']; ?>
        </div>
    <?php endif; ?>
    
    <!-- Files Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>File Name</th>
                            <th>Order</th>
                            <th>Type</th>
                            <th>Size</th>
                            <th>Uploaded By</th>
                            <th>Uploaded Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($files)): ?>
                            <tr>
                                <td colspan="8" class="text-center">No files found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($files as $file): ?>
                                <tr>
                                    <td><?php echo $file['id']; ?></td>
                                    <td><?php echo htmlspecialchars($file['original_filename']); ?></td>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>/orders/<?php echo $file['order_id']; ?>">
                                            <?php echo htmlspecialchars($file['order_number']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php
                                        $fileTypes = [
                                            FILE_TYPE_ARTWORK => 'Customer Artwork',
                                            FILE_TYPE_MOCKUP => 'Mockup',
                                            FILE_TYPE_FINAL => 'Final Print'
                                        ];
                                        
                                        echo $fileTypes[$file['file_type']] ?? 'Unknown';
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        // Format file size
                                        $size = $file['file_size'];
                                        if ($size < 1024) {
                                            echo $size . ' B';
                                        } elseif ($size < 1024 * 1024) {
                                            echo round($size / 1024, 2) . ' KB';
                                        } else {
                                            echo round($size / (1024 * 1024), 2) . ' MB';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($file['uploaded_by_name']); ?></td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($file['uploaded_at'])); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="<?php echo BASE_URL; ?>/files/download/<?php echo $file['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-download"></i>
                                            </a>
                                            
                                            <?php if (SessionHelper::getUserRole() <= ROLE_MANAGER): ?>
                                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $file['id']; ?>">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Delete Modal -->
                                        <div class="modal fade" id="deleteModal<?php echo $file['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $file['id']; ?>" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="deleteModalLabel<?php echo $file['id']; ?>">Delete File</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        Are you sure you want to delete the file <strong><?php echo htmlspecialchars($file['original_filename']); ?></strong>?
                                                        <p class="text-danger mt-2">This action cannot be undone.</p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <form action="<?php echo BASE_URL; ?>/files/delete" method="post">
                                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                            <input type="hidden" name="file_id" value="<?php echo $file['id']; ?>">
                                                            <button type="submit" class="btn btn-danger">Delete</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include APP_PATH . '/views/layouts/footer.php'; ?>