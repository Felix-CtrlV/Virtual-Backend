<style>
    .pagination-container { display: flex; justify-content: center; gap: 8px; margin: 30px 0; }
    .page-btn {
        border: 1px solid #ddd; padding: 10px 18px; cursor: pointer; background: #fff;
        border-radius: 6px; font-weight: bold; transition: 0.3s;
    }
    .page-btn:hover:not(:disabled) { border-color: #007bff; color: #007bff; background: #f0f7ff; }
    .page-btn.active { background: #007bff; border-color: #007bff; color: #fff; }
    .page-btn:disabled { color: #ccc; cursor: not-allowed; }
</style>

<section class="page-content collection-page">    
    <div class="container" style="padding-top: 0;">
        
        <h2 class="text-center">Latest Stock Items</h2>
        <div class="search-bar">
            <input type="text" name="search_product" id="searchBar" placeholder="Search items.....">
            <i class="fas fa-search"></i>
        </div>
        <div class="row g-4" id="productResults"></div> <div id="paginationControls" class="pagination-container"></div> </div>
</section>

<script>
    const searchInput = document.getElementById("searchBar");
    const resultContainer = document.getElementById("productResults");
    const paginationContainer = document.getElementById("paginationControls");

    let supplierId = <?= json_encode($supplier_id) ?>;
    const urlParams = new URLSearchParams(window.location.search);
    let categoryId = urlParams.get('category_id') || "";

    function fetchProduct(query = "", page = 1) {
        let params = new URLSearchParams();
        params.append('search', query);
        params.append('page', page);
        params.append('supplier_id', supplierId);
        params.append('category_id', categoryId);

        fetch("../templates/template3/utils/search.php?supplier_id=" + supplierId, {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: params.toString()
        })
        .then(res => res.json())
        .then(data => {
            resultContainer.innerHTML = data.html;
            renderPagination(data.total_pages, page);
        });
    }

    function renderPagination(total, current) {
        let html = "";
        if (total <= 1) { paginationContainer.innerHTML = ""; return; }
        
        for (let i = 1; i <= total; i++) {
            html += `<button class="page-btn ${i === current ? 'active' : ''}" onclick="fetchProduct(searchInput.value, ${i})">${i}</button>`;
        }
        paginationContainer.innerHTML = html;
    }

    fetchProduct();

    let debounceTimer;
    searchInput.addEventListener("keyup", () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => fetchProduct(searchInput.value, 1), 300);
    });
</script>