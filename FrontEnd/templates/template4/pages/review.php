<?php
// --- PHP LOGIC: PRESERVED (No Changes to Logic) ---

// 1. Initialize & Fetch ID
$supplier_id = isset($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : 1;

// Fetch colors from shop_assets (Keeping logic, though we enforce White/Black in CSS now)
$color_sql = "SELECT primary_color, secondary_color FROM shop_assets WHERE supplier_id = $supplier_id LIMIT 1";
$color_result = $conn->query($color_sql);
$primary_color = "#FFFFFF"; // Force White for this design
$secondary_color = "#000000"; // Force Black for this design

if ($color_result && $color_result->num_rows > 0) {
    // We fetch them to avoid errors, but the CSS below overrides for the "Famous" look
    $color_row = $color_result->fetch_assoc();
}

// 2. Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $rating = (int)$_POST['rating'];
    $email  = trim($_POST['email']);
    $review_text = trim($_POST['review_text']);

    if ($rating > 0 && !empty($review_text)) {
        if (!empty($email)) {
            $cust_stmt = $conn->prepare("SELECT customer_id FROM customers WHERE email = ?");
            $cust_stmt->bind_param("s", $email);
            $cust_stmt->execute();
            $res = $cust_stmt->get_result();

            if ($res->num_rows > 0) {
                $customer = $res->fetch_assoc();
                $cid = $customer['customer_id'];

                $stmt = $conn->prepare("INSERT INTO reviews (supplier_id, customer_id, review, rating, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->bind_param("iisi", $supplier_id, $cid, $review_text, $rating);

                if ($stmt->execute()) {
                    echo "<script>alert('Review published.'); window.location.href='?supplier_id=$supplier_id&page=review';</script>";
                } else {
                    echo "<script>alert('System error.');</script>";
                }
            } else {
                echo "<script>alert('Email not associated with an account.');</script>";
            }
        } else {
            echo "<script>alert('Email required.');</script>";
        }
    } else {
        echo "<script>alert('Rating and review text required.');</script>";
    }
}

// 3. Fetch Stats
$sql_stats = "SELECT rating FROM reviews WHERE supplier_id = $supplier_id";
$result_stats = $conn->query($sql_stats);

$total_reviews = 0;
$sum_ratings = 0;
$star_counts = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];

if ($result_stats && $result_stats->num_rows > 0) {
    while ($row = $result_stats->fetch_assoc()) {
        $r = (int)$row['rating'];
        if ($r >= 1 && $r <= 5) {
            $star_counts[$r]++;
            $sum_ratings += $r;
            $total_reviews++;
        }
    }
}
$avg_rating = $total_reviews > 0 ? number_format($sum_ratings / $total_reviews, 1) : "0.0";

// 4. Fetch Recent Reviews
$sql_reviews = "
    SELECT r.*, c.name, c.image 
    FROM reviews r 
    JOIN customers c ON r.customer_id = c.customer_id 
    WHERE r.supplier_id = $supplier_id 
    ORDER BY r.created_at DESC LIMIT 10";
$reviews_res = $conn->query($sql_reviews);
?>

