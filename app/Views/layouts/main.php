<?php

use App\Core\Session;

$successMessage = Session::flash('success');
$errorMessage = Session::flash('error');
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$publicBasePath = app_base_path();
$adminBasePath = $publicBasePath . '/admin';
$isAdminRoute = $requestPath === $adminBasePath
    || str_starts_with($requestPath, $adminBasePath . '/')
    || preg_match('/(^|\s)admin(?:-|\s|$)/', (string) ($bodyClass ?? '')) === 1;

if ($isAdminRoute) {
    $bodyClass = trim(($bodyClass ?? '') . ' admin-route');
}

$content = $content ?? '';

// Browser assets must stay on the host that served this page.  Using APP_URL
// here made a phone opened at the LAN address request CSS from its own
// `localhost`, so the stylesheet (including the logo size rule) never loaded.
$assetUrl = static fn(string $path): string => app_base_path() . '/' . ltrim($path, '/');

// Keep local page styles fresh while preserving their existing load order.
$stylesheetUrl = static function (string $url): string {
    $path = parse_url($url, PHP_URL_PATH);
    $prefix = app_base_path() . '/';
    if (!is_string($path) || !str_starts_with($path, $prefix)) {
        return $url;
    }
    $file = __DIR__ . '/../../../public/' . substr($path, strlen($prefix));
    if (!is_file($file)) {
        return $url;
    }
    parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $query);
    $query['v'] = filemtime($file);
    return $path . '?' . http_build_query($query);
};

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <meta name="theme-color" content="#1f7a4d">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= e($assetUrl('assets/img/favicon-32.png')) ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?= e($assetUrl('assets/img/ani-mart-icon-192.png')) ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= e($assetUrl('assets/img/apple-touch-icon.png')) ?>">
    <link rel="manifest" href="<?= e($assetUrl('manifest.webmanifest')) ?>">

    <title>

        <?= $title ?? 'Ani-Mart' ?>

    </title>

    <!-- BOOTSTRAP -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <!-- BOOTSTRAP ICONS -->

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- GOOGLE FONT -->

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com">

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- CUSTOM CSS -->

    <link rel="stylesheet" href="<?= e($assetUrl('assets/css/app.css?v=' . filemtime(__DIR__ . '/../../../public/assets/css/app.css'))) ?>">

    <!-- SHARED SKELETONS -->
    <link rel="stylesheet" href="<?= e($assetUrl('assets/css/components/skeleton.css?v=1')) ?>">

    <!-- PAGE-SPECIFIC CSS -->
    <?php if (!empty($pageCss)): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($stylesheetUrl($pageCss), ENT_QUOTES, 'UTF-8') ?>">
    <?php endif; ?>

    <?php if (empty($hideLayout)): ?>
        <link rel="stylesheet" href="<?= e($assetUrl('assets/css/components/navbar.css?v=' . filemtime(__DIR__ . '/../../../public/assets/css/components/navbar.css'))) ?>">
    <?php endif; ?>

    <?php foreach ($pageStyles ?? [] as $stylesheet): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($stylesheetUrl($stylesheet), ENT_QUOTES, 'UTF-8') ?>">
    <?php endforeach; ?>

    <?php if ($isAdminRoute): ?>
        <link rel="stylesheet" href="<?= e($assetUrl('assets/css/pages/admin-layout.css?v=' . filemtime(__DIR__ . '/../../../public/assets/css/pages/admin-layout.css'))) ?>">
    <?php endif; ?>

    <!-- SHARED HERO DECORATION (loaded last to normalize page-specific hero variants) -->
    <link rel="stylesheet" href="<?= e($assetUrl('assets/css/components/hero-decoration.css?v=' . filemtime(__DIR__ . '/../../../public/assets/css/components/hero-decoration.css'))) ?>">

    <script src="<?= e($assetUrl('assets/js/skeleton.js?v=1')) ?>"></script>

