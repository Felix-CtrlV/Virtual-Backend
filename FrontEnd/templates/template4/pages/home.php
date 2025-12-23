<section class="container my-5">
    <div class="row text-center">
        <div class="col-md-4 mb-4">
            <div class="p-4 border-0 shadow-sm rounded bg-light h-100">
                <h3 class="h5 fw-bold" style="color: var(--primary);">Quality Assured</h3>
                <p class="text-muted small">We source only the finest materials for our clients.</p>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="p-4 border-0 shadow-sm rounded bg-light h-100">
                <h3 class="h5 fw-bold" style="color: var(--primary);">Fast Delivery</h3>
                <p class="text-muted small">Efficient logistics to get products to you on time.</p>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="p-4 border-0 shadow-sm rounded bg-light h-100">
                <h3 class="h5 fw-bold" style="color: var(--primary);">Support 24/7</h3>
                <p class="text-muted small">Dedicated account managers for every supplier.</p>
            </div>
        </div>
    </div>
</section>

<section class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Featured Products</h2>
        <a href="?supplier_id=<?= $supplier_id ?>&page=products" class="btn btn-outline-primary btn-sm" style="border-color: var(--primary); color: var(--primary);">View All</a>
    </div>

    <div class="row">
        <?php for ($i = 1; $i <= 4; $i++): ?>
            <div class="col-6 col-md-3 mb-4">
                <div class="card h-100 border-0 shadow-sm product-card">
                    <div class="bg-secondary rounded-top" style="height: 200px; background: #f0f0f0 url('../assets/placeholder-prod.jpg') center/cover;"></div>
                    <div class="card-body">
                        <h5 class="card-title h6">Product Name <?= $i ?></h5>
                        <p class="card-text fw-bold" style="color: var(--secondary);">$0.00</p>
                    </div>
                </div>
            </div>
        <?php endfor; ?>
    </div>
</section>

<section class="py-5 my-5" style="background-color: var(--primary); color: white;">
    <div class="container text-center">
        <h2 class="fw-bold mb-3">Partner with <?= htmlspecialchars($supplier['company_name']) ?></h2>
        <p class="lead mb-4">Experience the difference in quality and service with our dedicated team.</p>
        <a href="?supplier_id=<?= $supplier_id ?>&page=contact" class="btn btn-light btn-lg px-5 fw-bold" style="color: var(--primary);">Get a Quote</a>
    </div>
</section>