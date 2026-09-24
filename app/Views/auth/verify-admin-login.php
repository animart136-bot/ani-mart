<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrator Verification | Ani-Mart</title>
    <link rel="icon" type="image/png" sizes="32x32" href="/ani-mart/public/assets/img/favicon-32.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/ani-mart/public/assets/css/app.css?v=<?= filemtime(__DIR__ . '/../../../public/assets/css/app.css') ?>">
    <link rel="stylesheet" href="/ani-mart/public/assets/css/auth/auth.css?v=<?= filemtime(__DIR__ . '/../../../public/assets/css/auth/auth.css') ?>">
</head>
<body>
<div class="auth-page"><div class="auth-card">
    <img src="/ani-mart/public/assets/img/ani-mart-logo.png" class="auth-brand-logo" alt="Ani-Mart">
    <div class="text-center mb-4"><span class="badge text-bg-success rounded-pill mb-3">Administrator protection</span><h1 class="auth-title">Confirm it’s you</h1><p class="auth-subtitle mt-2">Enter the 6-digit code sent to <?= htmlspecialchars((string) $pendingEmail) ?>.</p></div>
    <?php if ($message = flash('success')): ?><div class="alert auth-alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($message = flash('error')): ?><div class="alert auth-alert alert-danger"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <form method="post" action="/ani-mart/public/login/admin-verify" class="auth-form">
        <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">
        <div class="auth-form-field mb-4"><label class="form-label auth-label" for="admin-code">Verification code</label><input class="form-control auth-input auth-otp-input" type="text" id="admin-code" name="otp_code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required></div>
        <button class="btn auth-submit w-100"><i class="bi bi-shield-lock me-2"></i>Verify and sign in</button>
    </form>
    <form method="post" action="/ani-mart/public/login/admin-resend" class="text-center mt-3"><input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>"><button class="btn auth-ghost-action">Resend code</button></form>
    <div class="text-center mt-3"><a href="/ani-mart/public/login" class="auth-link">Cancel and return to login</a></div>
</div></div>
</body>
</html>
