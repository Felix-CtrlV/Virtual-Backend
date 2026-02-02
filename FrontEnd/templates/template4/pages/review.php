<?php
// --- PHP LOGIC: PRESERVED (No Changes to Logic) ---

// 1. Initialize & Fetch ID
$supplier_id = isset($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : 1;

// Fetch colors from shop_assets (Keeping logic, though we enforce White/Black in CSS now)
$color_sql = "SELECT primary_color, secondary_color FROM shop_assets WHERE company_id = $company_id LIMIT 1";
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
        // if (!empty($email)) {
        //     $cust_stmt = $conn->prepare("SELECT customer_id FROM customers WHERE email = ?");
        //     $cust_stmt->bind_param("s", $email);
        //     $cust_stmt->execute();
        //     $res = $cust_stmt->get_result();

        //     if ($res->num_rows > 0) {
        //         $customer = $res->fetch_assoc();
                $cid = $_SESSION['customer_id']; 

                $stmt = $conn->prepare("INSERT INTO reviews (company_id, customer_id, review, rating, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->bind_param("iisi", $company_id, $cid, $review_text, $rating);

                if ($stmt->execute()) {
                    echo "<script>alert('Review published.'); window.location.href='?supplier_id=$supplier_id&page=review';</script>";
                } else {
                    echo "<script>alert('System error.');</script>";
                }
            
        } else {
            echo "<script>alert('Email required.');</script>";
        }
    } else {
        echo "<script>alert('Rating and review text required.');</script>";
    }


// 3. Fetch Stats
$sql_stats = "SELECT rating FROM reviews WHERE company_id = $company_id";
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
    WHERE r.company_id = $company_id 
    ORDER BY r.created_at DESC LIMIT 10";
$reviews_res = $conn->query($sql_reviews);
?>

