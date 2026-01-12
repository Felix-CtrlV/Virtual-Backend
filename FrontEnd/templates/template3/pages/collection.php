<section class="page-content collection-page">
    <div class="container" style="padding-top: 0;">
        <!-- <div class="collectionContainer"></div> -->
        <h2 class="text-center">Latest Stock Items</h2>
        <div class="search-bar">
            <input type="text" name="search_product" id="searchBar" placeholder="Search items.....">
            <i class="fas fa-search"></i>
        </div>
        <div class="row g-4" id="productResults">

        </div>
    </div>
</section>

<script>
    const searchInput = document.getElementById("searchBar");
    const resultContainer = document.getElementById("productResults");

    if (searchInput && resultContainer) {
        let supplierId = <?= json_encode($supplier_id) ?>;
        const urlParams = new URLSearchParams(window.location.search);
        let categoryId = urlParams.get('category_id') || "";
        function fetchProduct(query = "") {
           
            fetch("../templates/template3/utils/search.php?supplier_id=" + supplierId + "&category_id=" + categoryId, {
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