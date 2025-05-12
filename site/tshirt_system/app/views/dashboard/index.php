<?php include APP_PATH . '/views/layouts/header.php'; ?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Dashboard</h1>
        
        <?php if (SessionHelper::getUserRole() <= ROLE_MANAGER): ?>
        <div>
            <a href="<?php echo BASE_URL; ?>/orders/sync" class="btn btn-outline-primary">
                <i class="bi bi-cloud-download"></i> Sync Orders
            </a>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if (isset($flash)): ?>
        <div class="alert alert-<?php echo $flash['type']; ?>" role="alert">
            <?php echo $flash['message']; ?>
        </div>
    <?php endif; ?>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-left-primary h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Pending Orders</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $orderStats[STATUS_PENDING]; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-clock fs-2 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-left-info h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                In Progress</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $orderStats[STATUS_IN_PROGRESS]; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-tools fs-2 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-left-warning h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Ready to Ship</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $orderStats[STATUS_PRINTED]; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-box-seam fs-2 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-left-success h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Shipped Orders</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $orderStats[STATUS_SHIPPED]; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-truck fs-2 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <?php if (SessionHelper::getUserRole() == ROLE_PRODUCTION && !empty($assignedJobs)): ?>
        <!-- Assigned Jobs -->
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">My Assigned Jobs</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Job ID</th>
                                    <th>Order</th>
                                    <th>Customer</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($assignedJobs as $job): ?>
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
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>/jobs/<?php echo $job['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                            
                                            <?php if ($job['status'] == STATUS_QUEUED): ?>
                                                <form action="<?php echo BASE_URL; ?>/jobs/start" method="post" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?php echo SecurityHelper::generateCsrfToken(); ?>">
                                                    <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-warning">
                                                        <i class="bi bi-play-fill"></i> Start
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <?php if ($job['status'] == STATUS_IN_PROGRESS): ?>
                                                <form action="<?php echo BASE_URL; ?>/jobs/mark-printed" method="post" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?php echo SecurityHelper::generateCsrfToken(); ?>">
                                                    <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-success">
                                                        <i class="bi bi-check-lg"></i> Mark Printed
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <a href="<?php echo BASE_URL; ?>/jobs" class="btn btn-outline-primary mt-3">View All Jobs</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (SessionHelper::getUserRole() == ROLE_SHIPPING && !empty($readyToShip)): ?>
        <!-- Ready to Ship -->
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Orders Ready to Ship</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Customer</th>
                                    <th>Shipping Method</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($readyToShip as $job): ?>
                                    <tr>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>/orders/<?php echo $job['order_id']; ?>">
                                                <?php echo htmlspecialchars($job['order_number']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($job['customer_name']); ?></td>
                                        <td><?php echo htmlspecialchars($job['shipping_method']); ?></td>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>/shipping" class="btn btn-sm btn-primary">
                                                <i class="bi bi-truck"></i> Ship
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <a href="<?php echo BASE_URL; ?>/shipping" class="btn btn-outline-primary mt-3">Go to Shipping Console</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (SessionHelper::getUserRole() <= ROLE_MANAGER): ?>
        <!-- Recent Orders -->
        <div class="col-md-<?php echo !empty($readyToShip) ? '6' : '12'; ?>">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Orders</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Customer</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentOrders)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center">No orders found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recentOrders as $order): ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo BASE_URL; ?>/orders/<?php echo $order['id']; ?>">
                                                    <?php echo htmlspecialchars($order['order_number']); ?>
                                                </a>
                                            </td>
                                            <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
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
                                            <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <a href="<?php echo BASE_URL; ?>/orders" class="btn btn-outline-primary mt-3">View All Orders</a>
                </div>
            </div>
        </div>
        
        <!-- Ready to Ship (for managers/admins) -->
        <?php if (!empty($readyToShip)): ?>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Orders Ready to Ship</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Customer</th>
                                    <th>Shipping Method</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($readyToShip as $job): ?>
                                    <tr>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>/orders/<?php echo $job['order_id']; ?>">
                                                <?php echo htmlspecialchars($job['order_number']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($job['customer_name']); ?></td>
                                        <td><?php echo htmlspecialchars($job['shipping_method']); ?></td>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>/shipping" class="btn btn-sm btn-primary">
                                                <i class="bi bi-truck"></i> Ship
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <a href="<?php echo BASE_URL; ?>/shipping" class="btn btn-outline-primary mt-3">Go to Shipping Console</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php include APP_PATH . '/views/layouts/footer.php'; ?>