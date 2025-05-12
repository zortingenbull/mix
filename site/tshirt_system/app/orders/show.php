<?php include APP_PATH . '/views/layouts/header.php'; ?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Order Details: <?php echo htmlspecialchars($order['order_number']); ?></h1>
        <a href="<?php echo BASE_URL; ?>/orders" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Orders
        </a>
    </div>
    
    <?php if (isset($flash)): ?>
        <div class="alert alert-<?php echo $flash['type']; ?>" role="alert">
            <?php echo $flash['message']; ?>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Order Information -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Order Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <th width="150">Order Number:</th>
                                    <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                </tr>
                                <tr>
                                    <th>Customer:</th>
                                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                </tr>
                                <tr>
                                    <th>Email:</th>
                                    <td><?php echo htmlspecialchars($order['customer_email']); ?></td>
                                </tr>
                                <tr>
                                    <th>Total:</th>
                                    <td>$<?php echo number_format($order['order_total'], 2); ?></td>
                                </tr>
                                <tr>
                                    <th>Created:</th>
                                    <td><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></td>
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
                                        
                                        $statusClass = $statusClasses[$order['status']] ?? 'bg-secondary';
                                        $statusName = $statusNames[$order['status']] ?? 'Unknown';
                                        ?>
                                        
                                        <span class="badge <?php echo $statusClass; ?>"><?php echo $statusName; ?></span>
                                        
                                        <?php if (SessionHelper::getUserRole() <= ROLE_MANAGER): ?>
                                            <button type="button" class="btn btn-sm btn-outline-primary ms-2" data-bs-toggle="modal" data-bs-target="#updateStatusModal">
                                                Update
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Assigned To:</th>
                                    <td>
                                        <?php if (!empty($order['assigned_user_name'])): ?>
                                            <?php echo htmlspecialchars($order['assigned_user_name']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Unassigned</span>
                                        <?php endif; ?>
                                        
                                        <?php if (SessionHelper::getUserRole() <= ROLE_MANAGER): ?>
                                            <button type="button" class="btn btn-sm btn-outline-primary ms-2" data-bs-toggle="modal" data-bs-target="#assignModal">
                                                Assign
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Shipping Method:</th>
                                    <td><?php echo htmlspecialchars($order['shipping_method']); ?></td>
                                </tr>
                                <tr>
                                    <th>Tracking Number:</th>
                                    <td>
                                        <?php if (!empty($order['tracking_number'])): ?>
                                            <?php echo htmlspecialchars($order['tracking_number']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Not shipped yet</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>ShipStation ID:</th>
                                    <td><?php echo htmlspecialchars($order['shipstation_order_id']); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Shipping Address -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Shipping Address</h5>
                </div>
                <div class="card-body">
                    <?php 
                    $address = json_decode($order['shipping_address'], true);
                    if ($address):
                    ?>
                        <address>
                            <?php echo htmlspecialchars($address['name']); ?><br>
                            <?php echo htmlspecialchars($address['street1']); ?><br>
                            <?php if (!empty($address['street2'])): ?>
                                <?php echo htmlspecialchars($address['street2']); ?><br>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($address['city']); ?>, <?php echo htmlspecialchars($address['state']); ?> <?php echo htmlspecialchars($address['postalCode']); ?><br>
                            <?php echo htmlspecialchars($address['country']); ?>
                        </address>
                    <?php else: ?>
                        <p class="text-muted">No shipping address available</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Order Details -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Order Items</h5>
                </div>
                <div class="card-body">
                    <?php 
                    $orderDetails = json_decode($order['order_details'], true);
                    $items = $orderDetails['items'] ?? [];
                    
                    if (!empty($items)):
                    ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>SKU</th>
                                        <th>Quantity</th>
                                        <th>Unit Price</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                                            <td><?php echo htmlspecialchars($item['sku'] ?? 'N/A'); ?></td>
                                            <td><?php echo (int) $item['quantity']; ?></td>
                                            <td>$<?php echo number_format($item['unitPrice'], 2); ?></td>
                                            <td>$<?php echo number_format($item['unitPrice'] * $item['quantity'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No item details available</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="col-md-4">
            <!-- Production Jobs -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Production Jobs</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($jobs)): ?>
                        <p class="text-muted">No production jobs created yet.</p>
                        
                        <?php if (SessionHelper::getUserRole() <= ROLE_MANAGER): ?>
                            <a href="<?php echo BASE_URL; ?>/jobs/create/<?php echo $order['id']; ?>" class="btn btn-primary">
                                <i class="bi bi-plus-lg"></i> Create Job
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($jobs as $job): ?>
                                <a href="<?php echo BASE_URL; ?>/jobs/<?php echo $job['id']; ?>" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">Job #<?php echo $job['id']; ?></h6>
                                        <small>
                                            <?php
                                            $jobStatusClasses = [
                                                STATUS_PENDING => 'bg-secondary',
                                                STATUS_QUEUED => 'bg-info text-dark',
                                                STATUS_IN_PROGRESS => 'bg-warning text-dark',
                                                STATUS_PRINTED => 'bg-primary',
                                                STATUS_SHIPPED => 'bg-success'
                                            ];
                                            
                                            $jobStatusNames = [
                                                STATUS_PENDING => 'Pending',
                                                STATUS_QUEUED => 'Queued',
                                                STATUS_IN_PROGRESS => 'In Progress',
                                                STATUS_PRINTED => 'Printed',
                                                STATUS_SHIPPED => 'Shipped'
                                            ];
                                            
                                            $jobStatusClass = $jobStatusClasses[$job['status']] ?? 'bg-secondary';
                                            $jobStatusName = $jobStatusNames[$job['status']] ?? 'Unknown';
                                            ?>
                                            
                                            <span class="badge <?php echo $jobStatusClass; ?>"><?php echo $jobStatusName; ?></span>
                                        </small>
                                    </div>
                                    <p class="mb-1">
                                        <?php if (!empty($job['notes'])): ?>
                                            <?php echo nl2br(htmlspecialchars(substr($job['notes'], 0, 100))); ?>
                                            <?php echo (strlen($job['notes']) > 100) ? '...' : ''; ?>
                                        <?php else: ?>
                                            <span class="text-muted">No notes</span>
                                        <?php endif; ?>
                                    </p>
                                    <small>Created: <?php echo date('M j, Y', strtotime($job['created_at'])); ?></small>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Order Notes -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Notes</h5>
                </div>
                <div class="card-body">
                    <form action="<?php echo BASE_URL; ?>/orders/notes" method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                        
                        <div class="mb-3">
                            <textarea class="form-control" name="notes" rows="4" placeholder="Add notes about this order..."><?php echo !empty($jobs[0]['notes']) ? htmlspecialchars($jobs[0]['notes']) : ''; ?></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Save Notes</button>
                    </form>
                </div>
            </div>
            
            <!-- Files -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Files</h5>
                </div>
                <div class="card-body">
                    <div id="fileList">
                        <!-- File list will be loaded via AJAX or included here -->
                        <p class="text-muted">No files uploaded yet.</p>
                    </div>
                    
                    <div class="mt-3">
                        <form action="<?php echo BASE_URL; ?>/files/upload" method="post" enctype="multipart/form-data" class="dropzone" id="fileUpload">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            
                            <div class="fallback">
                                <input name="file" type="file" multiple />
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Assign Modal -->
    <div class="modal fade" id="assignModal" tabindex="-1" aria-labelledby="assignModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="assignModalLabel">Assign Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="<?php echo BASE_URL; ?>/orders/assign" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                        <input type="hidden" name="redirect" value="/orders/<?php echo $order['id']; ?>">
                        
                        <div class="mb-3">
                            <label for="user_id" class="form-label">Assign to User</label>
                            <select class="form-select" id="user_id" name="user_id">
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
    
    <!-- Update Status Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateStatusModalLabel">Update Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="<?php echo BASE_URL; ?>/orders/update-status" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                        <input type="hidden" name="redirect" value="/orders/<?php echo $order['id']; ?>">
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Order Status</label>
                            <select class="form-select" id="status" name="status">
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
</div>

<?php include APP_PATH . '/views/layouts/footer.php'; ?>