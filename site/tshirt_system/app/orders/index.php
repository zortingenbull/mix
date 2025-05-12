<?php include APP_PATH . '/views/layouts/header.php'; ?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Orders</h1>
        
        <?php if (SessionHelper::getUserRole() <= ROLE_MANAGER): ?>
        <div>
            <a href="<?php echo BASE_URL; ?>/orders/sync" class="btn btn-primary">
                <i class="bi bi-cloud-download"></i> Sync with ShipStation
            </a>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if (isset($flash)): ?>
        <div class="alert alert-<?php echo $flash['type']; ?>" role="alert">
            <?php echo $flash['message']; ?>
        </div>
    <?php endif; ?>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" action="<?php echo BASE_URL; ?>/orders" class="row g-3">
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
                    <input type="text" class="form-control" id="search" name="search" placeholder="Customer name or order ID" value="<?php echo isset($filters['search']) ? htmlspecialchars($filters['search']) : ''; ?>">
                </div>
                
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Orders Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Assigned To</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No orders found. Try adjusting your filters or <a href="<?php echo BASE_URL; ?>/orders/sync">sync with ShipStation</a>.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>/orders/<?php echo $order['id']; ?>">
                                            <?php echo htmlspecialchars($order['order_number']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                    <td>$<?php echo number_format($order['order_total'], 2); ?></td>
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
                                        
                                        $statusClass = $statusClasses[$order['status']] ?? 'bg-secondary';
                                        $statusName = $statusNames[$order['status']] ?? 'Unknown';
                                        ?>
                                        
                                        <span class="badge <?php echo $statusClass; ?>"><?php echo $statusName; ?></span>
                                    </td>
                                    <td>
                                        <?php if (!empty($order['assigned_user_name'])): ?>
                                            <?php echo htmlspecialchars($order['assigned_user_name']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Unassigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButton<?php echo $order['id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                                Actions
                                            </button>
                                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton<?php echo $order['id']; ?>">
                                                <li>
                                                    <a class="dropdown-item" href="<?php echo BASE_URL; ?>/orders/<?php echo $order['id']; ?>">
                                                        <i class="bi bi-eye"></i> View Details
                                                    </a>
                                                </li>
                                                
                                                <?php if (SessionHelper::getUserRole() <= ROLE_MANAGER): ?>
                                                <li>
                                                    <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#assignModal<?php echo $order['id']; ?>">
                                                        <i class="bi bi-person-check"></i> Assign
                                                    </button>
                                                </li>
                                                
                                                <li>
                                                    <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#statusModal<?php echo $order['id']; ?>">
                                                        <i class="bi bi-arrow-repeat"></i> Update Status
                                                    </button>
                                                </li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                        
                                        <!-- Assign Modal -->
                                        <div class="modal fade" id="assignModal<?php echo $order['id']; ?>" tabindex="-1" aria-labelledby="assignModalLabel<?php echo $order['id']; ?>" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="assignModalLabel<?php echo $order['id']; ?>">Assign Order</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form action="<?php echo BASE_URL; ?>/orders/assign" method="post">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                            <input type="hidden" name="redirect" value="/orders">
                                                            
                                                            <div class="mb-3">
                                                                <label for="user_id<?php echo $order['id']; ?>" class="form-label">Assign to User</label>
                                                                <select class="form-select" id="user_id<?php echo $order['id']; ?>" name="user_id">
                                                                    <option value="">Unassigned</option>
                                                                    <?php foreach ($users as $user): ?>
                                                                        <option value="<?php echo $user['id']; ?>" <?php echo $order['assigned_to'] == $user['id'] ? 'selected' : ''; ?>>
                                                                            <?php echo htmlspecialchars($user['name']); ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-primary">Assign</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Status Modal -->
                                        <div class="modal fade" id="statusModal<?php echo $order['id']; ?>" tabindex="-1" aria-labelledby="statusModalLabel<?php echo $order['id']; ?>" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="statusModalLabel<?php echo $order['id']; ?>">Update Status</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form action="<?php echo BASE_URL; ?>/orders/update-status" method="post">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                            <input type="hidden" name="redirect" value="/orders">
                                                            
                                                            <div class="mb-3">
                                                                <label for="status<?php echo $order['id']; ?>" class="form-label">Order Status</label>
                                                                <select class="form-select" id="status<?php echo $order['id']; ?>" name="status">
                                                                    <option value="<?php echo STATUS_PENDING; ?>" <?php echo $order['status'] == STATUS_PENDING ? 'selected' : ''; ?>>Pending</option>
                                                                    <option value="<?php echo STATUS_QUEUED; ?>" <?php echo $order['status'] == STATUS_QUEUED ? 'selected' : ''; ?>>Queued</option>
                                                                    <option value="<?php echo STATUS_IN_PROGRESS; ?>" <?php echo $order['status'] == STATUS_IN_PROGRESS ? 'selected' : ''; ?>>In Progress</option>
                                                                    <option value="<?php echo STATUS_PRINTED; ?>" <?php echo $order['status'] == STATUS_PRINTED ? 'selected' : ''; ?>>Printed</option>
                                                                    <option value="<?php echo STATUS_SHIPPED; ?>" <?php echo $order['status'] == STATUS_SHIPPED ? 'selected' : ''; ?>>Shipped</option>
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