<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?php echo BASE_URL; ?>/dashboard">T-Shirt Printing System</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNavDropdown">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>/dashboard">Dashboard</a>
                </li>
                
                <?php if (SessionHelper::getUserRole() <= ROLE_MANAGER): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownOrders" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Orders
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="navbarDropdownOrders">
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/orders">All Orders</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/orders/sync">Sync with ShipStation</a></li>
                    </ul>
                </li>
                <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>/orders">Orders</a>
                </li>
                <?php endif; ?>
                
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>/jobs">Print Jobs</a>
                </li>
                
                <?php if (SessionHelper::getUserRole() == ROLE_SHIPPING || SessionHelper::getUserRole() <= ROLE_MANAGER): ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>/shipping">Shipping</a>
                </li>
                <?php endif; ?>
                
                <?php if (SessionHelper::getUserRole() <= ROLE_MANAGER): ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>/files">Files</a>
                </li>
                <?php endif; ?>
                
                <?php if (SessionHelper::getUserRole() == ROLE_ADMIN): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownAdmin" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Admin
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="navbarDropdownAdmin">
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/users">User Management</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/logs">System Logs</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/settings">System Settings</a></li>
                    </ul>
                </li>
                <?php endif; ?>
            </ul>
            
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownUser" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle"></i> <?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'User'; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownUser">
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/profile">My Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/logout">Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>