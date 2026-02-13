<?php
$pageTitle = 'View Companies';
$pageSubtitle = 'Browse and manage all companies in the mall.';
include("partials/nav.php");
?>

<section class="section active">

    <div class="card">
        <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center; margin-bottom: 16px;">
            <div class="search" style="flex: 1; min-width: 200px;">
                <lord-icon class="search-icon" src="https://cdn.lordicon.com/xaekjsls.json" trigger="loop" delay="2000"
                    colors="primary:#ffffff" style="width:13px;height:13px"></lord-icon>
                <input autocomplete="off" type="text" id="searchshop" placeholder="Search shops..." />
            </div>
            <select id="filterStatus" style="padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-light); color: var(--text); font-size: 13px;">
                <option value="all">All statuses</option>
                <option value="active">Active</option>
                <option value="pending">Pending</option>
            </select>
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

    function fetchSuppliers() {
        var q = (document.getElementById("searchshop") || {}).value || "";
        var status = (document.getElementById("filterStatus") || {}).value || "all";
        var body = "search=" + encodeURIComponent(q) + "&status=" + encodeURIComponent(status);
        fetch("./utils/search_suppliers.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: body
        })
            .then(res => res.text())
            .then(data => {
                tableBody.innerHTML = data;
            });
    }

    fetchSuppliers();

    var debounceTimer;
    searchInput.addEventListener("keyup", function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(fetchSuppliers, 300);
    });
    document.getElementById("filterStatus").addEventListener("change", fetchSuppliers);

</script>


</html>