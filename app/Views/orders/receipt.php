<?php

$order = $order ?? null;
$items = $items ?? [];
$download = (bool) ($download ?? false);
$receiptRole = ($receiptRole ?? 'customer') === 'farmer' ? 'farmer' : 'customer';

if (!$order) {
    http_response_code(404);
    echo 'Order not found.';
    exit;
}

$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$orderNumber = $order->order_number ?? ('#' . ($order->id ?? ''));
$status = (string) ($order->status ?? 'pending');
$statusLabel = ucwords(str_replace('_', ' ', $status));
$createdAt = !empty($order->created_at) ? strtotime($order->created_at) : false;
$totalAmount = (float) ($order->total_amount ?? 0);
$deliveryFee = $order->delivery_fee !== null
    ? (float) $order->delivery_fee
    : (float) ($order->seller_delivery_fee ?? 0);
$merchandiseTotal = 0.0;

foreach ($items as $item) {
    $merchandiseTotal += isset($item->subtotal)
        ? (float) $item->subtotal
        : (float) ($item->price ?? 0) * (int) ($item->quantity ?? 0);
}

$customerName = trim(
    (string) ($order->first_name ?? '') . ' ' . (string) ($order->last_name ?? '')
);
$shippingAddress = trim((string) sensitive_value(
    $order,
    !empty($order->delivery_address_encrypted) ? 'delivery_address' : 'shipping_address',
    !empty($order->delivery_address_encrypted) ? 'orders.delivery_address' : 'orders.shipping_address',
    $order->delivery_address ?? $order->shipping_address ?? ''
));
$paymentMethod = ucwords(str_replace('_', ' ', (string) ($order->payment_method ?? 'cash_on_delivery')));
$downloadUrl = '/ani-mart/public/orders/receipt/download?id=' . (int) $order->id;
$ordersUrl = $receiptRole === 'farmer'
    ? '/ani-mart/public/farmer/orders'
    : '/ani-mart/public/customer/orders';