</head>

<body class="<?= $bodyClass ?? '' ?>">

    <a class="skip-to-content" href="#main-content">Skip to main content</a>
    
    <!-- NAVBAR -->

    <?php if (empty($hideLayout)): ?>

        <?php ob_start(); require ANI_MART_ROOT . '/app/Views/partials/navbar.php'; echo normalize_app_markup((string) ob_get_clean()); ?>

    <?php endif; ?>

    <!-- FLASH -->

    <?php if (!empty($successMessage) || !empty($errorMessage)): ?>

        <div class="container mt-4">

            <?php if (!empty($successMessage)): ?>

                <div class="alert alert-success rounded-4">

                    <?= htmlspecialchars($successMessage) ?>

                </div>

            <?php endif; ?>

            <?php if (!empty($errorMessage)): ?>

                <div class="alert alert-danger rounded-4">

                    <?= htmlspecialchars($errorMessage) ?>

                </div>

            <?php endif; ?>

        </div>

    <?php endif; ?>

    <!-- PAGE CONTENT -->

    <div id="main-content" tabindex="-1">
        <?= normalize_app_markup((string) $content) ?>
    </div>

    <!-- FOOTER -->

    <?php if (empty($hideLayout)): ?>

        <?php ob_start(); require ANI_MART_ROOT . '/app/Views/partials/footer.php'; echo normalize_app_markup((string) ob_get_clean()); ?>

    <?php endif; ?>

    <!-- JS -->

    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        window.__aniMartCsrfToken = <?= json_encode(csrf_token()) ?>;

        const originalFetch = window.fetch;
        window.fetch = function(input, init = {}) {
            const method = String(init.method || 'GET').toUpperCase();
            const isMutation = ['POST', 'PUT', 'PATCH', 'DELETE'].includes(method);

            if (isMutation) {
                const headers = new Headers(init.headers || {});
                const rawBody = init.body ?? null;

                if (!headers.has('X-CSRF-Token') && !headers.has('x-csrf-token')) {
                    headers.set('X-CSRF-Token', window.__aniMartCsrfToken);
                }

                if (rawBody instanceof FormData) {
                    if (!rawBody.has('_token')) {
                        rawBody.append('_token', window.__aniMartCsrfToken);
                    }
                    init.body = rawBody;
                } else if (rawBody instanceof URLSearchParams) {
                    if (!rawBody.has('_token')) {
                        rawBody.append('_token', window.__aniMartCsrfToken);
                    }
                    init.body = rawBody.toString();
                } else if (typeof rawBody === 'string') {
                    if (!/(_token=)/.test(rawBody)) {
                        const delimiter = rawBody.length > 0 ? '&' : '';
                        init.body = rawBody + delimiter + '_token=' + encodeURIComponent(window.__aniMartCsrfToken);
                    }
                } else if (rawBody === null || rawBody === undefined) {
                    init.body = new URLSearchParams({ _token: window.__aniMartCsrfToken }).toString();
                }

                init.headers = headers;
            }

            return originalFetch.call(this, input, init);
        };
    </script>
        <script>
            window.addEventListener('pageshow', function (event) {
                if (event.persisted) {
                    window.location.reload();
                }
            });
        </script>

    <?= $pageScript ?? '' ?>

    <div class="modal fade" id="aniMartImageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down" style="max-width: min(94vw, 1100px); width: 100%; margin: 0 auto;">
            <div class="modal-content border-0 bg-dark rounded-4 overflow-hidden shadow" style="max-height: 92vh; background: linear-gradient(180deg, #1e1f22 0%, #0d0f12 100%);">
                <div class="modal-header border-0 px-3 py-2 bg-dark-subtle" style="background: rgba(255,255,255,0.04) !important;">
                    <div class="d-flex align-items-center gap-2 text-white small fw-semibold">
                        <span class="rounded-circle bg-white" style="width: 10px; height: 10px; display: inline-block; opacity: 0.85;"></span>
                        <span class="rounded-circle bg-light" style="width: 10px; height: 10px; display: inline-block; opacity: 0.7;"></span>
                        <span class="rounded-circle bg-secondary" style="width: 10px; height: 10px; display: inline-block; opacity: 0.6;"></span>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close preview"></button>
                </div>
                <div class="modal-body p-3 text-center bg-dark d-flex align-items-center justify-content-center" style="min-height: min(70vh, 720px); max-height: calc(92vh - 56px);">
                    <img id="aniMartImageModalImage" src="" alt="Expanded image preview" data-image-modal="false" style="max-height: calc(92vh - 88px); width: auto; height: auto; max-width: 100%; object-fit: contain; display: block; border-radius: 0.75rem;">
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="aniMartConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden" style="background: linear-gradient(180deg, #ffffff 0%, #f9fbf9 100%); border: 1px solid rgba(31, 122, 77, 0.08);">
                <div class="modal-body p-4 text-center">
                    <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle shadow-sm" style="width: 72px; height: 72px; background: linear-gradient(135deg, rgba(31, 122, 77, 0.12), rgba(56, 189, 113, 0.18)); color: #1f7a4d;">
                        <i class="bi bi-shield-exclamation fs-3"></i>
                    </div>
                    <h5 class="fw-bold mb-2 text-dark" id="aniMartConfirmModalTitle">Confirm action</h5>
                    <p class="mb-4 text-muted small lh-base" id="aniMartConfirmModalText">Are you sure?</p>
                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-light border px-4 fw-semibold" data-bs-dismiss="modal" style="background: #f5f7f6; border-color: #dfe9e3; color: #2d3a32;">Cancel</button>
                        <button type="button" class="btn btn-success px-4 fw-semibold shadow-sm" id="aniMartConfirmModalAction" style="background: linear-gradient(135deg, #1f7a4d 0%, #2d9a62 100%); border: none;">Confirm</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const modalElement = document.getElementById('aniMartImageModal');
            const modalImage = document.getElementById('aniMartImageModalImage');

            if (!modalElement || !modalImage) {
                return;
            }

            let modalToRestore = null;

            const openPreview = function (clickedImage) {
                const source = clickedImage.dataset.imageModalSrc
                    || clickedImage.currentSrc
                    || clickedImage.src;

                if (!source) {
                    return;
                }

                modalImage.src = source;
                modalImage.alt = clickedImage.alt
                    ? 'Expanded view: ' + clickedImage.alt
                    : 'Expanded image preview';

                const showPreview = function () {
                    bootstrap.Modal.getOrCreateInstance(modalElement).show();
                };
                const parentModal = clickedImage.closest('.modal.show');

                if (parentModal && parentModal !== modalElement) {
                    modalToRestore = parentModal;
                    parentModal.addEventListener('hidden.bs.modal', showPreview, { once: true });
                    bootstrap.Modal.getOrCreateInstance(parentModal).hide();
                    return;
                }

                modalToRestore = null;
                showPreview();
            };

            document.addEventListener('click', function (event) {
                const clickedImage = event.target.closest('img[data-image-modal="true"]');
                if (!clickedImage || clickedImage.closest('[data-image-modal="false"], #aniMartImageModal')) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();
                openPreview(clickedImage);
            });

            document.addEventListener('keydown', function (event) {
                if (event.key !== 'Enter' && event.key !== ' ') {
                    return;
                }

                const focusedImage = event.target.closest('img[data-image-modal="true"]');
                if (!focusedImage || focusedImage.closest('[data-image-modal="false"], #aniMartImageModal')) {
                    return;
                }

                event.preventDefault();
                openPreview(focusedImage);
            });

            document.querySelectorAll('img[data-image-modal="true"]').forEach(function (image) {
                image.tabIndex = image.tabIndex >= 0 ? image.tabIndex : 0;
                image.setAttribute('role', 'button');
                image.setAttribute('aria-label', 'Open image preview: ' + (image.alt || 'image'));
            });

            modalElement.addEventListener('hidden.bs.modal', function () {
                modalImage.removeAttribute('src');
                modalImage.alt = 'Expanded image preview';

                if (modalToRestore) {
                    const parentModal = modalToRestore;
                    modalToRestore = null;
                    bootstrap.Modal.getOrCreateInstance(parentModal).show();
                }
            });
        })();
    </script>

    <script>
        (function () {
            const confirmModalId = 'aniMartConfirmModal';
            const confirmModalElement = document.getElementById(confirmModalId);
            const confirmModal = confirmModalElement ? new bootstrap.Modal(confirmModalElement) : null;
            const confirmTitle = document.getElementById('aniMartConfirmModalTitle');
            const confirmText = document.getElementById('aniMartConfirmModalText');
            const confirmAction = document.getElementById('aniMartConfirmModalAction');
            let confirmHandler = null;

            function showConfirm(message, title, callback, options = {}) {
                if (!confirmModal || !confirmTitle || !confirmText || !confirmAction) {
                    return;
                }

                confirmTitle.textContent = title || 'Confirm action';
                confirmText.textContent = message || 'Are you sure?';
                confirmAction.textContent = options.confirmText || 'Confirm';

                const confirmVariant = options.confirmClass || 'btn-success';
                confirmAction.className = 'btn ' + confirmVariant + ' px-4 fw-semibold shadow-sm';
                confirmAction.style.background = confirmVariant === 'btn-danger' ? 'linear-gradient(135deg, #d63b3b 0%, #bb2e2e 100%)' : 'linear-gradient(135deg, #1f7a4d 0%, #2d9a62 100%)';
                confirmAction.style.border = 'none';
                confirmAction.style.color = '#ffffff';

                confirmHandler = callback || null;
                confirmModal.show();
            }

            if (confirmModal && confirmAction) {
                confirmAction.addEventListener('click', function () {
                    if (typeof confirmHandler === 'function') {
                        confirmHandler();
                    }
                    confirmModal.hide();
                    confirmHandler = null;
                });
            }

            document.addEventListener('click', function (event) {
                const trigger = event.target.closest('[data-confirm]');
                if (!trigger) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();

                const message = trigger.dataset.confirm || 'Are you sure?';
                const title = trigger.dataset.confirmTitle || 'Confirm action';
                const confirmTextValue = trigger.dataset.confirmText || 'Confirm';
                const confirmClass = trigger.dataset.confirmClass || 'btn-danger';
                const href = trigger.getAttribute('href');
                const form = trigger.closest('form');

                showConfirm(message, title, function () {
                    if (href) {
                        window.location.href = href;
                        return;
                    }

                    if (form) {
                        form.submit();
                        return;
                    }

                    if (trigger instanceof HTMLButtonElement && trigger.form) {
                        trigger.form.submit();
                    }
                }, { confirmText: confirmTextValue, confirmClass: confirmClass });
            });

            document.addEventListener('submit', function (event) {
                const form = event.target;
                if (!form || !form.dataset.confirm) {
                    return;
                }

                event.preventDefault();
                const title = form.dataset.confirmTitle || 'Confirm action';
                const message = form.dataset.confirm || 'Are you sure?';
                const confirmTextValue = form.dataset.confirmText || 'Confirm';
                const confirmClass = form.dataset.confirmClass || 'btn-danger';

                showConfirm(message, title, function () {
                    form.submit();
                }, { confirmText: confirmTextValue, confirmClass: confirmClass });
            });

            window.aniMartConfirm = showConfirm;
        })();
    </script>

    <script>
        if ('serviceWorker' in navigator && window.isSecureContext) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/ani-mart/public/service-worker.js').catch(() => {});
            });
        }
    </script>

</body>

</html>
