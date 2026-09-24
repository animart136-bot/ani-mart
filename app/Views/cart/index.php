<?php

$title = "Shopping Cart";

ob_start();

$total = 0;

?>

<div class="container app-page-spacing">

    <div class="hero mb-5">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <span class="hero-badge">Shopping Cart</span>
                <h1 class="hero-title mt-3">Review your selected items</h1>
                <p class="hero-subtitle mt-3">Adjust quantities, manage your farm product cart, and proceed to checkout when you are ready.</p>
            </div>
        </div>
    </div>

    <?php if (!empty($items)): ?>

        <div class="row g-4">

            <!-- LEFT -->

            <div class="col-lg-8">

                <div class="cart-table">

                    <!-- HEADER -->

                    <div class="cart-table-header">

                        <div>

                            <input
                                type="checkbox"
                                id="selectAll">

                        </div>

                        <div>

                            Product

                        </div>

                        <div class="text-center">

                            Price

                        </div>

                        <div class="text-center">

                            Quantity

                        </div>

                        <div class="text-center">

                            Subtotal

                        </div>

                        <div class="text-center">

                            Remove

                        </div>

                    </div>

                    <?php

                    $currentShop = '';

                    foreach ($items as $item):

                        $subtotal =
                            $item->price *
                            $item->quantity;

                        $total +=
                            $subtotal;

                        if ($currentShop !== $item->shop_name):

                            if ($currentShop !== ''):

                                echo '</div>';

                            endif;

                            $currentShop =
                                $item->shop_name;

                    ?>

                            <!-- SHOP -->

                            <div class="shop-group">

                                <div class="shop-header">

                                    <div
                                        class="d-flex align-items-center gap-3">

                                        <input
                                            type="checkbox"
                                            class="shop-checkbox">

                                        <div class="shop-title">

                                            <i class="bi bi-shop"></i>

                                            <?= htmlspecialchars(
                                                $item->shop_name
                                            ) ?>

                                        </div>

                                    </div>

                                </div>

                            <?php endif; ?>

                            <!-- PRODUCT ROW -->

                            <div class="cart-row">

                                <div>

                                    <input
                                        type="checkbox"
                                        class="cart-checkbox"
                                        value="<?= $item->cart_id ?>"
                                        data-price="<?= $item->price ?>"
                                        data-qty="<?= $item->quantity ?>">

                                </div>

                                <div>

                                    <div class="d-flex gap-3">

                                        <img
                                            src="<?= htmlspecialchars($item->image) ?>"
                                            class="cart-product-image">

                                        <div>

                                            <h5 class="fw-bold mb-1">

                                                <?= htmlspecialchars(
                                                    $item->name
                                                ) ?>

                                            </h5>

                                            <?php if (!empty($item->variant_label)): ?>
                                                <small class="text-success fw-semibold d-block mb-1">
                                                    <?= htmlspecialchars($item->variant_label) ?>
                                                </small>
                                            <?php endif; ?>

                                            <small
                                                class="text-muted d-block mb-1">

                                                Fresh Farm Product

                                            </small>

                                            <small class="text-muted">Available stock: <?= (int) $item->stock ?></small>

                                        </div>

                                    </div>

                                </div>

                                <div>

                                    ₱<?= number_format(
                                            $item->price,
                                            2
                                        ) ?>

                                </div>

                                <div>

                                    <div
                                        class="qty-box"
                                        data-cart-id="<?= $item->cart_id ?>"
                                        data-stock="<?= (int) $item->stock ?>">

                                        <button
                                            type="button"
                                            class="qty-btn minus">

                                            -

                                        </button>

                                        <span
                                            class="qty-number">

                                            <?= $item->quantity ?>

                                        </span>

                                        <button
                                            type="button"
                                            class="qty-btn plus">

                                            +

                                        </button>

                                    </div>
                                </div>

                                <div
                                    class="fw-bold text-success">

                                    ₱<?= number_format(
                                            $subtotal,
                                            2
                                        ) ?>

                                </div>

                                <div>

                                    <form
                                        method="POST"
                                        action="/ani-mart/public/cart/remove"
                                        class="d-inline">

                                        <input
                                            type="hidden"
                                            name="_token"
                                            value="<?= csrf_token(); ?>">

                                        <input
                                            type="hidden"
                                            name="cart_id"
                                            value="<?= (int) $item->cart_id ?>">

                                        <button
                                            type="submit"
                                            class="btn btn-link text-danger text-decoration-none p-0">
                                            Delete
                                        </button>

                                    </form>

                                </div>

                            </div>

                        <?php endforeach; ?>

                            </div>

                </div>

            </div>

            <!-- SUMMARY -->

            <div class="col-lg-4">

                <div class="cart-summary-card">

                    <h4 class="fw-bold mb-4">

                        Order Summary

                    </h4>

                    <p class="small text-muted">You can check out products from multiple shops in one order. Each shop's delivery fee is included once.</p>

                    <div
                        class="d-flex justify-content-between mb-3">

                        <span>

                            Selected Items

                        </span>

                        <strong id="selectedCount">

                            <?= count($items) ?>

                        </strong>

                    </div>

                    <div
                        class="d-flex justify-content-between mb-4">

                        <span>

                            Total

                        </span>

                        <h3
                            class="fw-bold text-success"
                            id="cartTotal">

                            ₱<?= number_format(
                                    $total,
                                    2
                                ) ?>

                        </h3>

                    </div>

                    <form
                        method="POST"
                        action="/ani-mart/public/cart/checkout"
                        id="checkoutForm">

                        <input
                            type="hidden"
                            name="_token"
                            value="<?= csrf_token(); ?>">

                        <div id="selectedCartInputs"></div>

                        <button
                            type="submit"
                            class="btn btn-success w-100 rounded-pill py-3">
                            Proceed to Checkout
                        </button>

                    </form>

                </div>

            </div>

        </div>

    <?php else: ?>

        <div class="empty-cart">

            <div class="text-center">

                <i
                    class="bi bi-cart-x"
                    style="
                        font-size:5rem;
                        color:#adb5bd;
                    "></i>

                <h3 class="fw-bold mt-4">

                    Your cart is empty

                </h3>

                <p class="text-muted">

                    Browse products and add them to your cart.

                </p>

                <a
                    href="/ani-mart/public/marketplace"
                    class="btn btn-success rounded-pill px-4">

                    Continue Shopping

                </a>

            </div>

        </div>

    <?php endif; ?>

