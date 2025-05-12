<?php include APP_PATH . '/views/layouts/header.php'; ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Edit User</h2>
                </div>
                <div class="card-body">
                    <?php if (isset($flash)): ?>
                        <div class="alert alert-<?php echo $flash['type']; ?>" role="alert">
                            <?php echo $flash['message']; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="<?php echo BASE_URL; ?>/users/update" method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" minlength="8">
                            <div class="form-text">Leave blank to keep current password. New password must be at least 8 characters long.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="<?php echo ROLE_ADMIN; ?>" <?php echo $user['role'] == ROLE_ADMIN ? 'selected' : ''; ?>>Admin</option>
                                <option value="<?php echo ROLE_MANAGER; ?>" <?php echo $user['role'] == ROLE_MANAGER ? 'selected' : ''; ?>>Manager</option>
                                <option value="<?php echo ROLE_PRODUCTION; ?>" <?php echo $user['role'] == ROLE_PRODUCTION ? 'selected' : ''; ?>>Production</option>
                                <option value="<?php echo ROLE_SHIPPING; ?>" <?php echo $user['role'] == ROLE_SHIPPING ? 'selected' : ''; ?>>Shipping</option>
                                <option value="<?php echo ROLE_VIEWER; ?>" <?php echo $user['role'] == ROLE_VIEWER ? 'selected' : ''; ?>>Viewer</option>
                            </select>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="<?php echo BASE_URL; ?>/users" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update User</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include APP_PATH . '/views/layouts/footer.php'; ?>