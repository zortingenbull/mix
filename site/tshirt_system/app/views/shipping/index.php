<?php include APP_PATH . '/views/layouts/header.php'; ?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Shipping Console</h1>
    </div>
    
    <?php if (isset($flash)): ?>
        <div class="alert alert-<?php echo $flash['type']; ?>" role="alert">
            <?php echo $flash['message']; ?>
        </div>
    <?php endif; ?>
    
    <?php if (empty($jobs)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> No orders ready for shipping at this time.
        </div>
    <?php else: ?>
        <!-- Shipping Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Shipping Method</th>
                                <th>Address</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($jobs as $job): ?>
                                <?php
                                $address = json_decode($job['shipping_address'], true);
                                ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>/orders/<?php echo $job['order_id']; ?>">
                                            <?php echo htmlspecialchars($job['order_number']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($job['customer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($job['shipping_method']); ?></td>
                                    <td>
                                        <?php if ($address): ?>
                                            <?php echo htmlspecialchars($address['name']); ?><br>
                                            <?php echo htmlspecialchars($address['city']); ?>, <?php echo htmlspecialchars($address['state']); ?> <?php echo htmlspecialchars($address['postalCode']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">No address available</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">Ready to Ship</span>
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" id="dropdownActions<?php echo $job['order_id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                                Actions
                                            </button>
                                            <ul class="dropdown-menu" aria-labelledby="dropdownActions<?php echo $job['order_id']; ?>">
                                                <li>
                                                    <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#markShippedModal<?php echo $job['order_id']; ?>">
                                                        <i class="bi bi-truck"></i> Mark as Shipped
                                                    </button>
                                                </li>
                                                <li>
                                                    <form action="<?php echo BASE_URL; ?>/shipping/generate-label" method="post">
                                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                        <input type="hidden" name="order_id" value="<?php echo $job['order_id']; ?>">
                                                        <button type="submit" class="dropdown-item">
                                                            <i class="bi bi-printer"></i> Print Shipping Label
                                                        </button>
                                                    </form>
                                                </li>
                                                <li>
                                                    <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#manualShippingModal<?php echo $job['order_id']; ?>">
                                                        <i class="bi bi-pencil"></i> Manual Entry
                                                    </button>
                                                </li>
                                            </ul>
                                        </div>
                                        
                                        <!-- Mark Shipped Modal -->
                                        <div class="modal fade" id="markShippedModal<?php echo $job['order_id']; ?>" tabindex="-1" aria-labelledby="markShippedModalLabel<?php echo $job['order_id']; ?>" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="markShippedModalLabel<?php echo $job['order_id']; ?>">Mark Order as Shipped</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form action="<?php echo BASE_URL; ?>/shipping/mark-shipped" method="post">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                            <input type="hidden" name="order_id" value="<?php echo $job['order_id']; ?>">
                                                            
                                                            <div class="mb-3">
                                                                <label for="trackingNumber<?php echo $job['order_id']; ?>" class="form-label">Tracking Number</label>
                                                                <input type="text" class="form-control" id="trackingNumber<?php echo $job['order_id']; ?>" name="tracking_number" required>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label for="carrierCode<?php echo $job['order_id']; ?>" class="form-label">Carrier</label>
                                                                <select class="form-select" id="carrierCode<?php echo $job['order_id']; ?>" name="carrier_code" required>
                                                                    <option value="usps">USPS</option>
                                                                    <option value="ups">UPS</option>
                                                                    <option value="fedex">FedEx</option>
                                                                    <option value="dhl">DHL</option>
                                                                    <option value="other">Other</option>
                                                                </select>
                                                            </div>
                                                            
                                                            <p class="text-info">
                                                                <i class="bi bi-info-circle"></i> This will update the order status in ShipStation and notify the customer.
                                                            </p>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-primary">Mark as Shipped</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Manual Shipping Modal -->
                                        <div class="modal fade" id="manualShippingModal<?php echo $job['order_id']; ?>" tabindex="-1" aria-labelledby="manualShippingModalLabel<?php echo $job['order_id']; ?>" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="manualShippingModalLabel<?php echo $job['order_id']; ?>">Manual Shipping Entry</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form action="<?php echo BASE_URL; ?>/shipping/manual" method="post">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                            <input type="hidden" name="order_id" value="<?php echo $job['order_id']; ?>">
                                                            
                                                            <div class="mb-3">
                                                                <label for="manualTrackingNumber<?php echo $job['order_id']; ?>" class="form-label">Tracking Number</label>
                                                                <input type="text" class="form-control" id="manualTrackingNumber<?php echo $job['order_id']; ?>" name="tracking_number" required>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label for="manualCarrierCode<?php echo $job['order_id']; ?>" class="form-label">Carrier</label>
                                                                <select class="form-select" id="manualCarrierCode<?php echo $job['order_id']; ?>" name="carrier_code" required>
                                                                    <option value="usps">USPS</option>
                                                                    <option value="ups">UPS</option>
                                                                    <option value="fedex">FedEx</option>
                                                                    <option value="dhl">DHL</option>
                                                                    <option value="other">Other</option>
                                                                </select>
                                                            </div>
                                                            
                                                            <p class="text-warning">
                                                                <i class="bi bi-exclamation-triangle"></i> This will only update the local database, not ShipStation. Use this only if you've already handled shipping outside the system.
                                                            </p>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-primary">Save</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include APP_PATH . '/views/layouts/footer.php'; ?>