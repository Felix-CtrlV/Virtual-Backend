<section class="page-content product-page" style="min-height: 80vh;">
    <div class="container">
        <h2 class="text-center mb-4"><i>2026 Luxury Watch List</i></h2>
        

        <div class="search-container">
            <form action="" method="GET" class="d-flex" onsubmit="event.preventDefault();">
                <input type="hidden" name="supplier_id" value="<?= $supplier_id ?>">
                <input class="form-control me-2" type="search" name="query" id="searchBar"
                       placeholder="Search products..." aria-label="Search">
                <button class="btn btn-outline-primary" type="button" onclick="fetchProduct(document.getElementById('searchBar').value)">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>

        <div class="featured-section mt-4">
            <div class="row g-4" id="productResults">
                <div class="col-12 text-center">
                    <p>Loading products...</p>
                </div>
            </div>
        </div>
    </div>
</section>

   
    <script>
    const searchInput = document.getElementById("searchBar");
    const resultContainer = document.getElementById("productResults");

    if (searchInput && resultContainer) {
        let supplierId = <?= json_encode($supplier_id) ?>;

       
        function fetchProduct(query = "") {
            
            fetch("../templates/template5/utils/search.php?supplier_id=" + supplierId, {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "search=" + encodeURIComponent(query)
            })
            .then(res => res.text())
            .then(data => {
                resultContainer.innerHTML = data;
            })
            .catch(err => {
                console.error("Error fetching products:", err);
                resultContainer.innerHTML = '<p class="text-danger text-center">Error loading products.</p>';
            });
        }

      
        fetchProduct(""); 
        

        let debounceTimer;
        searchInput.addEventListener("keyup", () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                fetchProduct(searchInput.value);
            }, 300);
        });
    }
</script>



<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<footer class="footer">
    <div class="footer-container">
        <div class="footer-section">
            <h2 class="footer-logo">LUXURY<span>WATCH</span></h2>
            <p>Providing high-quality products 2026. Quality you can trust, delivered to your door.</p>
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