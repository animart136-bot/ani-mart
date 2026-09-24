<?php

$title = $title ?? 'File a Report';
$bodyClass = $bodyClass ?? 'report-create-page';

$pageStyles[] = '/ani-mart/public/assets/css/pages/report-create.css?v=' . filemtime(__DIR__ . '/../../../../public/assets/css/pages/report-create.css');

ob_start();
?>

<main class="container app-page-spacing report-create-shell">
    <section class="hero report-page-hero" aria-labelledby="report-page-title">
        <div class="report-hero-copy">
            <span class="hero-badge">Trust & Safety</span>
            <h1 class="hero-title" id="report-page-title">Report an issue with confidence.</h1>
            <p class="hero-subtitle">Submit a clear report, attach useful evidence, and help the Ani-Mart team resolve problems faster.</p>
        </div>
        <div class="report-tags" aria-label="Report review features">
            <span><i class="bi bi-flag" aria-hidden="true"></i> Priority</span>
            <span><i class="bi bi-paperclip" aria-hidden="true"></i> Evidence</span>
            <span><i class="bi bi-lightning-charge" aria-hidden="true"></i> Fast review</span>
        </div>
    </section>

    <div class="row g-4 align-items-start report-create-layout">
        <div class="col-xl-8">
            <div class="card report-page-card">
                <div class="card-body">
                    <div class="report-form-heading">
                        <h2 class="h4 fw-bold mb-2">Create a new report</h2>
                        <p class="text-muted mb-0">Use the form below to describe the issue and attach evidence so our
                            Trust & Safety team can respond quickly.</p>
                    </div>

                    <form id="reportForm" enctype="multipart/form-data">
                        <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                        <input id="target_id" type="hidden" name="target_id" value="0">

                        <div class="report-page-field mb-3">
                            <label for="target_type">What are you reporting?</label>
                            <select id="target_type" name="target_type" class="form-select modern-input" required>
                                <option value="">Choose what you want to report</option>
                                <option value="product">A product or listing</option>
                                <option value="seller">A farm or seller</option>
                                <option value="customer">A customer</option>
                                <option value="message">A message</option>
                                <option value="bug">A technical problem</option>
                                <option value="feature">A feature request</option>
                            </select>
                            <small class="text-muted d-block mt-2">For a problem with your own delivered order, use <a
                                    href="/ani-mart/public/customer/complaints">Order Disputes</a> instead.</small>
                            <small class="text-muted d-block mt-1">To report a community post or comment, use its report action in the <a
                                    href="/ani-mart/public/community">Community</a>.</small>
                        </div>

                        <div class="report-page-field mb-3">
                            <label for="target_reference">Who or what is it?</label>
                            <select id="target_picker" class="form-select modern-input mb-2 d-none"
                                aria-label="Select the item to report"></select>
                            <input id="target_reference" name="target_reference" type="text"
                                class="form-control modern-input" maxlength="255" required
                                placeholder="Example: Fresh Haven, product name, user name, message details, or page link">
                            <small id="target_help">Choose from the list when available, or describe the item or person
                                so we can find it.</small>
                        </div>

                        <div class="report-page-field mb-3">
                            <label for="reason">Why are you reporting it?</label>
                            <select id="reason" name="reason" class="form-select modern-input" required>
                                <option value="">Select a reason</option>
                                <optgroup label="Product Report">
                                    <option value="Fake Product">Fake Product</option>
                                    <option value="Misleading Description">Misleading Description</option>
                                    <option value="Expired Produce">Expired Produce</option>
                                    <option value="Wrong Harvest Information">Wrong Harvest Information</option>
                                    <option value="Counterfeit">Counterfeit</option>
                                    <option value="Spam Listing">Spam Listing</option>
                                    <option value="Price Manipulation">Price Manipulation</option>
                                    <option value="Inappropriate Images">Inappropriate Images</option>
                                </optgroup>
                                <optgroup label="Message & Conduct Report">
                                    <option value="Harassment">Harassment</option>
                                    <option value="Threats">Threats</option>
                                    <option value="Scam">Scam</option>
                                    <option value="Spam">Spam</option>
                                    <option value="Inappropriate Language">Inappropriate Language</option>
                                    <option value="Hate Speech">Hate Speech</option>
                                    <option value="False Information">False Information</option>
                                </optgroup>
                                <optgroup label="Bug & Feature">
                                    <option value="Broken Feature">Broken Feature</option>
                                    <option value="Page Error">Page Error</option>
                                    <option value="Performance Issue">Performance Issue</option>
                                    <option value="UI Problem">UI Problem</option>
                                    <option value="Mobile Bug">Mobile Bug</option>
                                    <option value="Upload Problem">Upload Problem</option>
                                    <option value="New Feature">New Feature</option>
                                    <option value="Improvement">Improvement</option>
                                    <option value="Accessibility">Accessibility</option>
                                    <option value="Expected Benefit">Expected Benefit</option>
                                    <option value="Other">Other</option>
                                </optgroup>
                            </select>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <div class="report-page-field">
                                    <label for="priority">Priority</label>
                                    <select id="priority" name="priority" class="form-select modern-input">
                                        <option value="medium">Medium</option>
                                        <option value="low">Low</option>
                                        <option value="high">High</option>
                                        <option value="critical">Critical</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="report-page-field">
                                    <label for="evidence_images">Evidence</label>
                                    <input id="evidence_images" type="file" name="evidence_images[]"
                                        class="form-control modern-input" accept="image/jpeg,image/png,image/webp"
                                        multiple>
                                    <small class="text-muted d-block mt-2">Upload up to 5 files for stronger
                                        review.</small>
                                </div>
                            </div>
                        </div>

                        <div class="report-page-field mb-3">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="8" class="form-control modern-input"
                                placeholder="Describe the problem clearly for our review team." required></textarea>
                        </div>

                        <div id="reportAlert" class="alert alert-danger d-none report-alert" role="alert"></div>

                        <div class="report-form-footer">
                            <div>
                                <small class="report-help-text">A detailed report helps us resolve your issue
                                    faster.</small>
                            </div>
                            <button type="submit" class="btn btn-primary btn-primary-modern btn-report-submit">Submit
                                Report</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <aside class="col-xl-4 report-sidebar" aria-label="Report guidance">
            <div class="card report-summary-card">
                <div class="card-body">
                    <h5 class="mb-3">What to include</h5>
                    <ul class="list-unstyled report-checklist mb-0">
                        <li>
                            <strong>1</strong><span>Choose the most accurate report type.</span>
                        </li>
                        <li>
                            <strong>2</strong><span>Provide a clear explanation of what happened.</span>
                        </li>
                        <li>
                            <strong>3</strong><span>Attach photos or screenshots when available.</span>
                        </li>
                        <li>
                            <strong>4</strong><span>Use the priority field only for urgent issues.</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="card report-summary-card">
                <div class="card-body">
                    <h5 class="mb-3">Fast review tips</h5>
                    <div class="report-tags">
                        <span class="report-tag">Evidence first</span>
                        <span class="report-tag">Clear summary</span>
                        <span class="report-tag">Relevant target</span>
                    </div>
                    <p class="text-muted mt-3">Our team reviews reports within 24 hours. We may follow up if more
                        information is needed.</p>
                </div>
            </div>
        </aside>
    </div>
