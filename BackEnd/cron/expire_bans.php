<?php
// CLI script to expire bans and restore user status.
// Run via cron / Windows Task Scheduler.

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo "Forbidden";
    exit;
}

require_once __DIR__ . '/../config/dbconfig.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$now = (new DateTime())->format('Y-m-d H:i:s');

try {
    $conn->begin_transaction();

    // Find expired bans (latest ban per entity) where user is still marked banned.
    // We only unban if the latest ban has expired.
    $expiredSql = "
        SELECT bl.entity_type, bl.entity_id
        FROM banned_list bl
        INNER JOIN (
            SELECT entity_type, entity_id, MAX(banned_at) AS max_banned_at
            FROM banned_list
            GROUP BY entity_type, entity_id
        ) latest
            ON latest.entity_type = bl.entity_type
           AND latest.entity_id = bl.entity_id
           AND latest.max_banned_at = bl.banned_at
        WHERE bl.banned_until IS NOT NULL
          AND bl.banned_until <= NOW()
    ";

    $result = $conn->query($expiredSql);

    $toUnban = [];
    while ($row = $result->fetch_assoc()) {
        $toUnban[] = $row;
    }

    $unbannedCount = 0;

    $updateCustomer = $conn->prepare("UPDATE customers SET status = 'active' WHERE customer_id = ? AND status = 'banned'");
    $updateSupplier = $conn->prepare("UPDATE suppliers SET status = 'active' WHERE supplier_id = ? AND status = 'banned'");

    foreach ($toUnban as $row) {
        $type = $row['entity_type'];
        $id = (int)$row['entity_id'];

        if ($type === 'customer') {
            $updateCustomer->bind_param('i', $id);
            $updateCustomer->execute();
            $unbannedCount += $updateCustomer->affected_rows;
        } elseif ($type === 'supplier') {
            $updateSupplier->bind_param('i', $id);
            $updateSupplier->execute();
            $unbannedCount += $updateSupplier->affected_rows;
        }
    }

    $conn->commit();

    echo '[' . $now . "] expire_bans.php: processed=" . count($toUnban) . ", unbanned=" . $unbannedCount . "\n";
} catch (Throwable $e) {
    if ($conn && $conn->errno === 0) {
        // ignore
    }
    if ($conn) {
        $conn->rollback();
    }

    fwrite(STDERR, '[' . $now . "] expire_bans.php error: " . $e->getMessage() . "\n");
    exit(1);
}
