<section class="page-content collection-page">
    <div class="container">
        <div class="collectionContainer"></div>
        <h2 class="text-center mb-5">Latest Products</h2>
        <div class="row g-4" id="productResults">

        </div>
    </div>
</section>

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