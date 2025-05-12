<?php include APP_PATH . '/views/layouts/header.php'; ?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Job #<?php echo $job['id']; ?> - <?php echo htmlspecialchars($job['order_number']); ?></h1>
        <a href="<?php echo BASE_URL; ?>/jobs" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Jobs
        </a>
    </div>
    
    <?php if (isset($flash)): ?>
        <div class="alert alert-<?php echo $flash['type']; ?>" role="alert">
            <?php echo $flash['message']; ?>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Job Information -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Job Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <th width="150">Job ID:</th>
                                    <td><?php echo $job['id']; ?></td>
                                </tr>
                                <tr>
                                    <th>Order Number:</th>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>/orders/<?php echo $job['order_id']; ?>">
                                            <?php echo htmlspecialchars($job['order_number']); ?>
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Customer:</th>
                                    <td><?php echo htmlspecialchars($job['customer_name']); ?></td>
                                </tr>
                                <tr>
                                    <th>Created:</th>
                                    <td><?php echo date('M j, Y g:i A', strtotime($job['created_at'])); ?></td>
                                </tr>
                                <tr>
                                    <th>Last Updated:</th>
                                    <td><?php echo date('M j, Y g:i A', strtotime($job['updated_at'])); ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <th width="150">Status:</th>
                                    <td>
                                        <?php
                                        $statusClasses = [
                                            STATUS_PENDING => 'bg-secondary',
                                            STATUS_QUEUED => 'bg-info text-dark',
                                            STATUS_IN_PROGRESS => 'bg-warning text-dark',
                                            STATUS_PRINTED => 'bg-primary',
                                            STATUS_SHIPPED => 'bg-success'
                                        ];
                                        
                                        $statusNames = [
                                            STATUS_PENDING => 'Pending',
                                            STATUS_QUEUED => 'Queued',
                                            STATUS_IN_PROGRESS => 'In Progress',
                                            STATUS_PRINTED => 'Printed',
                                            STATUS_SHIPPED => 'Shipped'
                                        ];
                                        
                                        $statusClass = $statusClasses[$job['status']] ?? 'bg-secondary';
                                        $statusName = $statusNames[$job['status']] ?? 'Unknown';
                                        ?>
                                        
                                        <span class="badge <?php echo $statusClass; ?>"><?php echo $statusName; ?></span>
                                        
                                        <?php if (SessionHelper::getUserRole() <= ROLE_MANAGER || (SessionHelper::getUserRole() == ROLE_PRODUCTION && $job['assigned_to'] == SessionHelper::getUserId())): ?>
                                            <button type="button" class="btn btn-sm btn-outline-primary ms-2" data-bs-toggle="modal" data-bs-target="#updateStatusModal">
                                                Update
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Assigned To:</th>
                                    <td>
                                        <?php if (!empty($job['assigned_user_name'])): ?>
                                            <?php echo htmlspecialchars($job['assigned_user_name']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Unassigned</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Updated By:</th>
                                    <td><?php echo htmlspecialchars($job['updated_by_name'] ?? 'System'); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Job Actions -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Job Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex gap-2">
                        <?php if ($job['status'] == STATUS_PENDING || $job['status'] == STATUS_QUEUED): ?>
                        <form action="<?php echo BASE_URL; ?>/jobs/start" method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-play-fill"></i> Start Job
                            </button>
                        </form>
                        <?php endif; ?>
                        
                        <?php if ($job['status'] == STATUS_IN_PROGRESS): ?>
                        <form action="<?php echo BASE_URL; ?>/jobs/mark-printed" method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-check-lg"></i> Mark as Printed
                            </button>
                        </form>
                        <?php endif; ?>
                        
                        <?php if (SessionHelper::getUserRole() <= ROLE_MANAGER): ?>
                        <a href="<?php echo BASE_URL; ?>/files/create/<?php echo $job['order_id']; ?>" class="btn btn-info">
                            <i class="bi bi-cloud-upload"></i> Upload Files
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Job Notes -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Job Notes</h5>
                    <?php if (SessionHelper::getUserRole() <= ROLE_MANAGER || (SessionHelper::getUserRole() == ROLE_PRODUCTION && $job['assigned_to'] == SessionHelper::getUserId())): ?>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editNotesModal">
                        <i class="bi bi-pencil"></i> Edit Notes
                    </button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (!empty($job['notes'])): ?>
                        <div class="notes-content">
                            <?php echo nl2br(htmlspecialchars($job['notes'])); ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No notes for this job.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="col-md-4">
            <!-- Order Details Quick View -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Order Details</h5>
                </div>
                <div class="card-body">
                    <a href="<?php echo BASE_URL; ?>/orders/<?php echo $job['order_id']; ?>" class="btn btn-outline-primary mb-3 w-100">
                        <i class="bi bi-eye"></i> View Full Order
                    </a>
                    
                    <div id="fileList">
                        <!-- File list will be loaded via AJAX -->
                        <h6>Files</h6>
                        <p class="text-muted">Loading files...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Update Status Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateStatusModalLabel">Update Job Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="<?php echo BASE_URL; ?>/jobs/update-status" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                        <input type="hidden" name="redirect" value="/jobs/<?php echo $job['id']; ?>">
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Job Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="<?php echo STATUS_PENDING; ?>" <?php echo $job['status'] == STATUS_PENDING ? 'selected' : ''; ?>>Pending</option>
                                <option value="<?php echo STATUS_QUEUED; ?>" <?php echo $job['status'] == STATUS_QUEUED ? 'selected' : ''; ?>>Queued</option>
                                <option value="<?php echo STATUS_IN_PROGRESS; ?>" <?php echo $job['status'] == STATUS_IN_PROGRESS ? 'selected' : ''; ?>>In Progress</option>
                                <option value="<?php echo STATUS_PRINTED; ?>" <?php echo $job['status'] == STATUS_PRINTED ? 'selected' : ''; ?>>Printed</option>
                                <option value="<?php echo STATUS_SHIPPED; ?>" <?php echo $job['status'] == STATUS_SHIPPED ? 'selected' : ''; ?>>Shipped</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Notes Modal -->
    <div class="modal fade" id="editNotesModal" tabindex="-1" aria-labelledby="editNotesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editNotesModalLabel">Edit Job Notes</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="<?php echo BASE_URL; ?>/jobs/update-notes" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                        
                        <div class="mb-3">
                            <textarea class="form-control" name="notes" rows="10"><?php echo htmlspecialchars($job['notes']); ?></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Notes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Load file list via AJAX
    document.addEventListener('DOMContentLoaded', function() {
        fetch('<?php echo BASE_URL; ?>/files/list/<?php echo $job['order_id']; ?>')
            .then(response => response.text())
            .then(data => {
                document.getElementById('fileList').innerHTML = data;
            })
            .catch(error => {
                console.error('Error fetching files:', error);
                document.getElementById('fileList').innerHTML = '<p class="text-danger">Error loading files</p>';
            });
    });
</script>

<?php include APP_PATH . '/views/layouts/footer.php'; ?>