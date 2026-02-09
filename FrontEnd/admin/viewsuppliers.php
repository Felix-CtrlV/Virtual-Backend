<?php
$pageTitle = 'View Companies';
$pageSubtitle = 'Browse and manage all companies in the mall.';
include("partials/nav.php");
?>

<section class="section active">

    <div class="card">
        <div class="section-header" style="margin-bottom: 12px;">
            <div class="search" method="post">
                <lord-icon class="search-icon" src="https://cdn.lordicon.com/xaekjsls.json" trigger="loop" delay="2000"
                    colors="primary:#ffffff" style="width:13px;height:13px">
                </lord-icon>
                <input autocomplete="off" type="text" id="searchshop" placeholder="Search Shops..." />
            </div>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Supplier</th>
                    <th>Contact</th>
                    <th>Rating</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="suppliertable">

            </tbody>
        </table>
    </div>
</section>

<script src="script.js"></script>
</body>

<script>
    const searchInput = document.getElementById("searchshop");
    const tableBody = document.getElementById("suppliertable");

    function fetchSuppliers(query = "") {
        fetch("./utils/search_suppliers.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "search=" + encodeURIComponent(query)
        })
            .then(res => res.text())
            .then(data => {
                tableBody.innerHTML = data;
            });
    }

    fetchSuppliers();

    let debounceTimer;

    searchInput.addEventListener("keyup", () => {
        clearTimeout(debounceTimer);

        debounceTimer = setTimeout(() => {
            fetchSuppliers(searchInput.value);
        }, 300);
    });

</script>


</html>