<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Register | Ani-Mart</title>

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

        <div class="auth-card auth-card--register">

            <img src="/ani-mart/public/assets/img/ani-mart-logo.png?v=<?= filemtime(__DIR__ . '/../../../public/assets/img/ani-mart-logo.png') ?>" class="auth-brand-logo" alt="Ani-Mart">

            <div class="text-center mb-4">

                <h1 class="auth-title">

                    Join Ani-Mart

                </h1>

                <p class="auth-subtitle mt-2">

                    Create your account and discover fresh
                    farm products directly from local farmers.

                </p>

            </div>

            <?php $errors = errors(); ?>

            <?php if ($msg = flash('success')): ?>

                <div class="alert auth-alert alert-success">

                    <?= htmlspecialchars($msg) ?>

                </div>

            <?php endif; ?>

            <form
                method="POST"
                action="/ani-mart/public/register"
                class="auth-form">

                <input
                    type="hidden"
                    name="_token"
                    value="<?= csrf_token(); ?>">

                <div class="auth-honeypot" aria-hidden="true">

                    <label>
                        Company Website
                    </label>

                    <input
                        type="text"
                        name="company_website"
                        tabindex="-1"
                        autocomplete="off">

                </div>

                <div class="auth-form-field mb-3">

                    <label class="form-label auth-label">

                        First Name

                    </label>

                    <input
                        type="text"
                        name="first_name"
                        class="form-control auth-input <?= isset($errors['first_name']) ? 'is-invalid' : '' ?>"
                        <?= isset($errors['first_name']) ? 'aria-invalid="true"' : '' ?>
                        placeholder="Enter first name"
                        value="<?= htmlspecialchars(old('first_name')) ?>"
                        required>
                    <?php if (isset($errors['first_name'])): ?>
                        <div class="invalid-feedback">
                            <?= htmlspecialchars($errors['first_name']) ?>
                        </div>
                    <?php endif; ?>

                </div>

                <div class="auth-form-field mb-3">

                    <label class="form-label auth-label">

                        Last Name

                    </label>

                    <input
                        type="text"
                        name="last_name"
                        class="form-control auth-input <?= isset($errors['last_name']) ? 'is-invalid' : '' ?>"
                        <?= isset($errors['last_name']) ? 'aria-invalid="true"' : '' ?>
                        placeholder="Enter last name"
                        value="<?= htmlspecialchars(old('last_name')) ?>"
                        required>
                    <?php if (isset($errors['last_name'])): ?>
                        <div class="invalid-feedback">
                            <?= htmlspecialchars($errors['last_name']) ?>
                        </div>
                    <?php endif; ?>

                </div>

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

                <div class="auth-form-field mb-4">

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
                            placeholder="At least 8 characters"
                            autocomplete="new-password"
                            required>
                        <button
                            type="button"
                            class="btn btn-outline-secondary"
                            id="togglePassword"
                            aria-label="Show password"
                            aria-pressed="false">
                            <i class="bi bi-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                    <?php if (isset($errors['password'])): ?>
                        <div class="invalid-feedback d-block">
                            <?= htmlspecialchars($errors['password']) ?>
                        </div>
                    <?php endif; ?>

                </div>

                <button
                    type="submit"
                    class="btn auth-submit w-100">

                    <i class="bi bi-person-plus-fill me-2"></i>

                    Create Account

                </button>

            </form>

            <div class="text-center mt-4">

                <span class="text-white-50">

                    Already have an account?

                </span>

                <a
                    href="/ani-mart/public/login"
                    class="auth-link">

                    Sign In

                </a>

            </div>

        </div>

    </div>

</body>

<script>
    document.getElementById('togglePassword')?.addEventListener('click', function () {
        const passwordInput = document.getElementById('password');
        const icon = this.querySelector('i');

        if (!passwordInput || !icon) {
            return;
        }

        const isHidden = passwordInput.type === 'password';
        passwordInput.type = isHidden ? 'text' : 'password';
        icon.classList.toggle('bi-eye', !isHidden);
        icon.classList.toggle('bi-eye-slash', isHidden);
        this.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
        this.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
    });

    const registerForm = document.querySelector('.auth-form');

    registerForm?.addEventListener('invalid', function (event) {
        const field = event.target;

        if (field.classList?.contains('auth-input')) {
            field.classList.add('is-invalid');
            field.setAttribute('aria-invalid', 'true');
        }
    }, true);

    registerForm?.addEventListener('input', function (event) {
        const field = event.target;

        if (field.classList?.contains('auth-input') && field.checkValidity()) {
            field.classList.remove('is-invalid');
            field.removeAttribute('aria-invalid');
        }
    });
</script>

</html>
