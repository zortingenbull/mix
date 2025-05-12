<div class="file-list">
    <?php
    $fileTypeNames = [
        FILE_TYPE_ARTWORK => 'Customer Artwork',
        FILE_TYPE_MOCKUP => 'Mockups',
        FILE_TYPE_FINAL => 'Final Prints'
    ];
    
    $hasFiles = false;
    
    foreach ($filesByType as $type => $typeFiles) {
        if (!empty($typeFiles)) {
            $hasFiles = true;
    ?>
        <h6 class="mt-3"><?php echo $fileTypeNames[$type]; ?></h6>
        <div class="list-group mb-3">
            <?php foreach ($typeFiles as $file) { ?>
                <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <div>
                        <?php
                        // Determine icon based on file extension
                        $extension = strtolower(pathinfo($file['original_filename'], PATHINFO_EXTENSION));
                        $icon = 'bi-file';
                        
                        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                            $icon = 'bi-file-image';
                        } elseif ($extension === 'pdf') {
                            $icon = 'bi-file-pdf';
                        }
                        ?>
                        <i class="bi <?php echo $icon; ?> me-2"></i>
                        <?php echo htmlspecialchars($file['original_filename']); ?>
                        <small class="text-muted ms-2">
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
                        </small>
                    </div>
                    <div class="btn-group">
                        <a href="<?php echo BASE_URL; ?>/files/download/<?php echo $file['id']; ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-download"></i>
                        </a>
                        
                        <?php if (SessionHelper::getUserRole() <= ROLE_MANAGER): ?>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteFileModal<?php echo $file['id']; ?>">
                                <i class="bi bi-trash"></i>
                            </button>
                            
                            <!-- Delete Modal -->
                            <div class="modal fade" id="deleteFileModal<?php echo $file['id']; ?>" tabindex="-1" aria-labelledby="deleteFileModalLabel<?php echo $file['id']; ?>" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="deleteFileModalLabel<?php echo $file['id']; ?>">Delete File</h5>
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
                                                <input type="hidden" name="redirect" value="/orders/<?php echo $file['order_id']; ?>">
                                                <button type="submit" class="btn btn-danger">Delete</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php } ?>
        </div>
    <?php 
        }
    }
    
    if (!$hasFiles) {
        echo '<p class="text-muted">No files uploaded yet.</p>';
    }
    ?>
</div>