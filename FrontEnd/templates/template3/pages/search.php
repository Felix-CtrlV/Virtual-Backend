<?php

$host = '';
$db   = 'your_database_name';
$user = 'your_username';
$pass = 'your_password';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Get the search query from the URL
$query = isset($_GET['search']) ? trim($_GET['search']) : '';

$results = [];
if ($query !== '') {
    // Search the 'name' column for the user's input
    $stmt = $pdo->prepare("SELECT * FROM products WHERE name LIKE ?");
    $stmt->execute(["%$query%"]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search Results</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 40px; }
        .product-container { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); 
            gap: 20px; 
            max-width: 1000px; 
            margin: 0 auto; 
        }
        .product-card { 
            background: white; 
            border-radius: 8px; 
            overflow: hidden; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.1); 
            text-align: center;
        }
        .product-card img { width: 100%; height: 350px; object-fit: cover; }
        .product-card h3 { margin: 15px 0; font-size: 24px; color: #333; }
        .view-btn { 
            display: block; 
            background: #0d6efd; 
            color: white; 
            text-decoration: none; 
            padding: 12px; 
            margin: 10px; 
            border-radius: 4px; 
            font-weight: bold;
        }
        .header-title { text-align: center; margin-bottom: 40px; }
    </style>
</head>
<body>

    <div class="header-title">
        <h1>Search Results for "<?php echo htmlspecialchars($query); ?>"</h1>
    </div>

    <div class="product-container">
        <?php if (!empty($results)): ?>
            <?php foreach ($results as $item): ?>
                <div class="product-card">
                    <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="Product Image">
                    <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                    <a href="product-details.php?id=<?php echo $item['id']; ?>" class="view-btn">View</a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align: center; grid-column: 1 / -1;">No products found matching your search.</p>
        <?php endif; ?>
    </div>

</body>
</html>