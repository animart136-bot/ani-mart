<?php

$title = "Orders Management";
$orders = $orders ?? [];

ob_start();

?>

<div class="container py-5">

    <div class="d-flex justify-content-between mb-4">

        <h2 class="fw-bold">
            Marketplace Orders
        </h2>

    </div>

    <div class="card border-0 shadow-sm">

        <div class="card-body">

            <div class="table-responsive">

                <table class="table align-middle">

                    <thead>

                        <tr>

                            <th>Order</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach (($orders ?? []) as $order): ?>

                            <tr>

                                <td>

                                    <?= $order->order_number ?>

                                </td>

                                <td>

                                    <?= $order->first_name ?>
                                    <?= $order->last_name ?>

                                </td>

                                <td>

                                    ₱<?= number_format(
                                        $order->total_amount,
                                        2
                                    ) ?>

                                </td>

                                <td>

                                    <span class="badge bg-warning">

                                        <?= ucfirst(
                                            $order->status
                                        ) ?>

                                    </span>

                                </td>

                                <td>

                                    <?= $order->created_at ?>

                                </td>

                                <td>

                                    <form
                                        method="POST"
                                        action="/ani-mart/public/admin/orders/status"
                                        class="d-flex gap-2"
                                    >

                                        <input
                                            type="hidden"
                                            name="_token"
                                            value="<?= csrf_token(); ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= $order->id ?>"
                                        >

                                        <select
                                            name="status"
                                            class="form-select form-select-sm"
                                        >

                                            <option
                                                value="pending"
                                                <?= $order->status == 'pending' ? 'selected' : '' ?>
                                            >
                                                Pending
                                            </option>

                                            <option
                                                value="processing"
                                                <?= $order->status == 'processing' ? 'selected' : '' ?>
                                            >
                                                Processing
                                            </option>

                                            <option
                                                value="completed"
                                                <?= $order->status == 'completed' ? 'selected' : '' ?>
                                            >
                                                Completed
                                            </option>

                                            <option
                                                value="cancelled"
                                                <?= $order->status == 'cancelled' ? 'selected' : '' ?>
                                            >
                                                Cancelled
                                            </option>

                                        </select>

                                        <button
                                            class="btn btn-success btn-sm"
                                        >
                                            Save
                                        </button>

                                    </form>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

<?php

$content = ob_get_clean();

require __DIR__ . '/../app/Views/layouts/main.php';

?>