</main>

<?php ob_start(); ?>
<script>
    const reportTargets = <?= json_encode($reportTargets ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    const targetType = document.getElementById('target_type');
    const targetPicker = document.getElementById('target_picker');
    const targetId = document.getElementById('target_id');
    const targetReference = document.getElementById('target_reference');
    const targetHelp = document.getElementById('target_help');
    const targetGuidance = {
        product: 'Select the product or listing you want to report.',
        seller: 'Select the farm or seller you want to report.',
        customer: 'Select the customer you want to report.',
        message: 'Enter the sender name and enough message details for our team to find it.',
        bug: 'Enter the page or feature where the problem happened.',
        feature: 'Enter the feature you would like us to improve or add.'
    };

    function refreshReportTargets() {
        const type = targetType.value;
        const targets = reportTargets[type] || [];
        targetPicker.replaceChildren();
        targetId.value = '0';
        targetReference.value = '';
        targetHelp.textContent = targetGuidance[type] || 'Choose what you are reporting first.';

        if (!targets.length) {
            targetPicker.classList.add('d-none');
            return;
        }

        const placeholder = new Option('Select from the list, or choose “Not listed”', '');
        placeholder.selected = true;
        targetPicker.add(placeholder);

        targets.forEach(target => {
            const option = new Option(target.label, target.id);
            option.dataset.label = target.label;
            targetPicker.add(option);
        });
        targetPicker.add(new Option('Not listed — I will describe it below', '0'));
        targetPicker.classList.remove('d-none');
    }

    targetType.addEventListener('change', refreshReportTargets);
    targetPicker.addEventListener('change', () => {
        const option = targetPicker.selectedOptions[0];
        targetId.value = targetPicker.value || '0';
        if (option && option.dataset.label) {
            targetReference.value = option.dataset.label;
        } else {
            targetReference.value = '';
            targetReference.focus();
        }
    });

    document.getElementById('reportForm').addEventListener('submit', function (event) {
        event.preventDefault();
        const form = event.currentTarget;
        const alertBox = document.getElementById('reportAlert');
        const data = new FormData(form);

        fetch('/ani-mart/public/reports/store', {
            method: 'POST',
            body: data,
        })
            .then(async response => {
                const payload = await response.text();

                try {
                    return JSON.parse(payload);
                } catch (_) {
                    throw new Error(response.status === 403
                        ? 'Your session has expired. Refresh the page and submit the report again.'
                        : 'Unable to submit the report right now. Please try again.');
                }
            })
            .then(result => {
                alertBox.classList.remove('d-none', 'alert-success', 'alert-danger');

                if (result.success) {
                    alertBox.textContent = 'Report submitted successfully. Our Trust & Safety team will review it shortly.';
                    alertBox.classList.add('alert-success');
                    window.setTimeout(() => {
                        window.location.assign(result.redirect_url || '/ani-mart/public/reports-complaints');
                    }, 600);
                } else {
                    alertBox.textContent = result.message || 'Unable to submit the report. Please try again.';
                    alertBox.classList.add('alert-danger');
                }
            })
            .catch(error => {
                alertBox.classList.remove('d-none', 'alert-success');
                alertBox.textContent = error.message || 'A server error occurred while submitting the report.';
                alertBox.classList.add('alert-danger');
            });
    });
</script>
<?php $pageScript = ob_get_clean(); ?>

<?php
$content = ob_get_clean();
require ANI_MART_ROOT . '/app/Views/layouts/main.php';
