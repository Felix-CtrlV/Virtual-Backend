<?php
// --- PHP LOGIC: PRESERVED & CLEANED ---

// 1. Initialize & Fetch ID
$supplier_id = isset($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : 1;

// Fetch colors from shop_assets
$color_sql = "SELECT primary_color, secondary_color FROM shop_assets WHERE supplier_id = $supplier_id LIMIT 1";
$color_result = $conn->query($color_sql);
$primary_color = "#000000"; // Default
$secondary_color = "#ededed"; // Default

if ($color_result && $color_result->num_rows > 0) {
    $color_row = $color_result->fetch_assoc();
    $primary_color = $color_row['primary_color'];
    $secondary_color = $color_row['secondary_color'];
}

// 2. Handle Form Submission (Consolidated)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ensure database connection $conn exists
    $rating = (int)$_POST['rating'];
    $email  = trim($_POST['email']); // Note: You need an email input field in the HTML if you check for it
    // If you removed email input to rely on session, adjust logic. 
    // Assuming strictly based on your previous code which checked email:
    $review_text = trim($_POST['review_text']);

    // Validations
    if ($rating > 0 && !empty($review_text)) {
        // OPTIONAL: If you want to require email to find customer_id:
        // $email = $_POST['email']; 
        // ... (Your existing email lookup logic here) ...

        // FOR DEMO: Assuming we are using the logged-in customer or a default for the snippet
        // If you need the email lookup, uncomment your original logic. 
        // Here is a generic insert assuming we have a customer_id (e.g. from session) or we use the lookup.

        /* RESTORING YOUR EXACT LOGIC FOR EMAIL LOOKUP:
           Note: I added an Email input field to the HTML form below so this logic works.
        */
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
                    echo "<script>alert('Review submitted successfully!'); window.location.href='?supplier_id=$supplier_id&page=review';</script>";
                } else {
                    echo "<script>alert('Error submitting review.');</script>";
                }
            } else {
                echo "<script>alert('Email not found. Please register first.');</script>";
            }
        } else {
            echo "<script>alert('Email is required.');</script>";
        }
    } else {
        echo "<script>alert('Please select a star rating and write a review.');</script>";
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
    /* --- 1. THEME VARIABLES (Matching Home.php) --- */
    :root {
        --bg-color: <?= $secondary_color ?>;
        --card-bg: #141414;
        --card-border: #2a2a2a;
        --text-main: <?= $primary_color ?>;
        --text-muted: #888888;
        --accent: <?= $primary_color ?>;
        /* Primary Color as Accent */
        --font-display: 'Helvetica Neue', 'Arial Black', sans-serif;
        --font-body: 'Helvetica', sans-serif;
        --transition-smooth: cubic-bezier(0.16, 1, 0.3, 1);
    }

    body {
        background-color: var(--bg-color);
        color: var(--text-main);
        font-family: var(--font-body);
    }

    /* --- 2. LAYOUT & GRID --- */
    .page-wrapper {
        max-width: 1400px;
        margin: 0 auto;
        padding: 40px 20px;
    }

    .page-header {
        text-align: center;
        margin-bottom: 60px;
        padding-top: 40px;
    }

    .page-title {
        font-family: var(--font-display);
        font-size: clamp(3rem, 6vw, 5rem);
        font-weight: 900;
        text-transform: uppercase;
        margin: 0;
        line-height: 0.9;
        letter-spacing: -0.02em;
        color: white;
    }

    .page-subtitle {
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 2px;
        margin-top: 15px;
        font-size: 0.9rem;
    }

    /* BENTO LAYOUT */
    .bento-wrapper {
        display: grid;
        grid-template-columns: 350px 1fr;
        /* Sidebar (Form) Left or Right? Let's do Stats Top, Reviews Left, Form Right */
        gap: 30px;
    }

    /* Responsive adjustment */
    @media (max-width: 1024px) {
        .bento-wrapper {
            grid-template-columns: 1fr;
        }
    }

    /* --- 3. COMPONENTS --- */

    /* Generic Card */
    .bento-card {
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: 20px;
        padding: 30px;
        position: relative;
        overflow: hidden;
    }

    /* STATS HEADER (Full Width) */
    .stats-dashboard {
        grid-column: 1 / -1;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 40px;
        background: linear-gradient(145deg, #1a1a1a, #0f0f0f);
        margin-bottom: 30px;
    }

    .big-score-block {
        flex: 0 0 auto;
        text-align: center;
        padding-right: 40px;
        border-right: 1px solid #333;
    }

    .big-score {
        font-size: 5rem;
        font-weight: 800;
        color: white;
        line-height: 1;
        font-family: var(--font-display);
    }

    .total-count {
        color: var(--text-muted);
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 1px;
        margin-top: 10px;
    }

    .bars-block {
        flex: 1;
        min-width: 300px;
    }

    /* HUD Progress Bars */
    .hud-bar-row {
        display: flex;
        align-items: center;
        margin-bottom: 8px;
        font-size: 0.8rem;
        color: #666;
    }

    .hud-label {
        width: 30px;
        font-weight: bold;
        color: #fff;
    }

    .hud-track {
        flex: 1;
        height: 4px;
        background: #222;
        margin: 0 15px;
        position: relative;
    }

    .hud-fill {
        height: 100%;
        background: var(--accent);
        box-shadow: 0 0 10px rgba(212, 175, 55, 0.3);
        /* Gold Glow */
        transition: width 1s ease-out;
    }

    /* --- 4. REVIEWS FEED --- */
    .reviews-feed {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .review-card {
        background: #111;
        border: 1px solid #222;
        padding: 25px;
        border-radius: 16px;
        transition: transform 0.3s ease, border-color 0.3s ease;
    }

    .review-card:hover {
        transform: translateY(-3px);
        border-color: #444;
    }

    .reviewer-header {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
    }

    .reviewer-img {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
        margin-right: 15px;
        border: 2px solid #333;
    }

    .reviewer-info h4 {
        margin: 0;
        font-size: 1rem;
        font-weight: 700;
        color: #fff;
    }

    .reviewer-info span {
        font-size: 0.75rem;
        color: #666;
        text-transform: uppercase;
    }

    .review-stars {
        color: var(--accent);
        font-size: 0.9rem;
        margin-left: auto;
    }

    .review-body {
        color: #ccc;
        line-height: 1.6;
        font-size: 0.95rem;
    }

    /* --- 5. STICKY FORM PANEL --- */
    .form-sticky-panel {
        position: sticky;
        top: 20px;
        height: fit-content;
        background: #161616;
        border: 1px solid #333;
    }

    .form-header {
        font-family: var(--font-display);
        font-size: 1.5rem;
        text-transform: uppercase;
        margin-bottom: 25px;
        border-bottom: 1px solid #333;
        padding-bottom: 15px;
        color: white;
    }

    .futuristic-input {
        width: 100%;
        background: #0a0a0a;
        border: 1px solid #333;
        color: #fff;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-family: var(--font-body);
        transition: border-color 0.3s;
    }

    .futuristic-input:focus {
        outline: none;
        border-color: var(--accent);
    }

    .submit-btn {
        width: 100%;
        padding: 18px;
        background: var(--accent);
        color: #000;
        font-weight: 900;
        text-transform: uppercase;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s;
        letter-spacing: 1px;
    }

    .submit-btn:hover {
        background: #fff;
        box-shadow: 0 0 20px rgba(255, 255, 255, 0.2);
    }

    /* Star Select (Radio Logic) */
    .star-select-container {
        display: flex;
        flex-direction: row-reverse;
        /* CSS Magic for hover logic */
        justify-content: center;
        gap: 10px;
        margin-bottom: 25px;
        padding: 10px 0;
    }

    .star-select-container input {
        display: none;
    }

    .star-select-container label svg {
        width: 30px;
        height: 30px;
        fill: #333;
        /* Default Empty */
        transition: fill 0.2s, transform 0.2s;
        cursor: pointer;
    }

    /* Hover & Checked Logic */
    .star-select-container label:hover svg,
    .star-select-container label:hover~label svg,
    .star-select-container input:checked~label svg {
        fill: var(--accent);
        filter: drop-shadow(0 0 5px rgba(212, 175, 55, 0.6));
    }

    .star-select-container label:hover {
        transform: scale(1.2);
    }

    /* Animations */
    .reveal-on-scroll {
        opacity: 0;
        transform: translateY(30px);
        transition: all 0.8s var(--transition-smooth);
    }

    .reveal-on-scroll.is-visible {
        opacity: 1;
        transform: translateY(0);
    }
</style>

<div class="page-wrapper">

    <header class="page-header reveal-on-scroll">
        <h1 class="page-title">Legacy Verified</h1>
        <p class="page-subtitle">Community Feedback & Performance metrics</p>
    </header>

    <div class="bento-card stats-dashboard reveal-on-scroll">
        <div class="big-score-block">
            <div class="big-score"><?= $avg_rating ?></div>
            <div class="total-count"><?= $total_reviews ?> Reviews</div>
        </div>

        <div class="bars-block">
            <?php
            $labels = [5 => '5.0', 4 => '4.0', 3 => '3.0', 2 => '2.0', 1 => '1.0'];
            foreach ($labels as $star => $label):
                $count = $star_counts[$star];
                $percent = $total_reviews > 0 ? ($count / $total_reviews) * 100 : 0;
            ?>
                <div class="hud-bar-row">
                    <span class="hud-label"><?= $star ?>★</span>
                    <div class="hud-track">
                        <div class="hud-fill" style="width: <?= $percent ?>%;"></div>
                    </div>
                    <span style="width:30px; text-align:right;"><?= $count ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="bento-wrapper">

        <div class="reviews-feed">
            <h3 style="margin-bottom:20px; font-family:var(--font-display); text-transform:uppercase;">Latest Intel</h3>

            <?php if ($reviews_res->num_rows > 0): ?>
                <?php while ($row = $reviews_res->fetch_assoc()): ?>
                    <div class="review-card reveal-on-scroll">
                        <div class="reviewer-header">
                            <img src="<?= $row['image'] ? '../assets/customer_profiles/' . $row['image'] : 'https://cdn-icons-png.flaticon.com/512/149/149071.png' ?>" class="reviewer-img">
                            <div class="reviewer-info">
                                <h4><?= htmlspecialchars($row['name']) ?></h4>
                                <span><?= date('d M Y', strtotime($row['created_at'])) ?></span>
                            </div>
                            <div class="review-stars">
                                <?php for ($i = 0; $i < $row['rating']; $i++) echo '★'; ?>
                            </div>
                        </div>
                        <p class="review-body">"<?= htmlspecialchars($row['review']) ?>"</p>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="review-card" style="text-align:center; color:#666;">
                    No reviews yet. Be the first to break the silence.
                </div>
            <?php endif; ?>
        </div>

        <div class="bento-card form-sticky-panel reveal-on-scroll">
            <div class="form-header">Leave Your Mark</div>

            <form method="POST" action="">
                <div class="star-select-container">
                    <input type="radio" name="rating" id="star-5" value="5">
                    <label for="star-5" title="5 Stars">
                        <svg viewBox="0 0 576 512">
                            <path d="M259.3 17.8L194 150.2 47.9 171.5c-26.2 3.8-36.7 36.1-17.7 54.6l105.7 103-25 145.5c-4.5 26.3 23.2 46 46.4 33.7L288 439.6l130.7 68.7c23.2 12.2 50.9-7.4 46.4-33.7l-25-145.5 105.7-103c19-18.5 8.5-50.8-17.7-54.6L382 150.2 316.7 17.8c-11.7-23.6-45.6-23.9-57.4 0z" />
                        </svg>
                    </label>

                    <input type="radio" name="rating" id="star-4" value="4">
                    <label for="star-4" title="4 Stars">
                        <svg viewBox="0 0 576 512">
                            <path d="M259.3 17.8L194 150.2 47.9 171.5c-26.2 3.8-36.7 36.1-17.7 54.6l105.7 103-25 145.5c-4.5 26.3 23.2 46 46.4 33.7L288 439.6l130.7 68.7c23.2 12.2 50.9-7.4 46.4-33.7l-25-145.5 105.7-103c19-18.5 8.5-50.8-17.7-54.6L382 150.2 316.7 17.8c-11.7-23.6-45.6-23.9-57.4 0z" />
                        </svg>
                    </label>

                    <input type="radio" name="rating" id="star-3" value="3">
                    <label for="star-3" title="3 Stars">
                        <svg viewBox="0 0 576 512">
                            <path d="M259.3 17.8L194 150.2 47.9 171.5c-26.2 3.8-36.7 36.1-17.7 54.6l105.7 103-25 145.5c-4.5 26.3 23.2 46 46.4 33.7L288 439.6l130.7 68.7c23.2 12.2 50.9-7.4 46.4-33.7l-25-145.5 105.7-103c19-18.5 8.5-50.8-17.7-54.6L382 150.2 316.7 17.8c-11.7-23.6-45.6-23.9-57.4 0z" />
                        </svg>
                    </label>

                    <input type="radio" name="rating" id="star-2" value="2">
                    <label for="star-2" title="2 Stars">
                        <svg viewBox="0 0 576 512">
                            <path d="M259.3 17.8L194 150.2 47.9 171.5c-26.2 3.8-36.7 36.1-17.7 54.6l105.7 103-25 145.5c-4.5 26.3 23.2 46 46.4 33.7L288 439.6l130.7 68.7c23.2 12.2 50.9-7.4 46.4-33.7l-25-145.5 105.7-103c19-18.5 8.5-50.8-17.7-54.6L382 150.2 316.7 17.8c-11.7-23.6-45.6-23.9-57.4 0z" />
                        </svg>
                    </label>

                    <input type="radio" name="rating" id="star-1" value="1">
                    <label for="star-1" title="1 Star">
                        <svg viewBox="0 0 576 512">
                            <path d="M259.3 17.8L194 150.2 47.9 171.5c-26.2 3.8-36.7 36.1-17.7 54.6l105.7 103-25 145.5c-4.5 26.3 23.2 46 46.4 33.7L288 439.6l130.7 68.7c23.2 12.2 50.9-7.4 46.4-33.7l-25-145.5 105.7-103c19-18.5 8.5-50.8-17.7-54.6L382 150.2 316.7 17.8c-11.7-23.6-45.6-23.9-57.4 0z" />
                        </svg>
                    </label>
                </div>

                <input type="email" name="email" class="futuristic-input" placeholder="Your Email (for verification)" required>
                <textarea name="review_text" rows="5" class="futuristic-input" placeholder="How was the performance?" required></textarea>

                <button type="submit" class="submit-btn">Publish Review</button>
            </form>
        </div>

    </div>
</div>

<script>
    // Reveal on Scroll Animation Logic
    document.addEventListener("DOMContentLoaded", function() {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: "0px 0px -50px 0px"
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target); // Only animate once
                }
            });
        }, observerOptions);

        const elements = document.querySelectorAll('.reveal-on-scroll');
        elements.forEach(el => observer.observe(el));
    });
</script>