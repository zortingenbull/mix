<?php include APP_PATH . '/views/layouts/header.php'; ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Create New Job</h2>
                </div>
                <div class="card-body">
                    <?php if (isset($flash)): ?>
                        <div class="alert alert-<?php echo $flash['type']; ?>" role="alert">
                            <?php echo $flash['message']; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="<?php echo BASE_URL; ?>/jobs/store" method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        
                        <div class="mb-3">
                            <label for="order_id" class="form-label">Order</label>
                            <select class="form-select" id="order_id" name="order_id" required>
                                <option value="">Select an order</option>
                                <?php foreach ($orders as $orderItem): ?>
                                    <option value="<?php echo $orderItem['id']; ?>" <?php echo (isset($order) && $order['id'] == $orderItem['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($orderItem['order_number'] . ' - ' . $orderItem['customer_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Initial Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="<?php echo STATUS_PENDING; ?>">Pending</option>
                                <option value="<?php echo STATUS_QUEUED; ?>" selected>Queued</option>
                                <option value="<?php echo STATUS_IN_PROGRESS; ?>">In Progress</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="5" placeholder="Add any special instructions or notes about this job..."></textarea>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="<?php echo BASE_URL; ?>/jobs" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Create Job</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include APP_PATH . '/views/layouts/footer.php'; ?>