<style>
    /* --- MODERN HIGH-END THEME --- */
    :root {
        --bg-color: #0A0A0A;
        --card-bg: #141414;
        --text-color: #FFFFFF;
        --text-muted: #AAAAAA;
        --border-color: #2A2A2A;
        --accent-color: #FFFFFF;
        --star-color: #FFD700;
        --font-main: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        --font-heading: 'Montserrat', sans-serif;
        --container-padding: clamp(20px, 5vw, 60px);
        --section-gap: clamp(30px, 6vw, 60px);
        --transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    body {
        background: linear-gradient(135deg, var(--bg-color) 0%, #1A1A1A 100%);
        color: var(--text-color);
        font-family: var(--font-main);
        line-height: 1.6;
        min-height: 100vh;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }

    /* --- LAYOUT --- */
    .page-wrapper {
        max-width: 1400px;
        margin: 0 auto;
        padding: var(--container-padding);
        padding-top: 40px;
        animation: fadeIn 0.8s ease-out;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* --- HEADER --- */
    .page-header {
        margin-bottom: var(--section-gap);
        padding-bottom: 30px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        position: relative;
        overflow: hidden;
    }

    .page-header::after {
        content: '';
        position: absolute;
        bottom: -1px;
        left: 0;
        width: 100px;
        height: 2px;
        background: linear-gradient(90deg, #fff, transparent);
    }

    .page-title {
        font-family: var(--font-heading);
        font-size: clamp(2.5rem, 8vw, 4.5rem);
        font-weight: 800;
        background: linear-gradient(135deg, #fff 0%, #aaa 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: 10px;
        line-height: 1.1;
        letter-spacing: -0.02em;
    }

    .page-subtitle {
        font-size: clamp(0.9rem, 2vw, 1.1rem);
        color: var(--text-muted);
        font-weight: 400;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .page-subtitle::before {
        content: '';
        width: 40px;
        height: 1px;
        background: var(--text-muted);
    }

    /* --- STATS DASHBOARD --- */
    .stats-dashboard {
        background: rgba(20, 20, 20, 0.7);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 20px;
        padding: clamp(25px, 4vw, 50px);
        margin-bottom: var(--section-gap);
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: clamp(30px, 5vw, 60px);
        align-items: center;
        transition: var(--transition);
    }

    .stats-dashboard:hover {
        border-color: rgba(255, 255, 255, 0.2);
        transform: translateY(-2px);
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    }

    .big-score-block {
        text-align: center;
        padding: 20px;
        border-right: 1px solid rgba(255, 255, 255, 0.1);
    }

    .big-score {
        font-family: var(--font-heading);
        font-size: clamp(4rem, 10vw, 6rem);
        font-weight: 800;
        line-height: 1;
        background: linear-gradient(135deg, #fff 0%, #ccc 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: 10px;
    }

    .total-count {
        font-size: clamp(0.8rem, 1.5vw, 0.9rem);
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.15em;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .total-count::before {
        content: '✓';
        color: #4CAF50;
        font-weight: bold;
    }

    .bars-block {
        padding: 10px 0;
    }

    .hud-bar-row {
        display: flex;
        align-items: center;
        margin-bottom: clamp(12px, 2vw, 15px);
        gap: 15px;
    }

    .hud-label {
        width: 40px;
        font-size: 0.9rem;
        font-weight: 600;
        color: #fff;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .hud-label::after {
        content: '★';
        color: var(--star-color);
    }

    .hud-track {
        flex: 1;
        height: 6px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 3px;
        overflow: hidden;
        position: relative;
    }

    .hud-fill {
        height: 100%;
        background: linear-gradient(90deg, #fff, #ddd);
        border-radius: 3px;
        transition: width 1.5s cubic-bezier(0.4, 0, 0.2, 1);
        min-width: 3px;
    }

    .hud-count {
        width: 40px;
        text-align: right;
        font-size: 0.9rem;
        color: var(--text-muted);
        font-weight: 500;
    }

    /* --- BENTO GRID LAYOUT --- */
    .bento-wrapper {
        display: grid;
        grid-template-columns: 1fr minmax(350px, 400px);
        gap: clamp(30px, 4vw, 50px);
        align-items: start;
    }

    /* --- REVIEWS FEED --- */
    .reviews-feed {
        position: relative;
    }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 40px;
        padding-bottom: 20px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .section-title {
        font-family: var(--font-heading);
        font-size: clamp(1.2rem, 3vw, 1.5rem);
        font-weight: 700;
        color: #fff;
        position: relative;
        padding-left: 20px;
    }

    .section-title::before {
        content: '';
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 4px;
        height: 24px;
        background: linear-gradient(180deg, #fff, #aaa);
        border-radius: 2px;
    }

    .review-count {
        font-size: 0.9rem;
        color: var(--text-muted);
        padding: 6px 15px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 20px;
    }

    .review-card {
        background: rgba(20, 20, 20, 0.6);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 16px;
        padding: clamp(25px, 3vw, 35px);
        margin-bottom: 25px;
        transition: var(--transition);
        position: relative;
        overflow: hidden;
    }

    .review-card:hover {
        border-color: rgba(255, 255, 255, 0.15);
        transform: translateY(-4px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
    }

    .review-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
    }

    .reviewer-header {
        display: flex;
        align-items: center;
        margin-bottom: 20px;
        gap: 15px;
    }

    .reviewer-avatar {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid rgba(255, 255, 255, 0.1);
        transition: var(--transition);
    }

    .review-card:hover .reviewer-avatar {
        border-color: rgba(255, 255, 255, 0.3);
        transform: scale(1.05);
    }

    .reviewer-info {
        flex: 1;
    }

    .reviewer-name {
        font-family: var(--font-heading);
        font-size: 1.1rem;
        font-weight: 600;
        color: #fff;
        margin-bottom: 4px;
    }

    .review-date {
        font-size: 0.85rem;
        color: var(--text-muted);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .review-date::before {
        content: '🗓️';
        font-size: 0.8rem;
    }

    .review-stars {
        display: flex;
        gap: 2px;
    }

    .review-stars span {
        color: var(--star-color);
        font-size: 1.1rem;
        filter: drop-shadow(0 0 8px rgba(255, 215, 0, 0.3));
    }

    .review-body {
        font-size: clamp(1rem, 1.5vw, 1.1rem);
        line-height: 1.7;
        color: #ddd;
        margin-top: 15px;
        padding-left: 5px;
        position: relative;
    }

    .review-body::before {
        content: '❝';
        position: absolute;
        left: -10px;
        top: -10px;
        font-size: 2rem;
        color: rgba(255, 255, 255, 0.1);
        font-family: serif;
    }

    /* --- FORM PANEL --- */
    .form-sticky-panel {
        position: sticky;
        top: 30px;
        background: rgba(20, 20, 20, 0.8);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 20px;
        padding: clamp(25px, 3vw, 40px);
        transition: var(--transition);
    }

    .form-sticky-panel:hover {
        border-color: rgba(255, 255, 255, 0.2);
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
    }

    .form-header {
        font-family: var(--font-heading);
        font-size: clamp(1.3rem, 3vw, 1.6rem);
        font-weight: 700;
        margin-bottom: 30px;
        color: #fff;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .form-header::before {
        content: '✍️';
        font-size: 1.3rem;
    }

    /* ORIGINAL STAR BUTTON STYLES (from before changes) */
    .star-select-container {
        display: flex;
        flex-direction: row-reverse;
        justify-content: center; /* Align left */
        gap: 22px;
        margin-bottom: 40px;
    }
    
    .star-select-container input { display: none; }
    
    .star-select-container label svg {
        width: 34px;
        height: 34px;
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

    /* Input Fields - Sleek Underline Style (Original) */
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

    /* Button - High Contrast (Original) */
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

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: var(--text-muted);
        font-size: 1.1rem;
        background: rgba(20, 20, 20, 0.5);
        border: 2px dashed rgba(255, 255, 255, 0.1);
        border-radius: 20px;
        margin-top: 20px;
    }

    .empty-state::before {
        content: '💬';
        font-size: 3rem;
        display: block;
        margin-bottom: 20px;
        opacity: 0.5;
    }

    /* --- RESPONSIVE DESIGN --- */
    @media (max-width: 1024px) {
        .stats-dashboard {
            grid-template-columns: 1fr;
            gap: 30px;
        }
        
        .big-score-block {
            border-right: none;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 30px;
            padding-right: 0;
        }
    }

    @media (max-width: 900px) {
        .bento-wrapper {
            grid-template-columns: 1fr;
        }
        
        .form-sticky-panel {
            position: static;
            margin-top: 40px;
        }
        
        .page-wrapper {
            padding: 20px;
        }
    }

    @media (max-width: 768px) {
        .section-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
        }
        
        .reviewer-header {
            flex-wrap: wrap;
        }
        
        .review-stars {
            width: 100%;
            justify-content: center;
            margin-top: 10px;
        }
    }

    @media (max-width: 480px) {
        .review-card {
            padding: 20px;
        }
        
        .reviewer-avatar {
            width: 48px;
            height: 48px;
        }
        
        .star-select-container label svg {
            width: 24px;
            height: 24px;
        }
        
        .futuristic-input {
            padding: 14px 0;
        }
        
        .submit-btn {
            padding: 16px;
        }
    }

    /* --- ANIMATIONS --- */
    .reveal-on-scroll {
        opacity: 0;
        transform: translateY(30px);
        transition: all 0.8s cubic-bezier(0.16, 1, 0.3, 1);
    }
    
    .reveal-on-scroll.is-visible {
        opacity: 1;
        transform: translateY(0);
    }

    /* --- LOADING ANIMATION FOR BARS --- */
    @keyframes slideIn {
        from { width: 0; }
    }

    .hud-fill {
        animation: slideIn 1.5s cubic-bezier(0.4, 0, 0.2, 1) forwards;
    }

    /* --- SCROLLBAR --- */
    ::-webkit-scrollbar {
        width: 8px;
    }
    
    ::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.05);
    }
    
    ::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 4px;
    }
    
    ::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.3);
    }
</style>

<div class="page-wrapper">
    <header class="page-header reveal-on-scroll">
        <h1 class="page-title">Customer Reviews</h1>
        <p class="page-subtitle">Authentic Feedback & Community Insights</p>
    </header>

    <div class="stats-dashboard reveal-on-scroll">
        <div class="big-score-block">
            <div class="big-score"><?= $avg_rating ?></div>
            <div class="total-count"><?= $total_reviews ?> Verified Reviews</div>
        </div>

        <div class="bars-block">
            <?php
            $labels = [5 => '5', 4 => '4', 3 => '3', 2 => '2', 1 => '1'];
            foreach ($labels as $star => $label):
                $count = $star_counts[$star];
                $percent = $total_reviews > 0 ? ($count / $total_reviews) * 100 : 0;
            ?>
                <div class="hud-bar-row">
                    <span class="hud-label"><?= $label ?></span>
                    <div class="hud-track">
                        <div class="hud-fill" style="width: <?= $percent ?>%;"></div>
                    </div>
                    <span class="hud-count"><?= $count ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="bento-wrapper">
        <div class="reviews-feed">
            <div class="section-header">
                <h2 class="section-title">Recent Reviews</h2>
                <span class="review-count"><?= $total_reviews ?> Total</span>
            </div>

            <?php if ($reviews_res->num_rows > 0): ?>
                <?php while ($row = $reviews_res->fetch_assoc()): ?>
                    <div class="review-card reveal-on-scroll">
                        <div class="reviewer-header">
                            <img src="<?= $row['image'] ? '../assets/customer_profiles/' . $row['image'] : 'https://cdn-icons-png.flaticon.com/512/149/149071.png' ?>" 
                                 class="reviewer-avatar" 
                                 alt="<?= htmlspecialchars($row['name']) ?>">
                            <div class="reviewer-info">
                                <h3 class="reviewer-name"><?= htmlspecialchars($row['name']) ?></h3>
                                <span class="review-date"><?= date('F j, Y', strtotime($row['created_at'])) ?></span>
                            </div>
                            <div class="review-stars">
                                <?php for ($i = 0; $i < 5; $i++): ?>
                                    <span><?= $i < $row['rating'] ? '★' : '☆' ?></span>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <p class="review-body"><?= htmlspecialchars($row['review']) ?></p>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state reveal-on-scroll">
                    Be the first to share your experience
                </div>
            <?php endif; ?>
        </div>

        <div class="form-sticky-panel reveal-on-scroll">
            <h2 class="form-header">Write a Review</h2>

            <form method="POST" action="">
                
                <!-- ORIGINAL STAR BUTTON (reverted to original style) -->
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

                <!-- ORIGINAL INPUT STYLES -->
                <input type="email" name="email" class="futuristic-input" placeholder="Email Address" required>
                <textarea name="review_text" rows="4" class="futuristic-input" placeholder="Your Experience..." style="resize:none;" required></textarea>

                <!-- ORIGINAL BUTTON STYLE -->
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
                }
            });
        }, { 
            threshold: 0.05,
            rootMargin: "0px 0px -50px 0px"
        });

        document.querySelectorAll('.reveal-on-scroll').forEach(el => observer.observe(el));

        // Star rating interaction (original behavior)
        const starInputs = document.querySelectorAll('.star-select-container input');
        starInputs.forEach(input => {
            input.addEventListener('change', function() {
                // Optional: Add any custom behavior here
                // The original star button CSS handles the visual feedback
            });
        });

        // Form submission feedback
        const form = document.querySelector('form');
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('.submit-btn');
            submitBtn.innerHTML = 'Publishing...';
            submitBtn.disabled = true;
            
            // Re-enable after 3 seconds if still on page (for demo)
            setTimeout(() => {
                submitBtn.innerHTML = 'Publish';
                submitBtn.disabled = false;
            }, 3000);
        });

        // Handle responsive behavior
        window.addEventListener('resize', function() {
            const formPanel = document.querySelector('.form-sticky-panel');
            if (window.innerWidth <= 900) {
                formPanel.style.position = 'static';
            } else {
                formPanel.style.position = 'sticky';
            }
        });
    });
</script>