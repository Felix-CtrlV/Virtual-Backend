<?php
$pageTitle = 'User Management';
$pageSubtitle = 'View and manage shopper accounts across the mall.';
include("partials/nav.php");
?>

<section class="section active">

    <div class="card">
        <div class="search">
            <lord-icon class="search-icon" src="https://cdn.lordicon.com/xaekjsls.json" trigger="loop" delay="2000"
                colors="primary:#ffffff" style="width:13px;height:13px">
            </lord-icon>
            <input autocomplete="off" type="text" id="searchuser" placeholder="Search Users..." />
        </div>
        <table>
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Joined</th>
                </tr>
            </thead>
            <tbody id="userbody">
                
            </tbody>
        </table>
    </div>
</section>

<script src="script.js"></script>
</body>

<script>
    const searchInput = document.getElementById("searchuser");
    const tableBody = document.getElementById("userbody");

    function fetchUsers(query = "") {
        fetch("./utils/search_users.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "search=" + encodeURIComponent(query)
        })
            .then(res => res.text())
            .then(data => {
                tableBody.innerHTML = data;
            });
    }

    fetchUsers();

    let debounceTimer;

    searchInput.addEventListener("keyup", () => {
    clearTimeout(debounceTimer);

    debounceTimer = setTimeout(() => {
        fetchUsers(searchInput.value);
    }, 300);
});
</script>

</html>