<?php

function placeOrder($conn, $customer_id, $supplier_id) {
    
    $cart_query = "SELECT c.variant_id, c.quantity, p.price 
                   FROM cart c 
                   JOIN product_variant v ON c.variant_id = v.variant_id 
                   JOIN products p ON v.product_id = p.product_id 
                   WHERE c.customer_id = ? AND c.supplier_id = ?";
    
    $stmt = mysqli_prepare($conn, $cart_query);
    mysqli_stmt_bind_param($stmt, "ii", $customer_id, $supplier_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $cart_items = [];
    $grand_total = 0;
    
    while ($row = mysqli_fetch_assoc($result)) {
        $cart_items[] = $row;
        $grand_total += ($row['price'] * $row['quantity']);
    }

    if (empty($cart_items)) return false;

    mysqli_begin_transaction($conn);

    try {
        $order_code = "ORD-" . strtoupper(substr(md5(time()), 0, 8));
        $payment_method = "crediverse";
        $order_status = "confirm";
        $order_date = date('Y-m-d H:i:s');

        $order_sql = "INSERT INTO orders (order_code, supplier_id, customer_id, price, payment_method, order_status, order_date) 
                      VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $o_stmt = mysqli_prepare($conn, $order_sql);
        mysqli_stmt_bind_param($o_stmt, "siidsss", $order_code, $supplier_id, $customer_id, $grand_total, $payment_method, $order_status, $order_date);
        
        if (!mysqli_stmt_execute($o_stmt)) {
            throw new Exception("Order insertion failed");
        }

        $order_id = mysqli_insert_id($conn);

        // Prepare statements
        $detail_sql = "INSERT INTO order_detail (order_id, variant_id, quantity) VALUES (?, ?, ?)";
        $d_stmt = mysqli_prepare($conn, $detail_sql);

        // NECESSARY CHANGE: Added "AND quantity >= ?" to prevent negative stock
        $update_stock_sql = "UPDATE product_variant SET quantity = quantity - ? WHERE variant_id = ? AND quantity >= ?";
        $u_stmt = mysqli_prepare($conn, $update_stock_sql);

        foreach ($cart_items as $item) {
            // Insert order details
            mysqli_stmt_bind_param($d_stmt, "iii", $order_id, $item['variant_id'], $item['quantity']);
            mysqli_stmt_execute($d_stmt);

            // NECESSARY CHANGE: Bind the requested quantity twice to satisfy the new WHERE clause
            mysqli_stmt_bind_param($u_stmt, "iii", $item['quantity'], $item['variant_id'], $item['quantity']);
            mysqli_stmt_execute($u_stmt);

            // NECESSARY CHANGE: Check if the update actually happened
            // If 0 rows were affected, it means the stock was lower than the requested quantity
            if (mysqli_stmt_affected_rows($u_stmt) === 0) {
                throw new Exception("Stock became insufficient for variant ID: " . $item['variant_id']);
            }
        }

        $del_sql = "DELETE FROM cart WHERE customer_id = ? AND supplier_id = ?";
        $del_stmt = mysqli_prepare($conn, $del_sql);
        mysqli_stmt_bind_param($del_stmt, "ii", $customer_id, $supplier_id);
        mysqli_stmt_execute($del_stmt);

        // If everything is successful, commit all changes at once
        mysqli_commit($conn);
        return true;

    } catch (Exception $e) {
        // If ANY step fails (including stock check), undo everything
        mysqli_rollback($conn);
        return false;
    }    
}
    
?>