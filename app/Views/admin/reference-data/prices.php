<?php
$records = $records ?? [];
$freshnessSummary = $freshnessSummary ?? ['current' => 0, 'needs_update' => 0, 'outdated' => 0];
$filters = $filters ?? ['q' => '', 'category' => '', 'status' => ''];
$referenceCategories = $referenceCategories ?? [];
$filteredCount = $filteredCount ?? count($records);
$supportedUnits = ['kg', 'g', 'piece', 'tray', 'dozen', 'liter', 'bundle', 'crate', 'sack'];
$title = 'Price References';
$bodyClass = 'admin-reference-prices-page';
$pageStyles[] = '/ani-mart/public/assets/css/pages/admin-reference-prices.css?v=' .
    filemtime(__DIR__ . '/../../../../public/assets/css/pages/admin-reference-prices.css');
ob_start();
$totalReferences = max(0, (int) ($count ?? 0));
$currentReferences = max(0, (int) ($freshnessSummary['current'] ?? 0));
$referenceCoverage = $totalReferences > 0
    ? (int) round(($currentReferences / $totalReferences) * 100)
    : 0;
?>
<main class="container price-reference-page app-page-spacing">
    <section class="price-reference-hero mb-4" aria-labelledby="price-reference-title">
        <div class="price-reference-hero-content">
            <span class="price-reference-eyebrow">
                Smart Price Check
            </span>

            <h1 id="price-reference-title">Price Reference Management</h1>

            <p>Maintain local market price guidance for available product categories. These references help sellers
                evaluate prices without limiting what they can charge.</p>

            <a href="/ani-mart/public/dashboard" class="price-reference-back"><i class="bi bi-arrow-left" aria-hidden="true"></i> Admin dashboard</a>

        </div>

        <div class="price-reference-hero-summary" aria-label="Reference dataset summary">
            <span>Active references</span>
            <strong><?= number_format($totalReferences) ?><small> records</small></strong>
            <div class="price-reference-coverage-track" aria-hidden="true"><i style="width: <?= max(0, min(100, $referenceCoverage)) ?>%"></i></div>
            <p><?= number_format((int) ($newToday ?? 0)) ?> new today · Updated <?= !empty($lastUpdated) ? date('M j, Y') : 'not yet' ?></p>
        </div>
    </section>

    <div class="row g-3 mb-4 price-reference-freshness">
        <div class="col-md-4">
            <div class="alert alert-success rounded-4 mb-0"><strong><?= (int) $freshnessSummary['current'] ?>
                    Current</strong><br><small>0–7 days old</small></div>
        </div>
        <div class="col-md-4">
            <div class="alert alert-warning rounded-4 mb-0"><strong><?= (int) $freshnessSummary['needs_update'] ?> Need
                    updating</strong><br><small>8–30 days old</small></div>
        </div>
        <div class="col-md-4">
            <div class="alert alert-danger rounded-4 mb-0"><strong><?= (int) $freshnessSummary['outdated'] ?>
                    Outdated</strong><br><small>Over 30 days; not used by Smart Price Check</small></div>
        </div>
    </div>

    <div class="row g-4 mb-4 price-reference-editor-grid">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <h2 class="h5 fw-bold">Add a verified reference price</h2>
                    <p class="text-muted small">The date added and Saint Bernard municipality are recorded
                        automatically.</p>
                    <form method="post" action="/ani-mart/public/admin/reference-data/prices/store" class="row g-3">
                        <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                        <div class="col-md-6"><label class="form-label" for="commodity">Product Name *</label><input
                                class="form-control" id="commodity" name="commodity" placeholder="e.g. Bitter Melon"
                                required></div>
                        <div class="col-md-6"><label class="form-label" for="category">Category *</label><select
                                class="form-select" id="category" name="category" required>
                                <option value="">Select category</option>
                                <?php foreach ($referenceCategories as $availableCategory): ?>
                                    <option value="<?= htmlspecialchars((string) $availableCategory) ?>">
                                        <?= htmlspecialchars((string) $availableCategory) ?></option><?php endforeach; ?>
                            </select></div>
                        <div class="col-md-4"><label class="form-label" for="reference_price">Reference price (₱)
                                *</label><input class="form-control" id="reference_price" name="reference_price"
                                type="number" min="0.01" step="0.01" required></div>
                        <div class="col-md-4"><label class="form-label" for="unit">Unit *</label><select
                                class="form-select" id="unit" name="unit"
                                required><?php foreach ($supportedUnits as $unit): ?>
                                    <option value="<?= $unit ?>"><?= ucfirst($unit) ?></option><?php endforeach; ?>
                            </select></div>
                        <div class="col-md-4"><label class="form-label" for="price_date">Date added</label><input
                                class="form-control" id="price_date" type="date" value="<?= date('Y-m-d') ?>" readonly>
                        </div>
                        <div class="col-md-6"><label class="form-label" for="minimum_price">Minimum price
                                (₱)</label><input class="form-control" id="minimum_price" name="minimum_price"
                                type="number" min="0" step="0.01"></div>
                        <div class="col-md-6"><label class="form-label" for="maximum_price">Maximum price
                                (₱)</label><input class="form-control" id="maximum_price" name="maximum_price"
                                type="number" min="0" step="0.01"></div>
                        <div class="col-12"><label class="form-label" for="municipality">Municipality</label><input
                                class="form-control" id="municipality" value="Saint Bernard" readonly></div>
                        <div class="col-12"><button class="btn btn-success px-4">Save reference price</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 price-reference-list-card">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h2 class="h5 fw-bold mb-0">All active price references</h2>
                <small class="text-muted">Showing <?= number_format((int) $filteredCount) ?> of
                    <?= number_format((int) ($count ?? 0)) ?></small>
            </div>
            <form method="get" action="/ani-mart/public/admin/reference-data/prices"
                class="row g-2 align-items-end mb-4 price-reference-filter-form" role="search">
                <div class="col-lg-5">
                    <label class="form-label" for="referenceSearch">Search references</label>
                    <input class="form-control" id="referenceSearch" name="q" type="search"
                        value="<?= htmlspecialchars((string) ($filters['q'] ?? '')) ?>"
                        placeholder="Product Name or category">
                </div>
                <div class="col-md-4 col-lg-3">
                    <label class="form-label" for="referenceCategory">Category</label>
                    <select class="form-select" id="referenceCategory" name="category">
                        <option value="">All categories</option>
                        <?php foreach ($referenceCategories as $filterCategory): ?>
                            <option value="<?= htmlspecialchars((string) $filterCategory) ?>" <?= ($filters['category'] ?? '') === $filterCategory ? 'selected' : '' ?>><?= htmlspecialchars((string) $filterCategory) ?>
                            </option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 col-lg-2">
                    <label class="form-label" for="referenceStatus">Status</label>
                    <select class="form-select" id="referenceStatus" name="status">
                        <option value="">All statuses</option>
                        <option value="new" <?= ($filters['status'] ?? '') === 'new' ? 'selected' : '' ?>>New today
                        </option>
                        <option value="current" <?= ($filters['status'] ?? '') === 'current' ? 'selected' : '' ?>>Current
                        </option>
                        <option value="needs_update" <?= ($filters['status'] ?? '') === 'needs_update' ? 'selected' : '' ?>>Needs update</option>
                        <option value="outdated" <?= ($filters['status'] ?? '') === 'outdated' ? 'selected' : '' ?>>
                            Outdated</option>
                    </select>
                </div>
                <div class="col-md-4 col-lg-2 d-flex gap-2">
                    <button class="btn btn-success flex-grow-1" type="submit"><i
                            class="bi bi-search me-1"></i>Search</button>
                    <a class="btn btn-outline-secondary" href="/ani-mart/public/admin/reference-data/prices"
                        aria-label="Clear search and filters"><i class="bi bi-x-lg"></i></a>
                </div>
            </form>
            <div class="table-responsive price-reference-table-wrap">
                <table class="table align-middle price-reference-table">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Reference</th>
                            <th>Price range</th>
                            <th>Municipality</th>
                            <th>Date added</th>
                            <th>Freshness</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$records): ?>
                            <tr class="price-reference-empty">
                                <td colspan="7" class="text-center text-muted py-4">
                                    <?= array_filter($filters) ? 'No active references match your search and filters.' : 'No reference prices have been added yet.' ?>
                                </td>
                            </tr><?php endif; ?>
                        <?php foreach ($records as $record):
                            $status = $record['freshness_status'];
                            $badge = $status === 'current' ? 'success' : ($status === 'needs_update' ? 'warning' : 'danger');
                            $label = $status === 'current' ? 'Current' : ($status === 'needs_update' ? 'Needs update' : 'Outdated');
                            $municipality = (string) ($record['municipality'] ?? 'Saint Bernard');
                            ?>
                            <tr class="price-reference-record">
                                <td data-label="Product Name">
                                    <strong><?= htmlspecialchars($record['commodity']) ?></strong><?php if (!empty($record['is_new_today'])): ?>
                                        <span
                                            class="badge rounded-pill text-bg-primary ms-1">New</span><?php endif; ?><br><small
                                        class="text-muted"><?= htmlspecialchars((string) ($record['category'] ?? '')) ?></small>
                                </td>
                                <td data-label="Reference" class="fw-bold">
                                    ₱<?= number_format((float) $record['reference_price'], 2) ?>/<?= htmlspecialchars($record['unit']) ?>
                                </td>
                                <td data-label="Price range">
                                    <?php if ($record['minimum_price'] !== null || $record['maximum_price'] !== null): ?>
                                        <?= $record['minimum_price'] !== null ? '₱' . number_format((float) $record['minimum_price'], 2) : '—' ?>
                                        –
                                        <?= $record['maximum_price'] !== null ? '₱' . number_format((float) $record['maximum_price'], 2) : '—' ?>
                                        <small class="d-block text-muted">per <?= htmlspecialchars((string) $record['unit']) ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">Not specified</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Municipality"><?= htmlspecialchars($municipality) ?></td>
                                <td data-label="Date added"><?= date('M j, Y', strtotime($record['price_date'])) ?><small
                                        class="d-block text-muted">Updated
                                        <?= date('M j, g:i A', strtotime($record['updated_at'])) ?></small></td>
                                <td data-label="Freshness"><span class="badge text-bg-<?= $badge ?>"><?= $label ?></span>
                                </td>
                                <td data-label="Action">
                                    <div class="price-reference-actions">
                                        <button class="btn btn-sm btn-outline-primary" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#editPrice<?= (int) $record['id'] ?>"
                                            aria-expanded="false" aria-controls="editPrice<?= (int) $record['id'] ?>"><i
                                                class="bi bi-pencil me-1"></i>Edit</button>
                                        <form method="post" action="/ani-mart/public/admin/reference-data/prices/delete"
                                            onsubmit="return confirm('Delete this price reference? It will no longer be used by Smart Price Check.');">
                                            <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                                            <input type="hidden" name="id" value="<?= (int) $record['id'] ?>">
                                            <button class="btn btn-sm btn-outline-danger" type="submit"><i
                                                    class="bi bi-trash me-1"></i>Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <tr class="collapse price-reference-edit-row" id="editPrice<?= (int) $record['id'] ?>">
                                <td colspan="7" class="bg-body-tertiary p-3">
                                    <form method="post" action="/ani-mart/public/admin/reference-data/prices/update"
                                        class="row g-2 align-items-end">
                                        <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                                        <input type="hidden" name="id" value="<?= (int) $record['id'] ?>">
                                        <div class="col-md-4"><label class="form-label">Product Name *</label><input
                                                class="form-control form-control-sm" name="commodity"
                                                value="<?= htmlspecialchars((string) $record['commodity']) ?>" required>
                                        </div>
                                        <div class="col-md-4"><label class="form-label">Category *</label><select
                                                class="form-select form-select-sm" name="category" required>
                                                <option value="">Select category</option>
                                                <?php foreach ($referenceCategories as $availableCategory): ?>
                                                    <option value="<?= htmlspecialchars((string) $availableCategory) ?>"
                                                        <?= ($record['category'] ?? '') === $availableCategory ? 'selected' : '' ?>><?= htmlspecialchars((string) $availableCategory) ?></option>
                                                <?php endforeach; ?>
                                            </select></div>
                                        <div class="col-md-3"><label class="form-label">Reference price (₱) *</label><input
                                                class="form-control form-control-sm" name="reference_price" type="number"
                                                min="0.01" step="0.01"
                                                value="<?= htmlspecialchars((string) $record['reference_price']) ?>"
                                                required></div>
                                        <div class="col-md-3"><label class="form-label">Minimum price (₱)</label><input
                                                class="form-control form-control-sm" name="minimum_price" type="number"
                                                min="0" step="0.01"
                                                value="<?= htmlspecialchars((string) ($record['minimum_price'] ?? '')) ?>">
                                        </div>
                                        <div class="col-md-3"><label class="form-label">Maximum price (₱)</label><input
                                                class="form-control form-control-sm" name="maximum_price" type="number"
                                                min="0" step="0.01"
                                                value="<?= htmlspecialchars((string) ($record['maximum_price'] ?? '')) ?>">
                                        </div>
                                        <div class="col-md-3"><label class="form-label">Unit *</label><select
                                                class="form-select form-select-sm" name="unit"
                                                required><?php foreach ($supportedUnits as $availableUnit): ?>
                                                    <option value="<?= $availableUnit ?>" <?= $record['unit'] === $availableUnit ? 'selected' : '' ?>><?= ucfirst($availableUnit) ?></option>
                                                <?php endforeach; ?>
                                            </select></div>
                                        <div class="col-md-3"><label class="form-label">Date added</label><input
                                                class="form-control form-control-sm" type="date"
                                                value="<?= htmlspecialchars((string) $record['price_date']) ?>" readonly>
                                        </div>
                                        <div class="col-md-4"><label class="form-label">Municipality</label><input
                                                class="form-control form-control-sm" value="Saint Bernard" readonly></div>
                                        <div class="col-12 d-flex justify-content-end gap-2 mt-3">
                                            <button class="btn btn-sm btn-outline-secondary" type="button"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#editPrice<?= (int) $record['id'] ?>">Cancel</button>
                                            <button class="btn btn-sm btn-primary" type="submit"><i
                                                    class="bi bi-check-lg me-1"></i>Save changes</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</main>
<?php $content = ob_get_clean();
require ANI_MART_ROOT . '/app/Views/layouts/main.php'; ?>
