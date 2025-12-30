<?php
  if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<nav class="main-nav navbar navbar-expand-lg">
    <div class="container">
<<<<<<< HEAD
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
    
    
                
                <form class="search-bar" action="search.php" method="post">
                    <input type="text" name="searchproduct" placeholder="Search....." required>
                         <i class="fas fa-search"></i>
                    </button>
                </form>
=======
        <ul class="navbar-nav me-auto">
            <?php
            $base_url = "?supplier_id=" . $supplier_id;
            ?>
            <li class="nav-item">
                <a class="nav-link <?= $page === 'home' ? 'active' : '' ?>" href="<?= $base_url ?>&page=home">Home</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $page === 'products' ? 'active' : '' ?>"
                    href="<?= $base_url ?>&page=products">Shop</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $page === 'about' ? 'active' : '' ?>" href="<?= $base_url ?>&page=about">About
                    Us</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $page === 'collection' ? 'active' : '' ?>"
                    href="<?= $base_url ?>&page=collection">Collection</a>
            </li>
        </ul>
>>>>>>> eb2df2a0486a205b6257d1e4ad15e7395208bc45

        <form class="search-bar">
            <input type="text" name="search_product" id="searchBar" placeholder="Search.....">
            <i class="fas fa-search"></i>
        </form>
    </div>
</nav>

