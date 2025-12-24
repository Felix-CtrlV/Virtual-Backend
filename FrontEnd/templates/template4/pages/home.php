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

<section class="page-description-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <span class="page-desc-label">Why Choose Us</span>
                <h2 class="page-desc-title">
                    Trusted Solutions from <?= htmlspecialchars($supplier['company_name']) ?>
                </h2>
                <p class="page-desc-text">
                    We help businesses grow by delivering reliable products, transparent pricing,
                    and consistent support. Our focus is long-term partnerships built on trust,
                    performance, and quality.
                </p>
            </div>
        </div>
    </div>
</section>






<section class="py-5 my-5" style="background-color: var(--primary); color: white;">
    <div class="container text-center">
        <h2 class="fw-bold mb-3">Partner with <?= htmlspecialchars($supplier['company_name']) ?></h2>
        <p class="lead mb-4">Experience the difference in quality and service with our dedicated team.</p>
        <a href="?supplier_id=<?= $supplier_id ?>&page=contact" class="btn btn-light btn-lg px-5 fw-bold" style="color: var(--primary);">Get a Quote</a>
    </div>
</section>