<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Verify Registration | Ani-Mart</title>

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

                    Verify Email

                </h1>

                <p class="auth-subtitle mt-2">

                    Enter the 6-digit code sent to
                    <strong><?= htmlspecialchars($pendingEmail ?? '') ?></strong>.

                </p>

            </div>

            <?php if ($msg = flash('success')): ?>

                <div class="alert auth-alert alert-success">

                    <?= htmlspecialchars($msg) ?>

                </div>

            <?php endif; ?>

            <?php if ($msg = flash('error')): ?>

                <div class="alert auth-alert alert-danger">

                    <?= htmlspecialchars($msg) ?>

                </div>

            <?php endif; ?>

            <form
                method="POST"
                action="/ani-mart/public/register/verify"
                class="auth-form">

                <input
                    type="hidden"
                    name="_token"
                    value="<?= csrf_token(); ?>">

                <div class="auth-form-field mb-4">

                    <label class="form-label auth-label">

                        Verification Code

                    </label>

                    <input type="hidden" name="otp_code" id="otpCode" required>
                    <div class="auth-otp-boxes" role="group" aria-label="Six-digit verification code">
                        <?php for ($digit = 0; $digit < 6; $digit++): ?>
                            <input
                                type="text"
                                class="form-control auth-input auth-otp-box"
                                inputmode="numeric"
                                pattern="[0-9]"
                                maxlength="1"
                                autocomplete="<?= $digit === 0 ? 'one-time-code' : 'off' ?>"
                                aria-label="Verification code digit <?= $digit + 1 ?>"
                                <?= $digit === 0 ? 'autofocus' : '' ?>>
                        <?php endfor; ?>
                    </div>

                </div>

                <button
                    type="submit"
                    class="btn auth-submit w-100">

                    <i class="bi bi-check2-circle me-2"></i>

                    Verify & Create Account

                </button>

            </form>

            <form
                method="POST"
                action="/ani-mart/public/register/resend-otp"
                class="text-center mt-3">

                <input
                    type="hidden"
                    name="_token"
                    value="<?= csrf_token(); ?>">

                <button
                    type="submit"
                    class="btn auth-ghost-action">

                    Resend Code

                </button>

            </form>

            <div class="text-center mt-3">

                <a
                    href="/ani-mart/public/register"
                    class="auth-link">

                    Use a different email

                </a>

            </div>

        </div>

    </div>

</body>

<script>
    const verificationForm = document.querySelector('.auth-form');
    const otpCode = document.getElementById('otpCode');
    const otpBoxes = Array.from(document.querySelectorAll('.auth-otp-box'));

    const setOtpCode = function (value) {
        const digits = String(value).replace(/\D/g, '').slice(0, 6);
        otpBoxes.forEach(function (box, index) {
            box.value = digits[index] || '';
        });
        otpCode.value = digits;
        return digits;
    };

    const syncOtpCode = function () {
        return setOtpCode(otpBoxes.map(function (box) { return box.value; }).join(''));
    };

    otpBoxes.forEach(function (box, index) {
        box.addEventListener('input', function () {
            box.value = box.value.replace(/\D/g, '').slice(-1);
            syncOtpCode();
            if (box.value !== '' && index < otpBoxes.length - 1) {
                otpBoxes[index + 1].focus();
            }
        });

        box.addEventListener('keydown', function (event) {
            if (event.key === 'Backspace' && box.value === '' && index > 0) {
                otpBoxes[index - 1].focus();
            }
        });

        box.addEventListener('paste', function (event) {
            event.preventDefault();
            const pasted = event.clipboardData?.getData('text') || '';
            const digits = setOtpCode(pasted);
            otpBoxes[Math.min(digits.length, otpBoxes.length - 1)].focus();
        });
    });

    verificationForm?.addEventListener('submit', function (event) {
        const code = syncOtpCode();
        if (code.length !== 6) {
            event.preventDefault();
            otpBoxes.find(function (box) { return box.value === ''; })?.focus();
        }
    });
</script>

</html>
