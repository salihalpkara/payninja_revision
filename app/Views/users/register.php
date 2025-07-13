<?php require APPROOT . '/app/views/layouts/header.php'; ?>

<div class="container vh-100 d-flex justify-content-center align-items-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm p-4">
            <h1 class="text-center"><i class="bi bi-receipt"></i></h1>
            <h3 class="text-center mb-3 fw-bold">Welcome to PayNinja</h3>
            <p class="text-center text-muted mb-4">Register and start splitting!</p>

            <form action="<?php echo URLROOT; ?>/users/register" method="post">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="fname" class="form-label">First Name</label>
                        <input type="text" name="fname" class="form-control <?php echo (!empty($data['fname_err'])) ? 'is-invalid' : ''; ?>" value="<?php echo $data['fname']; ?>" required>
                        <span class="invalid-feedback"><?php echo $data['fname_err']; ?></span>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="lname" class="form-label">Last Name</label>
                        <input type="text" name="lname" class="form-control <?php echo (!empty($data['lname_err'])) ? 'is-invalid' : ''; ?>" value="<?php echo $data['lname']; ?>" required>
                        <span class="invalid-feedback"><?php echo $data['lname_err']; ?></span>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" name="username" class="form-control <?php echo (!empty($data['username_err'])) ? 'is-invalid' : ''; ?>" value="<?php echo $data['username']; ?>" required>
                    <span class="invalid-feedback"><?php echo $data['username_err']; ?></span>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" name="email" class="form-control <?php echo (!empty($data['email_err'])) ? 'is-invalid' : ''; ?>" value="<?php echo $data['email']; ?>" required>
                    <span class="invalid-feedback"><?php echo $data['email_err']; ?></span>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" name="password" class="form-control <?php echo (!empty($data['password_err'])) ? 'is-invalid' : ''; ?>" value="<?php echo $data['password']; ?>" required>
                    <span class="invalid-feedback"><?php echo $data['password_err']; ?></span>
                </div>

                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control <?php echo (!empty($data['confirm_password_err'])) ? 'is-invalid' : ''; ?>" value="<?php echo $data['confirm_password']; ?>" required>
                    <span class="invalid-feedback"><?php echo $data['confirm_password_err']; ?></span>
                </div>

                <button type="submit" class="btn btn-primary w-100 mb-3">Register</button>

                <p class="text-center">Already have an account?</p>
                <div class="d-grid gap-2 col-6 mx-auto">
                    <a href="<?php echo URLROOT; ?>/users/login" class="btn btn-secondary m-auto">Login</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require APPROOT . '/app/views/layouts/footer.php'; ?>