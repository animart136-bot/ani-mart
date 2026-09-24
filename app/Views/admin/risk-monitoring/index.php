<?php

$title = "Risk Monitoring";

$riskSummary = $riskSummary ?? [
    'high'   => 0,
    'medium' => 0,
    'low'    => 0
];

$farmers = $farmers ?? [];
$recentRiskAlerts = $recentRiskAlerts ?? [];

function risk_badge_class($level)
{
    return match ($level) {
        'high' => 'bg-danger',
        'medium' => 'bg-warning text-dark',
        default => 'bg-success'
    };
}

ob_start();

?>

<link rel="stylesheet" href="/ani-mart/public/assets/css/pages/admin.css">
<link rel="stylesheet" href="/ani-mart/public/assets/css/pages/dashboard.css">

<div class="container app-page-spacing">

    <div class="admin-hero mb-5">

        <div class="row align-items-center">

            <div class="col-lg-8">

                <span class="hero-badge">
                    Marketplace Integrity
                </span>

                <h1 class="hero-title mt-4">
                    Farmer Risk Monitoring
                </h1>

                <p class="hero-subtitle mt-3">
                    Detect suspicious farmer behavior, monitor accountability,
                    and protect Ani-Mart marketplace trust.
                </p>

                <a href="/ani-mart/public/dashboard" class="admin-dashboard-back-link">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    Admin dashboard
                </a>

            </div>

        </div>

    </div>

    <div class="row g-4 mb-5">

        <div class="col-lg-4 col-md-6">

            <div class="risk-card risk-metric p-4 h-100">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <small class="text-muted d-block">
                            High Risk Farmers
                        </small>

                        <h2 class="fw-bold mb-0">
                            <?= (int) $riskSummary['high'] ?>
                        </h2>

                    </div>

                    <div class="risk-signal bg-danger-subtle text-danger">
                        <i class="bi bi-exclamation-octagon"></i>
                    </div>

                </div>

            </div>

        </div>

        <div class="col-lg-4 col-md-6">

            <div class="risk-card risk-metric p-4 h-100">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <small class="text-muted d-block">
                            Medium Risk Farmers
                        </small>

                        <h2 class="fw-bold mb-0">
                            <?= (int) $riskSummary['medium'] ?>
                        </h2>

                    </div>

                    <div class="risk-signal bg-warning-subtle text-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>

                </div>

            </div>

        </div>

        <div class="col-lg-4 col-md-6">

            <div class="risk-card risk-metric p-4 h-100">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <small class="text-muted d-block">
                            Low Risk Farmers
                        </small>

                        <h2 class="fw-bold mb-0">
                            <?= (int) $riskSummary['low'] ?>
                        </h2>

                    </div>

                    <div class="risk-signal bg-success-subtle text-success">
                        <i class="bi bi-check-circle"></i>
                    </div>

                </div>

            </div>

        </div>

    </div>

    <div class="row g-4 mb-5">

        <div class="col-12">

            <div class="risk-card">

                <div class="card-body p-4">

                    <div class="d-flex justify-content-between align-items-center mb-4">

                        <h4 class="fw-bold mb-0">
                            Top Suspicious Farmers
                        </h4>

                        <span class="badge bg-dark px-3 py-2">
                            Fraud Detection
                        </span>

                    </div>

                    <div class="table-responsive">

                        <table class="table align-middle">

                            <thead>

                                <tr>

                                    <th>Farmer Name</th>
                                    <th>Shop Name</th>
                                    <th>Trust Score</th>
                                    <th>Risk Score</th>
                                    <th>Risk Level</th>
                                    <th>Cancelled Orders</th>
                                    <th>Harvest Changes</th>
                                    <th>Actions</th>

                                </tr>

                            </thead>

                            <tbody>

                                <?php if (count($farmers)): ?>

                                    <?php foreach ($farmers as $farmer): ?>

                                        <?php

                                        $farmerName =
                                            trim(
                                                ($farmer->first_name ?? '')
                                                . ' ' .
                                                ($farmer->last_name ?? '')
                                            );

                                        ?>

                                        <tr id="farmer-<?= (int) $farmer->id ?>">

                                            <td>
                                                <?= htmlspecialchars($farmerName ?: 'Unknown Farmer') ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars($farmer->shop_name ?? 'No Shop') ?>
                                            </td>

                                            <td>
                                                <?= (int) ($farmer->trust_score ?? 0) ?>
                                            </td>

                                            <td>
                                                <?= (int) ($farmer->risk_score ?? 0) ?>
                                            </td>

                                            <td>
                                                <span class="badge <?= risk_badge_class($farmer->risk_level) ?> px-3 py-2">
                                                    <?= strtoupper($farmer->risk_level) ?>
                                                </span>
                                            </td>

                                            <td>
                                                <?= (int) ($farmer->cancelled_orders ?? 0) ?>
                                            </td>

                                            <td>
                                                <?= (int) ($farmer->harvest_changes ?? 0) ?>
                                            </td>

                                            <td class="risk-table-actions">

                                                <div class="d-flex flex-wrap gap-2">

                                                    <a
                                                        href="/ani-mart/public/admin/farmers#farmer-<?= (int) $farmer->id ?>"
                                                        class="btn btn-sm btn-outline-success rounded-pill">
                                                        View Farmer Profile
                                                    </a>

                                                    <a
                                                        href="/ani-mart/public/admin/transparency?farmer_id=<?= (int) $farmer->id ?>"
                                                        class="btn btn-sm btn-outline-dark rounded-pill">
                                                        View Transparency Logs
                                                    </a>

                                                    <a href="/ani-mart/public/admin/users?search=<?= urlencode((string) ($farmer->email ?? '')) ?>" class="btn btn-sm <?= ($farmer->status ?? '') === 'suspended' ? 'btn-outline-success' : 'btn-outline-danger' ?> rounded-pill">
                                                        <?= ($farmer->status ?? '') === 'suspended' ? 'Review restoration' : 'Manage access' ?>
                                                    </a>

                                                </div>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                <?php else: ?>

                                    <tr>

                                        <td colspan="8">

                                            <div class="text-center py-5">

                                                <i
                                                    class="bi bi-shield-check text-success"
                                                    style="font-size:3rem;"></i>

                                                <h5 class="mt-3">
                                                    No suspicious farmers detected.
                                                </h5>

                                                <p class="text-muted mb-0">
                                                    Risk records will appear as marketplace activity grows.
                                                </p>

                                            </div>

                                        </td>

                                    </tr>

                                <?php endif; ?>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        </div>

        <div class="col-12">

            <div class="risk-card">

                <div class="card-body p-4">

                    <h4 class="fw-bold mb-4">
                        Recent Risk Alerts
                    </h4>

                    <?php if (count($recentRiskAlerts)): ?>

                        <?php foreach ($recentRiskAlerts as $alert): ?>

                            <div class="risk-alert <?= htmlspecialchars($alert->risk_level) ?> rounded-4 p-3 mb-3">

                                <div class="fw-semibold">
                                    <?= htmlspecialchars($alert->message) ?>
                                </div>

                            </div>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <p class="text-muted mb-0">
                            No recent risk alerts.
                        </p>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </div>

</div>

<?php

$content = ob_get_clean();

require ANI_MART_ROOT . '/app/Views/layouts/main.php';

?>
