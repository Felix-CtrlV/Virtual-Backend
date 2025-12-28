<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<nav class="main-nav navbar navbar-expand-lg">
    <div class="container">
            <ul class="navbar-nav me-auto">                
                <?php
                $base_url = "?supplier_id=" . $supplier_id;
                ?>                
                <li class="nav-item">
                    <a class="nav-link <?= $page === 'home' ? 'active' : '' ?>" href="<?= $base_url ?>&page=home">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $page === 'products' ? 'active' : '' ?>" href="<?= $base_url ?>&page=products">Shop</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $page === 'about' ? 'active' : '' ?>" href="<?= $base_url ?>&page=about">About Us</a>
                </li>   
                <li class="nav-item">
                    <a class="nav-link <?= $page === 'collection' ? 'active' : '' ?>" href="<?= $base_url ?>&page=collection">Collection</a>
                </li>                
            </ul>
            <?php if ($page !== 'home' && $page !== 'about'): ?>
                <form class="search-bar">
                    <input type="text" placeholder="Search.....">
                    <i class="fas fa-search"></i>
                </form>
            <?php endif; ?>
    </div>
</nav>

