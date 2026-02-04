<?php
/**
 * Inventory Management Utility (Full Procedural Version)
 * * Logic Workflow:
 * 1. If Stock > 0: Always show to customers (Available for purchase).
 * 2. If Stock <= 0: Hide from the Product Detail page (Removed from UI).
 */

/**
 * Determine visibility and business status for the Front-End.
 * * @param int $quantity - Current stock from 'quantity' column.
 * @param string $status - Current status from 'status' column (available/unavailable).
 * @return array - Visibility, status text, and UI classes.
 */
function getVariantStatus($quantity, $status) {
    $quantity = (int)$quantity;
    $status = strtolower(trim($status));

    // RULE 1: If there is physical stock, it must be available for customers.
    if ($quantity > 0) {
        return [
            'status' => 'Available',
            'is_visible' => true,
            'can_add_to_cart' => true,
            'badge_class' => 'bg-green-100 text-green-700',
            'text_class' => 'text-green-600'
        ];
    } 
    
    // RULE 2: If stock is 0, hide the variant from the product page immediately.
    return [
        'status' => 'Removed',
        'is_visible' => false,
        'can_add_to_cart' => false,
        'badge_class' => 'bg-gray-100 text-gray-400',
        'text_class' => 'text-gray-400'
    ];
}

/**
 * Helper to render a status badge for Admin Dashboards or Cart summaries.
 * * @param int $quantity
 * @param string $status
 * @return string HTML Span element.
 */
function renderStatusBadge($quantity, $status) {
    $info = getVariantStatus($quantity, $status);
    
    // For admin display, we show "Out of Stock" instead of "Removed" 
    // so the admin knows the record still exists but is hidden.
    $displayText = ($info['status'] === 'Removed') ? 'Out of Stock' : $info['status'];
    
    return sprintf(
        '<span class="px-2 py-1 rounded text-xs font-bold %s">%s</span>',
        $info['badge_class'],
        $displayText
    );
}

/**
 * Check if a product has any sellable stock at all.
 * Useful for showing "Sold Out" ribbons on product thumbnails.
 * * @param array $variants - Array of variant rows from database.
 * @return bool
 */
function hasAnyStock($variants) {
    foreach ($variants as $variant) {
        if ((int)$variant['quantity'] > 0) {
            return true;
        }
    }
    return false;
}
?>