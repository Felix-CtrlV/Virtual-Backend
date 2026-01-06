
<nav class="main-nav navbar navbar-expand-lg">
    <div class="container">
        <ul class="navbar-nav me-auto">
            <?php $base_url = "?supplier_id=" . $supplier_id; ?>
            <li class="nav-item">
                <a class="nav-link <?= $page === 'home' ? 'active' : '' ?>" href="<?= $base_url ?>&page=home">Home</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $page === 'products' ? 'active' : '' ?>" href="<?= $base_url ?>&page=products">Products</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $page === 'about' ? 'active' : '' ?>" href="<?= $base_url ?>&page=about">About</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $page === 'contact' ? 'active' : '' ?>" href="<?= $base_url ?>&page=contact">Contact</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $page === 'review' ? 'active' : '' ?>" href="<?= $base_url ?>&page=review">Review</a>
            </li>
            </ul>
    </div>
</nav>
<?php ?>



   
