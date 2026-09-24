<?php
$items = $items ?? [];
$emptyText = $emptyText ?? 'No evidence images were provided.';
$allowPermanentDelete = !empty($allowPermanentDelete);
$columnClass = $columnClass ?? 'col-sm-6 col-xl-4';
$cardClass = $cardClass ?? 'card border-0 shadow-sm rounded-4 overflow-hidden';
$imageClass = $imageClass ?? 'img-fluid';
$metaClass = $metaClass ?? 'card-body p-3 small text-muted';
$alt = $alt ?? 'Evidence image';
?>
<?php if ($items): ?>
    <div class="row g-3 evidence-gallery">
        <?php foreach ($items as $item): ?>
            <div class="<?= e($columnClass) ?>">
                <figure class="<?= e($cardClass) ?> mb-0">
                    <img src="<?= e($item->image_url ?? '') ?>" class="<?= e($imageClass) ?>" alt="<?= e($alt) ?>" data-image-modal="true">
                    <figcaption class="<?= e($metaClass) ?>">
                        Uploaded <?= e(date('M d, Y', strtotime($item->created_at ?? 'now'))) ?>
                        <?php if (!empty($item->uploader_role)): ?> by <?= e($item->uploader_role) ?><?php endif; ?>
                    </figcaption>
                    <?php if ($allowPermanentDelete && !empty($item->asset_id)): ?>
                        <details class="card-body pt-0">
                            <summary class="small text-danger">Permanently delete image</summary>
                            <form method="POST" action="<?= e(app_url('admin/media/delete')) ?>" class="mt-2">
                                <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="asset_id" value="<?= (int) $item->asset_id ?>">
                                <label class="visually-hidden" for="evidence-delete-reason-<?= (int) $item->asset_id ?>">Deletion reason</label>
                                <input id="evidence-delete-reason-<?= (int) $item->asset_id ?>" name="reason" class="form-control form-control-sm" maxlength="500" placeholder="Required deletion reason" required>
                                <button class="btn btn-sm btn-outline-danger mt-2" type="submit" data-confirm="Permanently delete this image from Cloudinary? This cannot be undone.">Delete permanently</button>
                            </form>
                        </details>
                    <?php endif; ?>
                </figure>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <p class="text-muted mb-0"><?= e($emptyText) ?></p>
<?php endif; ?>
