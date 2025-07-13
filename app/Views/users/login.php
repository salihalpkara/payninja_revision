<?php require APPROOT . '/app/views/layouts/header.php'; ?>

<div class="container vh-100 d-flex justify-content-center align-items-center">
    <div class="login-wrapper">
        <div class="card shadow p-4 login-card">
            <h1 class="text-center"><i class="bi bi-receipt"></i></h1>

            <h3 class="text-center mb-3 fw-bold">Welcome to PayNinja</h3>
            <p class="text-center text-muted mb-3">Login and split some more</p>

            <?php flash('register_success'); ?>
            <?php if (!empty($data['username_err']) || !empty($data['password_err'])): ?>
                <div class="alert alert-danger">
                    <?php echo !empty($data['username_err']) ? $data['username_err'] : ''; ?>
                    <?php echo !empty($data['password_err']) ? $data['password_err'] : ''; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="<?php echo URLROOT; ?>/users/login" id="loginForm">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control <?php echo (!empty($data['username_err'])) ? 'is-invalid' : ''; ?>" name="username" id="username" required autofocus
                        value="<?php echo htmlspecialchars($data['username']); ?>">
                    <span class="invalid-feedback"><?php echo $data['username_err']; ?></span>
                </div>
                <label for="password" class="form-label">Password</label>
                <div class="input-group mb-3">
                    <input id="password" type="password" name="password" class="form-control <?php echo (!empty($data['password_err'])) ? 'is-invalid' : ''; ?>" required>
                    <button class="btn btn-outline-secondary" type="button" id="passwordToggleButton"><i class="bi bi-eye-slash" id="passwordToggleIcon"></i></button>
                </div>
                <span class="invalid-feedback"><?php echo $data['password_err']; ?></span>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" name="remember" id="remember"
                        <?php echo (isset($data['remember']) && $data['remember']) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="remember">Remember Me</label>
                </div>

                <button type="submit" class="btn btn-primary w-100 mb-3" id="loginBtn">Login</button>
                <p class="text-center mb-3">Don't have an account?</p>
                <div class="d-grid gap-2 col-6 mx-auto">
                    <a href="<?php echo URLROOT; ?>/users/register" class="btn btn-secondary m-auto">Register Here!</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const usernameInput = document.getElementById('username');
            const passwordInput = document.getElementById('password');
            const form = document.getElementById('loginForm');

            usernameInput.focus();

            usernameInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    passwordInput.focus();
                }
            });

            passwordInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    form.submit();
                }
            });

            const passwordToggleIcon = document.querySelector('#passwordToggleIcon');
            const passwordToggleButton = document.querySelector("#passwordToggleButton");
            const password = document.querySelector('#password');
            passwordToggleButton.addEventListener('click', () => {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                if (passwordToggleIcon.classList.contains('bi-eye-slash')) {
                    passwordToggleIcon.classList.replace('bi-eye-slash', 'bi-eye');
                } else {
                    passwordToggleIcon.classList.replace('bi-eye', 'bi-eye-slash');
                }
            });
        });
    </script>

    <?php require APPROOT . '/app/views/layouts/footer.php'; ?>