<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Login | Ani-Mart</title>

    <link rel="icon" type="image/png" sizes="32x32" href="/ani-mart/public/assets/img/favicon-32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/ani-mart/public/assets/img/apple-touch-icon.png">

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="/ani-mart/public/assets/css/app.css?v=<?= filemtime(__DIR__ . '/../../../public/assets/css/app.css') ?>">
    <link rel="stylesheet" href="/ani-mart/public/assets/css/auth/auth.css?v=<?= filemtime(__DIR__ . '/../../../public/assets/css/auth/auth.css') ?>">

</head>

<body>

    <div class="auth-page">

        <div class="auth-card">

            <img src="/ani-mart/public/assets/img/ani-mart-logo.png?v=<?= filemtime(__DIR__ . '/../../../public/assets/img/ani-mart-logo.png') ?>" class="auth-brand-logo" alt="Ani-Mart">

            <div class="text-center mb-4">

                <h1 class="auth-title">

                    Welcome Back

                </h1>

                <p class="auth-subtitle mt-2">

                    Fresh produce directly from local farmers.

                </p>

            </div>

<?php
$errorMessage = \App\Core\Session::flash('error');
?>

<?php if ($errorMessage): ?>
    <div class="alert auth-alert alert-danger alert-dismissible fade show mb-4">
        <?= htmlspecialchars($errorMessage) ?>

        <button type="button" 
                class="btn-close" 
                data-bs-dismiss="alert">
        </button>
    </div>
<?php endif; ?>

            <?php if (!empty($_SESSION['success'])): ?>

                <div class="alert auth-alert alert-success alert-dismissible fade show mb-4">

                    <i class="bi bi-check-circle-fill auth-alert__icon" aria-hidden="true"></i>

                    <div class="auth-alert__message">
                        <?= $_SESSION['success'] ?>
                    </div>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert">
                    </button>

                </div>

                <?php unset($_SESSION['success']); ?>

            <?php endif; ?>

            <?php if (!empty($_SESSION['error'])): ?>

<div class="alert auth-alert alert-danger alert-dismissible fade show mb-4">
    <?= htmlspecialchars($_SESSION['error']) ?>
</div>

<?php unset($_SESSION['error']); ?>

<?php endif; ?>

            <?php $errors = errors(); ?>

            <form
                method="POST"
                action="/ani-mart/public/login"
                class="auth-form">

                <input
                    type="hidden"
                    name="_token"
                    value="<?= csrf_token(); ?>">

                <div class="auth-form-field mb-3">

                    <label class="form-label auth-label">

                        Email Address

                    </label>

                    <input
                        type="email"
                        name="email"
                        class="form-control auth-input <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                        <?= isset($errors['email']) ? 'aria-invalid="true"' : '' ?>
                        placeholder="Enter email address"
                        value="<?= htmlspecialchars(old('email')) ?>"
                        required>

                    <?php if (isset($errors['email'])): ?>
                        <div class="invalid-feedback">
                            <?= htmlspecialchars($errors['email']) ?>
                        </div>
                    <?php endif; ?>

                </div>

                <div class="auth-form-field mb-2">

                    <label class="form-label auth-label">

                        Password

                    </label>

                    <div class="input-group">
                        <input
                            type="password"
                            name="password"
                            id="password"
                            class="form-control auth-input <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                            <?= isset($errors['password']) ? 'aria-invalid="true"' : '' ?>
                            placeholder="Enter password"
                            required>
                        <button
                            type="button"
                            class="btn btn-outline-secondary"
                            id="togglePassword"
                            aria-label="Show password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>

                    <?php if (isset($errors['password'])): ?>
                        <div class="invalid-feedback d-block">
                            <?= htmlspecialchars($errors['password']) ?>
                        </div>
                    <?php endif; ?>

                </div>

                <div class="text-end mb-4">
                    <a href="/ani-mart/public/password/forgot" class="auth-link">Forgot password?</a>
                </div>

                <button
                    type="submit"
                    class="btn auth-submit w-100">

                    <i class="bi bi-box-arrow-in-right me-2"></i>

                    Login to Ani-Mart

                </button>

            </form>

            <div class="text-center mt-4">

                <span class="text-white-50">

                    Don't have an account?

                </span>

                <a
                    href="/ani-mart/public/register"
                    class="auth-link">

                    Create Account

                </a>

            </div>

        </div>

    </div>

    <script>
        document.getElementById('togglePassword')?.addEventListener('click', function () {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');

            if (!passwordInput) {
                return;
            }

            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            icon.classList.toggle('bi-eye');
            icon.classList.toggle('bi-eye-slash');
            this.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
        });

        const loginForm = document.querySelector('.auth-form');

        loginForm?.addEventListener('invalid', function (event) {
            const field = event.target;

            if (field.classList?.contains('auth-input')) {
                field.classList.add('is-invalid');
                field.setAttribute('aria-invalid', 'true');
            }
        }, true);

        loginForm?.addEventListener('input', function (event) {
            const field = event.target;

            if (field.classList?.contains('auth-input') && field.checkValidity()) {
                field.classList.remove('is-invalid');
                field.removeAttribute('aria-invalid');
            }
        });
    </script>

</body>

</html>
