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
        $order_code = "ORD-" . strtoupper(substr(md5(time() . $customer_id), 0, 8)); 
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

       
        $detail_sql = "INSERT INTO order_detail (order_id, variant_id, quantity) VALUES (?, ?, ?)";
        $d_stmt = mysqli_prepare($conn, $detail_sql);

       
        $update_stock_sql = "UPDATE product_variant SET quantity = quantity - ? WHERE variant_id = ? AND quantity >= ?";
        $u_stmt = mysqli_prepare($conn, $update_stock_sql);

        foreach ($cart_items as $item) {
          
            mysqli_stmt_bind_param($d_stmt, "iii", $order_id, $item['variant_id'], $item['quantity']);
            mysqli_stmt_execute($d_stmt);

           
            mysqli_stmt_bind_param($u_stmt, "iii", $item['quantity'], $item['variant_id'], $item['quantity']);
            mysqli_stmt_execute($u_stmt);

           
            if (mysqli_stmt_affected_rows($u_stmt) === 0) {
                throw new Exception("Insufficient stock for item: " . $item['variant_id']);
            }
        }

       
        $del_sql = "DELETE FROM cart WHERE customer_id = ? AND supplier_id = ?";
        $del_stmt = mysqli_prepare($conn, $del_sql);
        mysqli_stmt_bind_param($del_stmt, "ii", $customer_id, $supplier_id);
        mysqli_stmt_execute($del_stmt);

        
        mysqli_commit($conn);
        return true;

    } catch (Exception $e) {
       
        mysqli_rollback($conn);
        error_log("Order Error: " . $e->getMessage()); 
        return false;
    }    
}
?>