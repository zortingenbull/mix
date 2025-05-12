<?php include APP_PATH . '/views/layouts/header.php'; ?>

<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-lg-5">
            <div class="card shadow-lg border-0 rounded-lg">
                <div class="card-header">
                    <h3 class="text-center font-weight-light my-4">Reset Password</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($flash)): ?>
                        <div class="alert alert-<?php echo $flash['type']; ?>" role="alert">
                            <?php echo $flash['message']; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="<?php echo BASE_URL; ?>/reset-password">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="token" value="<?php echo $token; ?>">
                        
                        <div class="form-floating mb-3">
                            <input class="form-control" id="password" name="password" type="password" placeholder="New Password" required>
                            <label for="password">New Password</label>
                        </div>
                        
                        <div class="form-floating mb-3">
                            <input class="form-control" id="password_confirm" name="password_confirm" type="password" placeholder="Confirm Password" required>
                            <label for="password_confirm">Confirm Password</label>
                        </div>
                        
                        <div class="d-flex align-items-center justify-content-between mt-4 mb-0">
                            <a class="small" href="<?php echo BASE_URL; ?>/login">Return to login</a>
                            <button type="submit" class="btn btn-primary">Reset Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include APP_PATH . '/views/layouts/footer.php'; ?>