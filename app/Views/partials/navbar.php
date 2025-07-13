<nav class="navbar navbar-expand-lg bg-body-tertiary fixed-top">
    <div class="container">
        <a class="navbar-brand" href="<?php echo URLROOT; ?>"><i class="bi bi-receipt me-2"></i><?php echo SITENAME; ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php if(isLoggedIn()) : ?>
                <li class="nav-item">
                    <a class="nav-link" id="navItemAccounts" href="<?php echo URLROOT; ?>/accounts">Accounts</a>
                </li>
                <?php endif; ?>
                <!-- Add other general navigation links here if needed -->
            </ul>
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <?php if(isLoggedIn()) : ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle fs-3 me-2"></i>
                        <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?php echo URLROOT; ?>/users/profile"><i class="bi bi-person-lines-fill me-2"></i>View profile</a></li>
                        <li><a class="dropdown-item" href="<?php echo URLROOT; ?>/users/edit"><i class="bi bi-pencil-square me-2"></i>Edit profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?php echo URLROOT; ?>/users/logout"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </li>
                <?php else : ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo URLROOT; ?>/users/register">Register</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo URLROOT; ?>/users/login">Login</a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<!-- div with height of 56 when display is small, 76 when display is large -->
<div class="d-block d-lg-none" style="height: 56px;"></div>
<div class="d-none d-lg-block" style="height: 76px;"></div>