</div>

<script>
    const selectAll =
        document.getElementById(
            'selectAll'
        );

    const checkboxes =
        document.querySelectorAll(
            '.cart-checkbox'
        );

    const shopCheckboxes =
        document.querySelectorAll('.shop-checkbox');

    shopCheckboxes.forEach(shopBox => {

        shopBox.addEventListener(
            'change',
            function() {

                const shopGroup =
                    this.closest('.shop-group');

                const products =
                    shopGroup.querySelectorAll(
                        '.cart-checkbox'
                    );

                products.forEach(product => {

                    product.checked =
                        this.checked;

                });

                updateTotal();

            }
        );

    });

    checkboxes.forEach(box => {

        box.addEventListener(
            'change',
            function() {

                const shopGroup =
                    this.closest('.shop-group');

                const products =
                    shopGroup.querySelectorAll(
                        '.cart-checkbox'
                    );

                const shopBox =
                    shopGroup.querySelector(
                        '.shop-checkbox'
                    );

                shopBox.checked = [...products].every(
                    product =>
                    product.checked
                );

                updateTotal();

            }
        );

    });

    function updateTotal() {
        let total = 0;
        let count = 0;

        checkboxes.forEach(box => {

            if (box.checked) {

                total +=
                    parseFloat(
                        box.dataset.price
                    ) *
                    parseInt(
                        box.dataset.qty
                    );

                count++;

            }

        });

        const cartTotalElement = document.getElementById('cartTotal');
        const selectedCountElement = document.getElementById('selectedCount');

        if (cartTotalElement) {
            cartTotalElement.innerText = '₱' + total.toFixed(2);
        }

        if (selectedCountElement) {
            selectedCountElement.innerText = count;
        }
    }

    if (selectAll) {

        selectAll.addEventListener(
            'change',
            function() {

                checkboxes.forEach(
                    box => box.checked =
                    this.checked
                );

                updateTotal();

            }
        );
    }

    checkboxes.forEach(box => {

        box.addEventListener(
            'change',
            updateTotal
        );

    });

    updateTotal();

    document
        .querySelectorAll('.qty-box')
        .forEach(box => {

            const minus =
                box.querySelector('.minus');

            const plus =
                box.querySelector('.plus');

            const number =
                box.querySelector('.qty-number');

            const cartId =
                box.dataset.cartId;

            const stock =
                parseInt(box.dataset.stock) || 0;

            function refreshQtyButtons() {
                const currentQty = parseInt(number.innerText) || 1;
                minus.disabled = currentQty <= 1;
                plus.disabled = stock > 0 && currentQty >= stock;
            }

            refreshQtyButtons();

            plus.addEventListener(
                'click',
                () => {

                    let qty =
                        parseInt(
                            number.innerText
                        ) + 1;

                    if (stock > 0 && qty > stock) {
                        qty = stock;
                        showToast(`Only ${stock} item${stock === 1 ? '' : 's'} available.`);
                    }

                    if (qty < 1) {

                        qty = 1;
                    }

                    updateQty(
                        cartId,
                        qty,
                        number
                    );

                }
            );

            minus.addEventListener(
                'click',
                () => {

                    let qty =
                        parseInt(
                            number.innerText
                        ) - 1;

                    if (qty < 1) {

                        qty = 1;
                    }

                    updateQty(
                        cartId,
                        qty,
                        number
                    );

                }
            );

        });

    function updateQty(
        cartId,
        qty,
        element
    ) {

        fetch(
                '/ani-mart/public/cart/update', {
                    method: 'POST',

                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },

                    body: 'cart_id=' +
                        cartId +
                        '&quantity=' +
                        qty
                }
            )
            .then(r => r.json())
            .then(data => {

                if (data.success) {

                    element.innerText =
                        qty;

                    location.reload();
                }

            });

    }

    const checkoutForm = document.getElementById('checkoutForm');

    if (checkoutForm) {
        checkoutForm.addEventListener(
            'submit',
            function(e) {
                const selected =
                    document.querySelectorAll(
                        '.cart-checkbox:checked'
                    );

                if (selected.length === 0) {
                    e.preventDefault();

                    alert(
                        'Please select at least one product.'
                    );

                    return;
                }

                const container =
                    document.getElementById(
                        'selectedCartInputs'
                    );

                container.innerHTML = '';

                selected.forEach(box => {

                    const input =
                        document.createElement(
                            'input'
                        );

                    input.type =
                        'hidden';

                    input.name =
                        'cart_ids[]';

                    input.value =
                        box.value;

                    container.appendChild(
                        input
                    );

                });
            }
        );
    }
</script>

<?php

$content = ob_get_clean();
$pageCss = '/ani-mart/public/assets/css/pages/cart.css';
require ANI_MART_ROOT . '/app/Views/layouts/main.php';
?>
