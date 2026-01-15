<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact | Grand Horizon Timepieces</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;500&family=Inter:wght@200;400&display=swap" rel="stylesheet">
</head>
<body>

    <main class="split-screen">
        <div class="visual-side">
            <img src="../uploads/shops/<?= $supplier_id ?>/<?= $banner5?>">
            <div class="overlay">
                <p><?= htmlspecialchars($supplier['description']) ?></p>
            </div>
        </div>

        <div class="content-side">
            <div class="form-wrapper">
                <header>
                    <h1><?= htmlspecialchars($supplier['tags'] ?? '') ?></h1>
                    <p><?= htmlspecialchars($about2 ?? '') ?></p>
                </header>

                <form class="luxury-form">
                    <div class="row">
                        <div class="field">
                            <label>First Name</label>
                            <input type="text" placeholder="Enter first name">
                        </div>
                        <div class="field">
                            <label>Last Name</label>
                            <input type="text" placeholder="Enter last name">
                        </div>
                    </div>


                 
                    <div class="field">
                        <label>Message</label>
                        <textarea rows="4" placeholder="How may we assist you today?"></textarea>
                    </div>

                    <button type="submit" class="submit-btn">Send Message</button>
                </form>

                <footer>
                    <div class="footer-item">
                        <span> <?= htmlspecialchars($supplier['email']) ?></span>
        
                    </div>
                    <div class="footer-item">
                        <span><?= htmlspecialchars($supplier['phone']) ?></span>
                       </div>

                       <div class="footer-item">
                        <span><?= htmlspecialchars($supplier['address']) ?></span>
                       </div>
                </footer>
            </div>
        </div>
    </main>

</body>
</html>