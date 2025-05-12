<?php include APP_PATH . '/views/layouts/header.php'; ?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Print Jobs</h1>
        
        <?php if (SessionHelper::getUserRole() <= ROLE_MANAGER): ?>
        <div>
            <a href="<?php echo BASE_URL; ?>/jobs/create" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Create New Job
            </a>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if (isset($flash)): ?>
        <div class="alert alert-<?php echo $flash['type']; ?>" role="alert">
            <?php echo $flash['message']; ?>
        </div>
    <?php endif; ?>
    
    <?php if (SessionHelper::getUserRole() != ROLE_PRODUCTION): ?>
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" action="<?php echo BASE_URL; ?>/jobs" class="row g-3">
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Statuses</option>
                        <option value="<?php echo STATUS_PENDING; ?>" <?php echo isset($filters['status']) && $filters['status'] == STATUS_PENDING ? 'selected' : ''; ?>>Pending</option>
                        <option value="<?php echo STATUS_QUEUED; ?>" <?php echo isset($filters['status']) && $filters['status'] == STATUS_QUEUED ? 'selected' : ''; ?>>Queued</option>
                        <option value="<?php echo STATUS_IN_PROGRESS; ?>" <?php echo isset($filters['status']) && $filters['status'] == STATUS_IN_PROGRESS ? 'selected' : ''; ?>>In Progress</option>
                        <option value="<?php echo STATUS_PRINTED; ?>" <?php echo isset($filters['status']) && $filters['status'] == STATUS_PRINTED ? 'selected' : ''; ?>>Printed</option>
                        <option value="<?php echo STATUS_SHIPPED; ?>" <?php echo isset($filters['status']) && $filters['status'] == STATUS_SHIPPED ? 'selected' : ''; ?>>Shipped</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="assigned_to" class="form-label">Assigned To</label>
                    <select class="form-select" id="assigned_to" name="assigned_to">
                        <option value="">All Users</option>
                        <option value="0" <?php echo isset($filters['assigned_to']) && $filters['assigned_to'] === 0 ? 'selected' : ''; ?>>Unassigned</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['id']; ?>" <?php echo isset($filters['assigned_to']) && $filters['assigned_to'] == $user['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" placeholder="Customer name or order number" value="<?php echo isset($filters['search']) ? htmlspecialchars($filters['search']) : ''; ?>">
                </div>
                
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>
    <?php else: ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> Showing jobs assigned to you. Complete them in order, starting from the top.
    </div>
    <?php endif; ?>
    
    <!-- Jobs Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Job ID</th>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Status</th>
                            <th>Last Updated</th>
                            <th>Updated By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($jobs)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No jobs found. Try adjusting your filters or create a new job.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($jobs as $job): ?>
                                <tr>
                                    <td><?php echo $job['id']; ?></td>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>/orders/<?php echo $job['order_id']; ?>">
                                            <?php echo htmlspecialchars($job['order_number']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($job['customer_name']); ?></td>
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
                                    </td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($job['updated_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($job['updated_by_name'] ?? 'System'); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="<?php echo BASE_URL; ?>/jobs/<?php echo $job['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                            
                                            <?php if (SessionHelper::getUserRole() == ROLE_PRODUCTION && $job['status'] == STATUS_QUEUED): ?>
                                            <form action="<?php echo BASE_URL; ?>/jobs/start" method="post" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-warning">
                                                    <i class="bi bi-play-fill"></i> Start
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                            
                                            <?php if (SessionHelper::getUserRole() == ROLE_PRODUCTION && $job['status'] == STATUS_IN_PROGRESS): ?>
                                            <form action="<?php echo BASE_URL; ?>/jobs/mark-printed" method="post" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-success">
                                                    <i class="bi bi-check-lg"></i> Mark Printed
                                                </button>
                                            </form>
                                            <?php endif; ?>
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