$logoPath = __DIR__ . '/../../../public/assets/img/ani-mart-logo.png';
$logoData = is_file($logoPath)
    ? 'data:image/png;base64,' . base64_encode((string) file_get_contents($logoPath))
    : '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt <?= $escape($orderNumber) ?> | Ani-Mart</title>
    <link rel="icon" type="image/png" sizes="32x32" href="/ani-mart/public/assets/img/favicon-32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/ani-mart/public/assets/img/apple-touch-icon.png">
    <style>
        :root {
            color-scheme: light;
            --green: #167347;
            --green-dark: #0f5132;
            --green-soft: #eaf6ef;
            --ink: #1d2b24;
            --muted: #68756e;
            --line: #dce7e0;
            --paper: #ffffff;
            --page: #f2f7f4;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: var(--page);
            color: var(--ink);
            font: 15px/1.5 Inter, ui-sans-serif, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .receipt-shell {
            width: min(920px, calc(100% - 32px));
            margin: 32px auto;
        }

        .receipt-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 18px;
        }

        .action-group { display: flex; flex-wrap: wrap; gap: 10px; }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            padding: 9px 17px;
            border: 1px solid var(--green);
            border-radius: 999px;
            background: var(--green);
            color: #fff;
            font: inherit;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }

        .button.secondary { background: #fff; color: var(--green-dark); }

        .receipt {
            overflow: hidden;
            border: 1px solid var(--line);
            border-radius: 22px;
            background: var(--paper);
            box-shadow: 0 18px 50px rgba(26, 72, 47, .11);
        }

        .receipt-accent { height: 8px; background: linear-gradient(90deg, #167347, #49a66f); }
        .receipt-content { padding: 42px 46px; }

        .receipt-header {
            display: flex;
            justify-content: space-between;
            gap: 32px;
            padding-bottom: 30px;
            border-bottom: 1px solid var(--line);
        }

        .brand { display: flex; align-items: center; gap: 13px; }

        .receipt-brand-logo { display: block; width: 205px; height: auto; max-height: 68px; object-fit: contain; }
        .brand-copy { margin: 2px 0 0; color: var(--muted); font-size: 13px; }
        .receipt-heading { text-align: right; }
        .receipt-heading h1 { margin: 0 0 5px; font-size: 27px; letter-spacing: -.03em; }
        .receipt-heading p { margin: 0; color: var(--muted); }

        .status {
            display: inline-block;
            margin-top: 10px;
            padding: 5px 12px;
            border-radius: 999px;
            background: var(--green-soft);
            color: var(--green-dark);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            padding: 28px 0;
        }

        .detail-title {
            margin: 0 0 9px;
            color: var(--muted);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .1em;
            text-transform: uppercase;
        }

        .detail-value { margin: 0 0 4px; font-weight: 700; }
        .detail-copy { margin: 0; color: var(--muted); white-space: pre-line; }

        table { width: 100%; border-collapse: collapse; }
        th {
            padding: 12px 10px;
            border-bottom: 2px solid var(--green);
            color: var(--muted);
            font-size: 11px;
            letter-spacing: .08em;
            text-align: left;
            text-transform: uppercase;
        }
        td { padding: 17px 10px; border-bottom: 1px solid var(--line); vertical-align: top; }
        th:first-child, td:first-child { padding-left: 0; }
        th:last-child, td:last-child { padding-right: 0; text-align: right; }
        .number { text-align: right; white-space: nowrap; }
        .item-name { font-weight: 750; }
        .shop-name { margin-top: 3px; color: var(--muted); font-size: 12px; }

        .summary-wrap { display: flex; justify-content: flex-end; padding: 25px 0 5px; }
        .summary { width: min(100%, 355px); }
        .summary-row { display: flex; justify-content: space-between; gap: 30px; padding: 7px 0; }
        .summary-row span:first-child { color: var(--muted); }
        .summary-row.total {
            margin-top: 9px;
            padding-top: 15px;
            border-top: 2px solid var(--ink);
            font-size: 20px;
            font-weight: 800;
        }
        .summary-row.total span { color: var(--ink); }

        .receipt-footer {
            display: flex;
            justify-content: space-between;
            gap: 24px;
            margin-top: 28px;
            padding: 20px 22px;
            border-radius: 14px;
            background: var(--green-soft);
            color: var(--green-dark);
            font-size: 13px;
        }

        .receipt-footer strong { display: block; margin-bottom: 2px; }
        .receipt-note { margin: 18px 0 0; color: var(--muted); font-size: 12px; text-align: center; }

        @media (max-width: 680px) {
            .receipt-shell { width: min(100% - 20px, 920px); margin: 14px auto; }
            .receipt-actions, .receipt-header, .receipt-footer { align-items: stretch; flex-direction: column; }
            .receipt-heading { text-align: left; }
            .receipt-content { padding: 27px 20px; }
            .details-grid { grid-template-columns: 1fr; gap: 22px; }
            th:nth-child(2), td:nth-child(2) { display: none; }
            th, td { padding-inline: 6px; }
        }

        @page { size: A4; margin: 14mm; }

        @media print {
            body { background: #fff; font-size: 12px; }
            .no-print { display: none !important; }
            .receipt-shell { width: 100%; margin: 0; }
            .receipt { border: 0; border-radius: 0; box-shadow: none; }
            .receipt-content { padding: 8mm 5mm 0; }
            .receipt-header { padding-bottom: 7mm; }
            .details-grid { padding: 7mm 0; }
            .receipt-footer, tr { break-inside: avoid; }
            .status, .receipt-footer { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <main class="receipt-shell">
        <?php if (!$download): ?>
            <nav class="receipt-actions no-print" aria-label="Receipt actions">
                <a class="button secondary" href="<?= $escape($ordersUrl) ?>">&#8592; Back to Orders</a>
                <div class="action-group">
                    <a class="button secondary" href="<?= $escape($downloadUrl) ?>" download>Download Receipt</a>
                    <button class="button" type="button" onclick="window.print()">Print Receipt</button>
                </div>
            </nav>
        <?php endif; ?>

        <article class="receipt" aria-label="Ani-Mart order receipt">
            <div class="receipt-accent"></div>
            <div class="receipt-content">
                <header class="receipt-header">
                    <div class="brand">
                        <div>
                            <?php if ($logoData !== ''): ?>
                                <img class="receipt-brand-logo" src="<?= $logoData ?>" alt="Ani-Mart">
                            <?php else: ?>
                                <strong>Ani-Mart</strong>
                            <?php endif; ?>
                            <p class="brand-copy">Fresh produce from local farmers</p>
                        </div>
                    </div>
                    <div class="receipt-heading">
                        <h1>Order Receipt</h1>
                        <p><?= $escape($orderNumber) ?></p>
                        <span class="status"><?= $escape($statusLabel) ?></span>
                    </div>
                </header>

                <section class="details-grid">
                    <div>
                        <p class="detail-title">Billed and delivered to</p>
                        <p class="detail-value"><?= $escape($customerName !== '' ? $customerName : 'Ani-Mart Customer') ?></p>
                        <?php if (!empty($order->customer_phone)): ?>
                            <p class="detail-copy"><?= $escape($order->customer_phone) ?></p>
                        <?php endif; ?>
                        <p class="detail-copy"><?= $escape($shippingAddress !== '' ? $shippingAddress : 'No delivery address provided') ?></p>
                    </div>
                    <div>
                        <p class="detail-title">Receipt details</p>
                        <p class="detail-value">Order date: <?= $createdAt ? $escape(date('F d, Y', $createdAt)) : 'Not available' ?></p>
                        <?php if ($createdAt): ?>
                            <p class="detail-copy">Placed at <?= $escape(date('g:i A', $createdAt)) ?></p>
                        <?php endif; ?>
                        <p class="detail-copy">Payment: <?= $escape($paymentMethod) ?></p>
                        <?php if (!empty($order->requested_delivery_date)): ?>
                            <p class="detail-copy">
                                Requested delivery: <?= $escape(date('F d, Y', strtotime($order->requested_delivery_date))) ?>
                                · <?= $escape(ucfirst((string) ($order->delivery_slot ?? ''))) ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </section>

                <table aria-label="Ordered items">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th class="number">Unit price</th>
                            <th class="number">Qty</th>
                            <th class="number">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($items)): ?>
                            <?php foreach ($items as $item): ?>
                                <?php
                                    $quantity = (int) ($item->quantity ?? 0);
                                    $price = (float) ($item->price ?? 0);
                                    $subtotal = isset($item->subtotal) ? (float) $item->subtotal : $price * $quantity;
                                ?>
                                <tr>
                                    <td>
                                        <div class="item-name"><?= $escape($item->name ?? 'Product') ?></div>
                                        <?php if (!empty($item->variant_label)): ?>
                                            <div class="detail-copy"><?= $escape($item->variant_label) ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($item->shop_name)): ?>
                                            <div class="shop-name">Sold by <?= $escape($item->shop_name) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="number">&#8369;<?= number_format($price, 2) ?></td>
                                    <td class="number"><?= $quantity ?></td>
                                    <td class="number"><strong>&#8369;<?= number_format($subtotal, 2) ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4">No items found for this order.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div class="summary-wrap">
                    <div class="summary">
                        <div class="summary-row">
                            <span>Merchandise subtotal</span>
                            <span>&#8369;<?= number_format($merchandiseTotal, 2) ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Seller delivery fee</span>
                            <span><?= $deliveryFee > 0 ? '&#8369;' . number_format($deliveryFee, 2) : 'Free' ?></span>
                        </div>
                        <div class="summary-row total">
                            <span>Total</span>
                            <span>&#8369;<?= number_format($totalAmount, 2) ?></span>
                        </div>
                    </div>
                </div>

                <footer class="receipt-footer">
                    <div>
                        <strong>Thank you for supporting local farmers.</strong>
                        Keep this receipt for your order records.
                    </div>
                    <div>
                        <strong>Receipt reference</strong>
                        <?= $escape($orderNumber) ?>
                    </div>
                </footer>

                <p class="receipt-note">This receipt was generated electronically by Ani-Mart.</p>
            </div>
        </article>
    </main>
</body>
</html>