<style>
    /* --- FAMOUS / HIGH-END THEME --- */
    :root {
        --bg-color: #050505; /* Deep Black */
        --card-bg: #111111;
        --text-color: #FFFFFF;
        --text-muted: #888888;
        --border-color: #333333;
        --accent-color: #FFFFFF; /* Monochrome Accent */
        
        --font-main: 'Helvetica Neue', Helvetica, Arial, sans-serif;
    }

    * {
        box-sizing: border-box;
    }

    body {
        background-color: var(--bg-color);
        color: var(--text-color);
        font-family: var(--font-main);
        margin: 0;
        -webkit-font-smoothing: antialiased;
    }

    /* --- LAYOUT --- */
    .page-wrapper {
        max-width: 1200px;
        margin: 0 auto;
        padding: 60px 20px;
    }

    .page-header {
        margin-bottom: 60px;
        text-align: left;
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 40px;
    }

    .page-title {
        font-size: 4rem;
        font-weight: 800;
        text-transform: uppercase;
        margin: 0;
        line-height: 0.9;
        letter-spacing: -2px;
        color: white;
    }

    .page-subtitle {
        font-size: 1rem;
        color: var(--text-muted);
        margin-top: 15px;
        font-weight: 400;
        letter-spacing: 1px;
        text-transform: uppercase;
    }

    /* STATS HEADER */
    .stats-dashboard {
        display: flex;
        align-items: center;
        gap: 60px;
        margin-bottom: 50px;
        background: var(--card-bg);
        padding: 40px;
        border: 1px solid var(--border-color);
        /* Minimalist sharp corners */
    }

    .big-score-block {
        text-align: left;
    }

    .big-score {
        font-size: 6rem;
        font-weight: 700;
        line-height: 1;
        letter-spacing: -3px;
    }

    .total-count {
        font-size: 0.8rem;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 2px;
        margin-top: 10px;
    }

    .bars-block {
        flex: 1;
    }

    .hud-bar-row {
        display: flex;
        align-items: center;
        margin-bottom: 8px;
        font-size: 0.75rem;
        color: var(--text-muted);
    }

    .hud-label {
        width: 30px;
        color: #fff;
        font-weight: 600;
    }

    .hud-track {
        flex: 1;
        height: 2px; /* Super thin sleek lines */
        background: #333;
        margin: 0 20px;
        position: relative;
    }

    .hud-fill {
        height: 100%;
        background: #fff; /* White bars */
        transition: width 1s ease-out;
    }

    /* --- BENTO GRID LAYOUT --- */
    .bento-wrapper {
        display: grid;
        /* Adjusted: Reviews (Main) gets 1fr, Form (Sidebar) gets 400px */
        grid-template-columns: 1fr 400px; 
        gap: 40px;
        align-items: start;
    }

    @media (max-width: 900px) {
        .bento-wrapper {
            grid-template-columns: 1fr;
        }
    }

    /* --- REVIEWS FEED --- */
    .section-title {
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 2px;
        color: var(--text-muted);
        margin-bottom: 30px;
        border-bottom: 1px solid #333;
        padding-bottom: 10px;
        display: inline-block;
    }

    .review-card {
        padding: 30px 0;
        border-bottom: 1px solid #222;
        transition: all 0.3s;
    }

    .review-card:first-child {
        padding-top: 0;
    }

    .reviewer-header {
        display: flex;
        align-items: center;
        margin-bottom: 20px;
    }

    .reviewer-img {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        object-fit: cover;
        margin-right: 15px;
        filter: grayscale(100%); /* High fashion b&w look */
        transition: filter 0.3s;
    }

    .review-card:hover .reviewer-img {
        filter: grayscale(0%);
    }

    .reviewer-info h4 {
        margin: 0;
        font-size: 1rem;
        font-weight: 700;
        color: white;
        letter-spacing: -0.5px;
    }

    .reviewer-info span {
        font-size: 0.75rem;
        color: #666;
    }

    .review-stars {
        margin-left: auto;
        color: #fff;
        font-size: 0.8rem;
        letter-spacing: 2px;
    }

    .review-body {
        font-size: 1.1rem;
        line-height: 1.6;
        color: #ddd;
        font-weight: 300;
    }

    /* --- STICKY FORM --- */
    .form-sticky-panel {
        position: sticky;
        top: 20px;
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        padding: 40px;
    }

    .form-header {
        font-size: 1.5rem;
        font-weight: 800;
        text-transform: uppercase;
        margin-bottom: 30px;
        letter-spacing: -1px;
    }

    /* Star Selector */
    .star-select-container {
        display: flex;
        flex-direction: row-reverse;
        justify-content: flex-end; /* Align left */
        gap: 5px;
        margin-bottom: 40px;
    }
    
    .star-select-container input { display: none; }
    
    .star-select-container label svg {
        width: 24px;
        height: 24px;
        fill: #333; /* Empty state */
        cursor: pointer;
        transition: fill 0.2s;
    }

    /* White Stars on Hover/Active */
    .star-select-container label:hover svg,
    .star-select-container label:hover ~ label svg,
    .star-select-container input:checked ~ label svg {
        fill: #FFF; 
    }

    /* Input Fields - Sleek Underline Style */
    .futuristic-input {
        width: 100%;
        background: transparent;
        border: none;
        border-bottom: 1px solid #444;
        color: #fff;
        padding: 15px 0;
        font-size: 1rem;
        font-family: var(--font-main);
        margin-bottom: 30px;
        transition: border-color 0.3s;
        border-radius: 0;
    }

    .futuristic-input:focus {
        outline: none;
        border-bottom: 1px solid #fff;
    }

    .futuristic-input::placeholder {
        color: #555;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 1px;
    }

    /* Button - High Contrast */
    .submit-btn {
        width: 100%;
        padding: 20px;
        background: #FFFFFF;
        color: #000000;
        font-weight: 800;
        text-transform: uppercase;
        border: none;
        cursor: pointer;
        transition: all 0.3s;
        letter-spacing: 1px;
        font-size: 0.9rem;
        margin-top: 10px;
    }

    .submit-btn:hover {
        background: #ddd;
        transform: translateY(-2px);
    }
    
    /* Reveal Animation */
    .reveal-on-scroll {
        opacity: 0;
        transform: translateY(20px);
        transition: all 0.8s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .reveal-on-scroll.is-visible {
        opacity: 1;
        transform: translateY(0);
    }

</style>

<div class="page-wrapper">

    <header class="page-header reveal-on-scroll">
        <h1 class="page-title">Verified Reviews</h1>
        <p class="page-subtitle">Authentic Feedback & Metrics</p>
    </header>

    <div class="stats-dashboard reveal-on-scroll">
        <div class="big-score-block">
            <div class="big-score"><?= $avg_rating ?></div>
            <div class="total-count"><?= $total_reviews ?> Verified Reviews</div>
        </div>

        <div class="bars-block">
            <?php
            $labels = [5 => '5.0', 4 => '4.0', 3 => '3.0', 2 => '2.0', 1 => '1.0'];
            foreach ($labels as $star => $label):
                $count = $star_counts[$star];
                $percent = $total_reviews > 0 ? ($count / $total_reviews) * 100 : 0;
            ?>
                <div class="hud-bar-row">
                    <span class="hud-label"><?= $star ?></span>
                    <div class="hud-track">
                        <div class="hud-fill" style="width: <?= $percent ?>%;"></div>
                    </div>
                    <span style="width:30px; text-align:right; color:#666;"><?= $count ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="bento-wrapper">

        <div class="reviews-feed">
            <div class="section-title reveal-on-scroll">Recent Opinions</div>

            <?php if ($reviews_res->num_rows > 0): ?>
                <?php while ($row = $reviews_res->fetch_assoc()): ?>
                    <div class="review-card reveal-on-scroll">
                        <div class="reviewer-header">
                            <img src="<?= $row['image'] ? '../assets/customer_profiles/' . $row['image'] : 'https://cdn-icons-png.flaticon.com/512/149/149071.png' ?>" class="reviewer-img">
                            <div class="reviewer-info">
                                <h4><?= htmlspecialchars($row['name']) ?></h4>
                                <span><?= date('M d, Y', strtotime($row['created_at'])) ?></span>
                            </div>
                            <div class="review-stars">
                                <?php for ($i = 0; $i < $row['rating']; $i++) echo '★'; ?>
                            </div>
                        </div>
                        <p class="review-body"><?= htmlspecialchars($row['review']) ?></p>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="review-card" style="padding:40px 0; color:#555;">
                    No reviews yet. Initiation required.
                </div>
            <?php endif; ?>
        </div>

        <div class="form-sticky-panel reveal-on-scroll">
            <div class="form-header">Write a Review</div>

            <form method="POST" action="">
                
                <div class="star-select-container">
                    <?php for($s=5; $s>=1; $s--): ?>
                    <input type="radio" name="rating" id="star-<?=$s?>" value="<?=$s?>">
                    <label for="star-<?=$s?>">
                        <svg viewBox="0 0 576 512">
                            <path d="M259.3 17.8L194 150.2 47.9 171.5c-26.2 3.8-36.7 36.1-17.7 54.6l105.7 103-25 145.5c-4.5 26.3 23.2 46 46.4 33.7L288 439.6l130.7 68.7c23.2 12.2 50.9-7.4 46.4-33.7l-25-145.5 105.7-103c19-18.5 8.5-50.8-17.7-54.6L382 150.2 316.7 17.8c-11.7-23.6-45.6-23.9-57.4 0z" />
                        </svg>
                    </label>
                    <?php endfor; ?>
                </div>

                <input type="email" name="email" class="futuristic-input" placeholder="Email Address" required>
                <textarea name="review_text" rows="4" class="futuristic-input" placeholder="Your Experience..." style="resize:none;" required></textarea>

                <button type="submit" class="submit-btn">Publish</button>
            </form>
        </div>

    </div>
</div>

<script>
    // Intersection Observer for Scroll Animations
    document.addEventListener("DOMContentLoaded", function() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target); 
                }
            });
        }, { threshold: 0.05, rootMargin: "0px 0px -50px 0px" });

        document.querySelectorAll('.reveal-on-scroll').forEach(el => observer.observe(el));
    });
</script>