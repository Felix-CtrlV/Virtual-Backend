<section class="page-content about-page py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                
                <h2 class="text-center mb-4">About <?= htmlspecialchars($supplier['company_name']) ?></h2>
                
                <?php if (!empty($shop_assets['about'])): ?>
                    <div class="about-content mb-4 text-center">
                        <p class="lead font-italic" style="color: #555;">
                            "<?= nl2br(htmlspecialchars($shop_assets['about'])) ?>"
                        </p>
                    </div>
                <?php endif; ?>
        </div>
        </div>
    </div>
</section>

                    
                    



                    


    

<footer class="footer">
    <div class="footer-container">
        <div class="footer-section">
            <h2 class="footer-logo">LUXURY<span>WATCH</span></h2>
      <?php 
    
    $since_year = date("Y"); 
        ?>

    <p>Providing high-quality products  <?php echo $since_year; ?>. Quality you can trust, delivered to your door.</p>
            
            <div class="social-links">
                <a href=""><i class="fab fa-facebook-f"></i></a>
                <a href=""><i class="fab fa-instagram"></i></a>
                <a href=""><i class="fab fa-twitter"></i></a>
                <a href=""><i class="fab fa-viber"></i></a>
            </div>
        </div>

        <div class="footer-section">
            <h3>Quick Links</h3>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="products.php">Products</a></li>
                <li><a href="about.php">About Us</a></li>
                <li><a href="contact.php">Contact Us</a></li>
                <li><a href="review.php">Review</a></li>
            </ul>
        </div>

        <div class="footer-section">
            <h3>Contact Us</h3>
            <p><i class="fas fa-envelope"></i> kaungpyaesone@gmail.com</p>
            <p><i class="fas fa-envelope"></i> kaungswanthaw@gmail.com</p>
            <p><i class="fas fa-phone"></i> +95 123456</p>
            <p><i class="fas fa-map-marker-alt"></i> Metro IT and Japanese Language Center</p>
        </div>
    </div>

    <div class="footer-bottom">
        <p>&copy; <?php echo date("Y"); ?> <span>MALLTIVERSE</span>. All rights reserved.</p>
    </div>
</footer>
</body>
</html>