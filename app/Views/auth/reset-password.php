<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choose New Password | Ani-Mart</title>
    <link rel="icon" type="image/png" sizes="32x32" href="/ani-mart/public/assets/img/favicon-32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/ani-mart/public/assets/img/apple-touch-icon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/ani-mart/public/assets/css/app.css?v=<?= filemtime(__DIR__ . '/../../../public/assets/css/app.css') ?>">
    <link rel="stylesheet" href="/ani-mart/public/assets/css/auth/auth.css?v=<?= filemtime(__DIR__ . '/../../../public/assets/css/auth/auth.css') ?>">
</head>
<body>
<div class="auth-page"><div class="auth-card">
    <img src="/ani-mart/public/assets/img/ani-mart-logo.png" class="auth-brand-logo" alt="Ani-Mart">
    <div class="text-center mb-4"><h1 class="auth-title">Choose a new password</h1><p class="auth-subtitle mt-2">Use at least 8 characters with a letter and a number.</p></div>
    <?php if ($message = flash('error')): ?><div class="alert auth-alert alert-danger"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if (empty($validReset)): ?>
        <div class="alert auth-alert alert-warning">This reset link is invalid, expired, or has already been used.</div>
        <a class="btn auth-submit w-100" href="/ani-mart/public/password/forgot">Request another link</a>
    <?php else: ?>
        <form method="post" action="/ani-mart/public/password/reset" class="auth-form">
            <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">
            <input type="hidden" name="token" value="<?= htmlspecialchars($resetToken) ?>">
            <div class="auth-form-field"><label class="form-label auth-label" for="new-password">New password</label><input class="form-control auth-input" type="password" id="new-password" name="password" minlength="8" autocomplete="new-password" required></div>
            <div class="auth-form-field mb-4"><label class="form-label auth-label" for="confirm-password">Confirm password</label><input class="form-control auth-input" type="password" id="confirm-password" name="password_confirmation" minlength="8" autocomplete="new-password" required></div>
            <button class="btn auth-submit w-100"><i class="bi bi-shield-check me-2"></i>Change password</button>
        </form>
    <?php endif; ?>
    <div class="text-center mt-4"><a href="/ani-mart/public/login" class="auth-link">Back to login</a></div>
</div></div>
</body>
</html>
