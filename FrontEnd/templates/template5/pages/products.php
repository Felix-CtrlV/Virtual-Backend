<?php

$supplier_id = isset($_GET['supplier_id']) ? intval($_GET['supplier_id']) : 10;
$query = "SELECT * FROM shop_assets WHERE supplier_id = $supplier_id";
$result = mysqli_query($conn, $query);
$shop_assets = mysqli_fetch_assoc($result);
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<div class="search-bar">
    <input type="text" name="search" id="searchBar" placeholder="Search.....">
    <button type="submit">
        <i class="fas fa-search"></i>
    </button>
</div>

<section class="page-content products-page t5-products-section">
    <div class="container">
        <h2 class="text-center mb-5"><?= htmlspecialchars($shop_assets['description'] ?? '') ?></h2>

        <div class="row g-4 products-container" id="productResults">
            <div class="col-12 text-center">
                <p>Loading products...</p>
            </div>
        </div>
    </div>
</section>


<script>
    const searchInput = document.getElementById("searchBar");
    const resultContainer = document.getElementById("productResults");

    if (searchInput && resultContainer) {
        let supplierId = <?= json_encode($supplier_id) ?>;

       
        function fetchProduct(query = "", page = 1) {
           /* resultContainer.style.opacity = "0.5"; */

            fetch("../templates/template5/utils/search.php?supplier_id=" + supplierId, {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
              
                body: "search=" + encodeURIComponent(query) + "&page=" + page 
            })
            .then(res => res.text())
            .then(data => {
                resultContainer.innerHTML = data;
                resultContainer.style.opacity = "1";
            })
            .catch(err => {
                resultContainer.innerHTML = '<div class="col-12 text-center text-danger">Error loading products.</div>';
                resultContainer.style.opacity = "1";
            });
        }

        
        fetchProduct(); 

        
        let debounceTimer;
        searchInput.addEventListener("keyup", () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                fetchProduct(searchInput.value, 1); 
            }, 300);
        });

       
        resultContainer.addEventListener("click", function(e) {
            let link = e.target.closest(".pagination-link");
            if (link) {
                e.preventDefault();
                let pageNumber = link.getAttribute("data-page");
                fetchProduct(searchInput.value, pageNumber);
                
                
                resultContainer.scrollIntoView({ behavior: 'smooth' });
            }
        });
    }
</script>