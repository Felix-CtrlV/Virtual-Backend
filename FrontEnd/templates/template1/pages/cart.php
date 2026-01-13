<?php

// 1. Redirect if not logged in

if (!isset($_SESSION['customer_id'])) {

    echo "<div class='container mt-5'><div class='alert alert-warning'>Please login to view your cart.</div></div>";

    return;

}



$customer_id = $_SESSION['customer_id'];



// 2. Fetch cart items with product details

$cart_query = "SELECT c.cart_id, c.quantity, p.product_name, p.price, p.image, p.product_id, v.color, v.size

               FROM cart c

               JOIN product_variant v ON c.variant_id = v.variant_id

               JOIN products p ON v.product_id = p.product_id

               WHERE c.customer_id = ? AND c.supplier_id = ?";



$stmt = mysqli_prepare($conn, $cart_query);

mysqli_stmt_bind_param($stmt, "ii", $customer_id, $supplier_id);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$cart_count = mysqli_num_rows($result);

$total_price = 0;

?>



<div class="container mt-5 mb-5">

    <h2 class="mb-4">Your Shopping Cart</h2>



    <?php if (mysqli_num_rows($result) > 0): ?>

        <div class="row">

            <div class="col-md-8">

                <div class="card shadow-sm">

                    <div class="card-body">

                        <table class="table table-hover align-middle">

                            <thead>

                                <tr>

                                    <th>Product</th>

                                    <th>Price</th>

                                    <th>Quantity</th>

                                    <th>Subtotal</th>

                                    <th>Action</th>

                                </tr>

                            </thead>

                            <tbody>

                                <?php while ($item = mysqli_fetch_assoc($result)):

                                    $subtotal = $item['price'] * $item['quantity'];

                                    $total_price += $subtotal;

                                    ?>

                                    <tr>

                                        <td>

                                            <div class="d-flex align-items-center">

                                                <img src="../uploads/products/<?= $item['product_id'] ?>_<?= $item['image'] ?>"

                                                    alt="<?= $item['product_name'] ?>"

                                                    style="width: 50px; height: 50px; object-fit: cover; margin-right: 15px;">

                                                <span>

                                                    <?= htmlspecialchars($item['product_name']) ?>

                                                    <small class="text-muted">Size:

                                                        <?= htmlspecialchars($item['size']) ?></small>

                                                    <div class="selected-color-container">

                                                        <small class="text-muted">Color: </small>

                                                        <span class="color-preview"

                                                            style="background-color: <?= htmlspecialchars($item['color']) ?>;"></span>

                                                    </div>

                                                </span>

                                            </div>

                                        </td>

                                        <td>$<?= number_format($item['price'], 2) ?></td>

                                        <td><?= $item['quantity'] ?></td>

                                        <td>$<?= number_format($subtotal, 2) ?></td>

                                        <td>

                                            <button class="btn btn-sm btn-outline-danger"

                                                onclick="removeFromCart(<?= $item['cart_id'] ?>)">

                                                <i class="bi bi-trash"></i>

                                            </button>

                                        </td>

                                    </tr>

                                <?php endwhile; ?>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>



            <div class="col-md-4">

                <div class="card shadow-sm">

                    <div class="card-body">

                        <h5 class="card-title">Order Summary</h5>

                        <hr>

                        <div class="d-flex justify-content-between mb-3">

                            <span>Total Items:</span>

                            <strong><?= $cart_count ?></strong>

                        </div>

                        <div class="d-flex justify-content-between mb-3">

                            <span>Grand Total:</span>

                            <strong class="text-primary fs-4">$<?= number_format($total_price, 2) ?></strong>

                        </div>

                        <button class="btn btn-primary w-100 py-2 mt-3"

                            style="background-color: var(--primary); border: none;">

                            PROCEED TO CHECKOUT

                        </button>

                        <a href="?supplier_id=<?= $supplier_id ?>&page=products"

                            class="btn btn-link w-100 text-center mt-2">

                            Continue Shopping

                        </a>

                    </div>

                </div>

            </div>

        </div>

    <?php else: ?>

        <div class="text-center py-5">

            <i class="bi bi-cart-x fs-1 text-muted"></i>

            <p class="mt-3">Your cart is empty.</p>

            <a href="?supplier_id=<?= $supplier_id ?>&page=products" class="btn btn-primary"

                style="background-color: var(--primary); border: none;">

                Shop Now

            </a>

        </div>

    <?php endif; ?>

</div>



<script>

    function removeFromCart(cartId) {

        if (confirm('Remove this item?')) {

            // Go up 3 levels to FrontEnd root, then into utils

            fetch('../../../utils/removeFromCart.php', {

                method: 'POST',

                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },

                body: new URLSearchParams({ 'cart_id': cartId })

            })

                // ... rest of code

                .then(res => res.json())

                .then(data => {

                    if (data.status === 'success') {

                        location.reload(); // Refresh to update list and total

                    } else {

                        alert(data.message);

                    }

                })

                .catch(err => console.error('Error:', err));

        }

    }

</script>