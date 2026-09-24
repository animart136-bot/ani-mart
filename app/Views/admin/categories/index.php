<?php
$title = 'Product Categories | Ani-Mart Administration';
$bodyClass = 'admin-controls-page';
$pageCss = '/ani-mart/public/assets/css/pages/admin-controls.css';
$categories = $categories ?? [];
ob_start();
?>
<main class="container admin-controls admin-categories-page app-page-spacing">
    <div class="admin-hero mb-5">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <span class="hero-badge">Product Categories</span>
                <h1 class="hero-title mt-4">Product Categories</h1>
                <p class="hero-subtitle mt-3">Create, rename, archive, and restore product categories.</p>
                <a href="/ani-mart/public/dashboard" class="admin-dashboard-back-link"><i class="bi bi-arrow-left" aria-hidden="true"></i> Admin dashboard</a>
            </div>
        </div>
    </div>

    <div class="admin-controls-grid">
        <section class="admin-control-card admin-control-form-card">
            <h2>Add a category</h2>
            <p>New active categories appear in seller product forms and marketplace filters.</p>
            <form method="post" action="/ani-mart/public/admin/categories/save" class="vstack gap-3">
                <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                <div><label class="form-label" for="categoryName">Category name</label><input class="form-control"
                        id="categoryName" name="name" maxlength="120" required></div>
                <button class="btn btn-success">Create category</button>
            </form>
        </section>

        <section class="admin-control-card admin-control-table-card">
            <div class="control-card-heading">
                <div>
                    <h2>Category directory</h2>
                    <p><?= count($categories) ?> categories, including archived entries.</p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle admin-responsive-table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Products</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$categories): ?>
                            <tr class="admin-table-empty">
                                <td colspan="5" class="text-center text-muted py-5">No product categories found.</td>
                            </tr><?php endif; ?>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td data-label="Category"><strong><?= htmlspecialchars($category->name) ?></strong></td>
                                <td data-label="Products"><?= number_format((int) ($category->product_count ?? 0)) ?></td>
                                <td data-label="Status"><span
                                        class="badge <?= !empty($category->is_active) ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= !empty($category->is_active) ? 'Active' : 'Archived' ?></span>
                                </td>
                                <td data-label="Actions">
                                    <div class="d-flex flex-wrap gap-2">
                                        <button class="btn btn-sm btn-outline-primary" type="button"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#editCategory<?= (int) $category->id ?>">Edit</button>
                                        <form method="post" action="/ani-mart/public/admin/categories/status"><input
                                                type="hidden" name="_token"
                                                value="<?= htmlspecialchars(csrf_token()) ?>"><input type="hidden" name="id"
                                                value="<?= (int) $category->id ?>"><input type="hidden" name="status"
                                                value="<?= !empty($category->is_active) ? 'archived' : 'active' ?>"><button
                                                class="btn btn-sm <?= !empty($category->is_active) ? 'btn-outline-danger' : 'btn-outline-success' ?>"><?= !empty($category->is_active) ? 'Archive' : 'Restore' ?></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <tr class="collapse edit-category-row" id="editCategory<?= (int) $category->id ?>">
                                <td colspan="5" data-label="Edit category">
                                    <form method="post" action="/ani-mart/public/admin/categories/save"
                                        class="row g-2 align-items-end">
                                        <input type="hidden" name="_token"
                                            value="<?= htmlspecialchars(csrf_token()) ?>"><input type="hidden" name="id"
                                            value="<?= (int) $category->id ?>">
                                        <div class="col-md-8"><label class="form-label">Name</label><input
                                                class="form-control" name="name" maxlength="120"
                                                value="<?= htmlspecialchars($category->name) ?>" required></div>
                                        <div class="col-md-4"><button class="btn btn-primary w-100">Save changes</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>
<?php $content = ob_get_clean();
require __DIR__ . '/../../layouts/main.php'; ?>
