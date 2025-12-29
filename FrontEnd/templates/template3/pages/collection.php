<section class="page-content collection-page">
    <div class="container">
        <div class="collectionContainer"></div>
        <h2 class="text-center mb-5">Latest Products</h2>
<<<<<<< HEAD
        <div class="row g-4">

              <?php
            if (!isset($_GET['category_id'])) { //category_id not found
                $products_stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE supplier_id = ? ORDER BY created_at DESC"); //pop products that's same with supplier_id
                if ($products_stmt) {
                    mysqli_stmt_bind_param($products_stmt, "i", $supplier_id);
                    mysqli_stmt_execute($products_stmt);
                    $products_result = mysqli_stmt_get_result($products_stmt);
                } else {
                    $products_result = false;
                }
            } else { //url found
                $products_stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE supplier_id = ? and category_id = ? ORDER BY created_at DESC");
                if ($products_stmt) {
                    mysqli_stmt_bind_param($products_stmt, "ii", $supplier_id, $_GET['category_id']);
                    mysqli_stmt_execute($products_stmt);
                    $products_result = mysqli_stmt_get_result($products_stmt);
                } else {
                    $products_result = false;
                }
            }
=======
        <div class="row g-4" id="productResults">
>>>>>>> 20da0d89b5d02c4796792814d73fa12757885793

        </div>
    </div>
</section>

<<<<<<< HEAD





    
 
=======
<script>
    const searchInput = document.getElementById("searchBar");
    const resultContainer = document.getElementById("productResults");

    if (searchInput && resultContainer) {
        let supplierId = <?= json_encode($supplier_id) ?>;

        function fetchProduct(query = "") {
            fetch("../templates/template3/utils/search.php?supplier_id=" + supplierId, {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "search=" + encodeURIComponent(query)
            })
                .then(res => res.text())
                .then(data => {
                    resultContainer.innerHTML = data;
                });
        }

        fetchProduct(); 

        let debounceTimer;
        searchInput.addEventListener("keyup", () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                fetchProduct(searchInput.value);
            }, 300);
        });
    }

</script>
>>>>>>> 20da0d89b5d02c4796792814d73fa12757885793
