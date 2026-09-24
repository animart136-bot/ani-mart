<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;

class CartController extends Controller
{
    public function index()
    {
        $user = Session::get('user');
        if (!$user) {
            return $this->redirect('/ani-mart/public/login');
        }

        $db = Database::connection();
        $stmt = $db->prepare("
            SELECT c.id AS cart_id, c.quantity, c.product_variant_id,
                COALESCE(c.variant_label, pv.variant_name) AS variant_label,
                COALESCE(c.variant_weight, pv.weight) AS variant_weight,
                COALESCE(c.variant_unit, pv.unit) AS variant_unit,
                COALESCE(c.price_type, p.price_type, 'retail') AS price_type,
                COALESCE(c.unit_price, pv.retail_price, pv.wholesale_price, p.price) AS price,
                p.id AS product_id, p.name, p.image,
                CASE WHEN c.product_variant_id IS NULL THEN p.stock ELSE pv.stock END AS stock,
                sp.id AS seller_profile_id, sp.shop_name
            FROM cart c
            INNER JOIN products p ON p.id = c.product_id
            LEFT JOIN product_variants pv ON pv.id = c.product_variant_id AND pv.product_id = p.id
            LEFT JOIN seller_profiles sp ON sp.id = p.seller_profile_id
            WHERE c.user_id = ?
            ORDER BY sp.shop_name, c.id DESC
        ");
        $stmt->execute([$user['id']]);

        return $this->view('cart/index', [
            'items' => $stmt->fetchAll(),
            'pageCss' => '/ani-mart/public/assets/css/pages/cart.css',
        ]);
    }

    public function add()
    {
        csrf_verify();
        header('Content-Type: application/json; charset=utf-8');

        $user = Session::get('user');
        $role = strtolower((string) ($user['role'] ?? ''));
        if (!$user || !in_array($role, ['customer', 'farmer'], true)) {
            return $this->json(false, 'A buyer account is required.');
        }

        $productId = (int) ($_POST['product_id'] ?? 0);
        $requestedVariantId = (int) ($_POST['variant_id'] ?? 0);
        if ($productId <= 0) {
            return $this->json(false, 'Invalid product.');
        }

        $db = Database::connection();
        $productStmt = $db->prepare("
            SELECT p.id, p.seller_profile_id, p.price, p.price_type, p.stock,
                   sp.user_id AS seller_user_id
            FROM products p
            INNER JOIN seller_profiles sp ON sp.id = p.seller_profile_id
            WHERE p.id = ? AND p.is_active = 1
              AND COALESCE(p.approval_status, 'approved') = 'approved'
              AND sp.status = 'approved'
            LIMIT 1
        ");
        $productStmt->execute([$productId]);
        $product = $productStmt->fetch();
        if (!$product) {
            return $this->json(false, 'This product is not available.');
        }

        if ((int) $product->seller_user_id === (int) $user['id']) {
            return $this->json(false, 'You cannot add your own product to the cart.');
        }

        $variantStmt = $db->prepare("
            SELECT id, variant_name, weight, unit, retail_price, wholesale_price,
                   minimum_wholesale_quantity, stock
            FROM product_variants
            WHERE product_id = ? AND is_active = 1
            ORDER BY id ASC
        ");
        $variantStmt->execute([$productId]);
        $variants = $variantStmt->fetchAll();

        $variant = null;
        if ($requestedVariantId > 0) {
            foreach ($variants as $candidate) {
                if ((int) $candidate->id === $requestedVariantId) {
                    $variant = $candidate;
                    break;
                }
            }
            if (!$variant) {
                return $this->json(false, 'The selected size is not available.');
            }
        } elseif (count($variants) === 1) {
            $variant = $variants[0];
        } elseif (count($variants) > 1) {
            return $this->json(false, 'Please open the product and choose a size.');
        }

        $availableStock = $variant ? (int) $variant->stock : (int) $product->stock;
        if ($availableStock <= 0) {
            return $this->json(false, 'This product is out of stock.');
        }

        $variantId = $variant ? (int) $variant->id : null;
        $unitPrice = $variant
            ? (float) ($variant->retail_price ?? $variant->wholesale_price ?? $product->price)
            : (float) $product->price;
        $priceType = (string) ($product->price_type ?? 'retail');

        $existingStmt = $db->prepare("
            SELECT id, quantity FROM cart
            WHERE user_id = ? AND product_id = ? AND product_variant_id <=> ?
            LIMIT 1
        ");
        $existingStmt->execute([$user['id'], $productId, $variantId]);
        $existing = $existingStmt->fetch();
        if ($existing && (int) $existing->quantity + 1 > $availableStock) {
            return $this->json(false, 'You cannot add more than the available stock.');
        }

        if ($existing) {
            $db->prepare('UPDATE cart SET quantity = quantity + 1, unit_price = ? WHERE id = ? AND user_id = ?')
                ->execute([$unitPrice, $existing->id, $user['id']]);
        } else {
            $db->prepare("
                INSERT INTO cart (
                    user_id, product_id, product_variant_id, variant_label,
                    variant_weight, variant_unit, price_type, unit_price, quantity
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
            ")->execute([
                $user['id'], $productId, $variantId,
                $variant->variant_name ?? null, $variant->weight ?? null,
                $variant->unit ?? null, $priceType, $unitPrice,
            ]);
        }

        $countStmt = $db->prepare('SELECT COALESCE(SUM(quantity), 0) AS total FROM cart WHERE user_id = ?');
        $countStmt->execute([$user['id']]);
        return $this->json(true, 'Added to cart.', ['count' => (int) ($countStmt->fetch()->total ?? 0)]);
    }

    public function remove()
    {
        csrf_verify();
        $user = Session::get('user');
        if (!$user) {
            return $this->redirect('/ani-mart/public/login');
        }
        Database::connection()->prepare('DELETE FROM cart WHERE id = ? AND user_id = ?')
            ->execute([(int) ($_POST['cart_id'] ?? 0), $user['id']]);
        return $this->redirect('/ani-mart/public/cart');
    }

    public function updateQuantity()
    {
        csrf_verify();
        header('Content-Type: application/json; charset=utf-8');
        $user = Session::get('user');
        if (!$user) {
            return $this->json(false, 'Login required.');
        }

        $id = (int) ($_POST['cart_id'] ?? 0);
        $quantity = max(1, (int) ($_POST['quantity'] ?? 1));
        $db = Database::connection();
        $stmt = $db->prepare("
            SELECT CASE WHEN c.product_variant_id IS NULL THEN p.stock ELSE pv.stock END AS stock
            FROM cart c
            INNER JOIN products p ON p.id = c.product_id
            LEFT JOIN product_variants pv ON pv.id = c.product_variant_id AND pv.product_id = p.id
            WHERE c.id = ? AND c.user_id = ? LIMIT 1
        ");
        $stmt->execute([$id, $user['id']]);
        $item = $stmt->fetch();
        if (!$item || (int) $item->stock <= 0) {
            return $this->json(false, 'This cart item is no longer available.');
        }

        $quantity = min($quantity, (int) $item->stock);
        $db->prepare('UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?')
            ->execute([$quantity, $id, $user['id']]);
        return $this->json(true, 'Quantity updated.', ['cart_id' => $id, 'quantity' => $quantity]);
    }

    public function clear()
    {
        csrf_verify();
        $user = Session::get('user');
        if (!$user) {
            return $this->redirect('/ani-mart/public/login');
        }
        Database::connection()->prepare('DELETE FROM cart WHERE user_id = ?')->execute([$user['id']]);
        return $this->redirect('/ani-mart/public/cart');
    }

    public function checkout()
    {
        csrf_verify();
        $user = Session::get('user');
        if (!$user) {
            return $this->redirect('/ani-mart/public/login');
        }

        $cartIds = $_POST['cart_ids'] ?? [];
        $cartIds = is_array($cartIds) ? $cartIds : [$cartIds];
        $cartIds = array_values(array_unique(array_filter(array_map('intval', $cartIds), fn($id) => $id > 0)));
        if ($cartIds === []) {
            Session::flash('error', 'Select at least one item to continue.');
            return $this->redirect('/ani-mart/public/cart');
        }

        $placeholders = implode(',', array_fill(0, count($cartIds), '?'));
        $stmt = Database::connection()->prepare("
            SELECT c.id, p.seller_profile_id, sp.user_id AS seller_user_id FROM cart c
            INNER JOIN products p ON p.id = c.product_id
            INNER JOIN seller_profiles sp ON sp.id = p.seller_profile_id
            WHERE c.id IN ($placeholders) AND c.user_id = ?
        ");
        $stmt->execute(array_merge($cartIds, [$user['id']]));
        $rows = $stmt->fetchAll();
        foreach ($rows as $row) {
            if ((int) $row->seller_user_id === (int) $user['id']) {
                Session::flash('error', 'You cannot purchase a product from your own shop.');
                return $this->redirect('/ani-mart/public/cart');
            }
        }
        if (count($rows) !== count($cartIds)) {
            Session::flash('error', 'Some selected cart items are no longer available. Please review your cart.');
            return $this->redirect('/ani-mart/public/cart');
        }

        $_SESSION['selected_cart_ids'] = $cartIds;
        return $this->redirect('/ani-mart/public/checkout');
    }

    private function json(bool $success, string $message, array $extra = [])
    {
        echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
        exit;
    }
}
