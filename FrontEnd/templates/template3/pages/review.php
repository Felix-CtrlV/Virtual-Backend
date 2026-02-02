<?php
include("../../BackEnd/config/dbconfig.php");

// --- 1. START SESSION & CHECK LOGIN ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$is_logged_in = isset($_SESSION['customer_id']);
$current_url = urlencode($_SERVER['REQUEST_URI']);

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // --- 2. BACKEND LOGIN PROTECTION ---
    // Even if JS is bypassed, prevent Guest from submitting via PHP
    if (!$is_logged_in) {
        echo "<script>
                alert('Please login to submit your review.');
                window.location.href = '../customerLogin.php?return_url=$current_url';
              </script>";
        exit();
    }

    $supplier_id = 1;
    $customer_id = $_SESSION['customer_id']; // Use actual ID from Session
    $rating = (int) $_POST['rating'];
    $review = trim($_POST['review']);

    $stmt = mysqli_prepare($conn, "
        INSERT INTO reviews (supplier_id, customer_id, review, rating, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    mysqli_stmt_bind_param(
        $stmt,
        "iisi",
        $supplier_id,
        $customer_id,
        $review,
        $rating
    );
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

$rating_counts = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
$total_ratings = 0;
$sum_ratings = 0;
$reviews = [];

$result = mysqli_query($conn, "
    SELECT 
        r.review_id,
        r.company_id,
        r.customer_id,
        r.review,
        r.rating,
        r.created_at,
        c.name,
        c.image FROM reviews r LEFT JOIN customers c ON r.customer_id = c.customer_id where company_id = $company_id");



while ($row = mysqli_fetch_assoc($result)) {
    $reviews[] = $row;
    $rating_counts[$row['rating']]++;
    $total_ratings++;
    $sum_ratings += $row['rating'];
}

$average_rating = $total_ratings > 0
    ? $sum_ratings / $total_ratings
    : 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Reviews</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .modal-overlay {
            display: none; 
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }

        .login-modal {
            background: rgba(255, 255, 255, 0.1);
            padding: 50px 40px;
            border-radius: 40px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            text-align: center;
            color: white;
            width: 400px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }

        .login-modal h2 {
            font-size: 2.2rem;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .login-modal p {
            opacity: 0.8;
            font-weight: 300;
            margin-bottom: 30px;
        }

        .modal-btn {
            display: block;
            width: 100%;
            padding: 15px;
            margin: 15px 0;
            border-radius: 40px;
            border: 1.5px solid rgba(255, 255, 255, 0.5);
            background: rgba(255, 255, 255, 0.05);
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .modal-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: white;
        }

        .or-divider {
            margin: 25px 0;
            display: flex;
            align-items: center;
            font-size: 0.9rem;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
            opacity: 0.6;
        }

        .or-divider::before, .or-divider::after {
            content: "";
            flex: 1;
            height: 1px;
            background: rgba(255, 255, 255, 0.3);
            margin: 0 15px;
        }

        .cancel-btn {
            margin-top: 20px;
            cursor: pointer;
            opacity: 0.5;
            transition: 0.3s;
            font-size: 0.95rem;
            display: inline-block;
        }

        .cancel-btn:hover {
            opacity: 1;
        }
    </style>
</head>

<body>
    <div class="container">

        <div class="review-section">
            <div class="rating-summary">
                <h2>Rating & Reviews</h2>

                <div class="rating-overview">
                    <div class="average-rating">
                        <span class="rating-number">
                            <?= number_format($average_rating, 1) ?>
                        </span>
                        <div class="stars">
                            <?php
                            $full = floor($average_rating);
                            $half = ($average_rating - $full) >= 0.5;
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= $full) {
                                    echo '<i class="fas fa-star"></i>';
                                } elseif ($i == $full + 1 && $half) {
                                    echo '<i class="fas fa-star-half-alt"></i>';
                                } else {
                                    echo '<i class="far fa-star"></i>';
                                }
                            }
                            ?>
                        </div>
                        <p><?= $total_ratings ?> Ratings</p>
                    </div>

                    <div class="rating-bars">
                        <?php for ($i = 5; $i >= 1; $i--):
                            $width = $total_ratings
                                ? ($rating_counts[$i] / $total_ratings) * 100
                                : 0;
                            ?>
                            <div class="rating-bar">
                                <span class="rating-label"><?= $i ?> Star</span>
                                <div class="bar-container">
                                    <div class="bar" style="width:<?= $width ?>%"></div>
                                </div>
                                <span class="rating-count"><?= $rating_counts[$i] ?></span>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>

            <div class="main-wrapper">
                <div class="comments-section">
                    <header class="comment-header">
                        <h2>Recent Feedbacks</h2>
                    </header>
                    <div class="comments-list">
                        <?php foreach ($reviews as $r): ?>
                            <div class="comment-item">
                                <div class="user-avatar-circle"
                                    style="background-image: url(../assets/customer_profiles/<?= $r['image'] ?>);">
                                </div>
                                <div class="comment-content">
                                    <div class="comment-bubble">
                                        <div class="bubble-header">
                                            <span class="user-name">                                                
                                                <?= $r['name'] ?>
                                            </span>

                                            <div class="star-rating">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <?= $i <= $r['rating']
                                                        ? '<span class="star filled">★</span>'
                                                        : '<span class="star">☆</span>' ?>
                                                <?php endfor; ?>
                                            </div>
                                        </div>

                                        <div class="feedback-message">
                                            <?= htmlspecialchars($r['review']) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="review-form-container">
                    <div class="add-review">
                        <header class="form-header">
                            <h3>Add a Review</h3>
                        </header>

                        <form id="reviewForm" method="POST">
                            <div class="textarea-group">
                                <label>Your Feedback</label>
                                <textarea name="review" placeholder="Within 400 Characters" maxlength="400" required></textarea>
                            </div>

                            <div class="stars-wrapper">
                                <div class="star-box gray" data-rating="1"><i class="fa-solid fa-star"></i></div>
                                <div class="star-box gray" data-rating="2"><i class="fa-solid fa-star"></i></div>
                                <div class="star-box gray" data-rating="3"><i class="fa-solid fa-star"></i></div>
                                <div class="star-box gray" data-rating="4"><i class="fa-solid fa-star"></i></div>
                                <div class="star-box gray" data-rating="5"><i class="fa-solid fa-star"></i></div>
                            </div>
                            <input type="hidden" name="rating" id="selected-rating" value="5">
                            <button type="submit" name="submit" class="submit-btn-gradient">SUBMIT</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div id="loginModal" class="modal-overlay">
            <div class="login-modal">
                <h1 style="font-size: 2.5rem; margin-bottom: 10px;">Log back in</h1>
                <p style="margin-bottom: 25px; opacity: 0.8;">Choose an account to continue.</p>

                <div class="or-divider">OR</div>
                <a href="../customerLogin.php?return_url=<?= $current_url ?>" class="modal-btn">Log in to another account</a>
                <a href="../customerRegister.php" class="modal-btn">Create account</a>

                <p onclick="document.getElementById('loginModal').style.display='none'" style="cursor:pointer; margin-top:20px; font-size: 0.9rem; opacity: 0.5; transition: 0.3s;"
                    onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.5'">Cancel
                </p>
            </div>
        </div>

        <script>
            // --- 3. FRONTEND LOGIN PROTECTION (JS) ---
            const isLoggedIn = <?= json_encode($is_logged_in) ?>;
            const reviewForm = document.getElementById('reviewForm');
            const starBoxes = document.querySelectorAll('.star-box');
            const ratingInput = document.getElementById('selected-rating');
            const loginModal = document.getElementById('loginModal');

            
            starBoxes.forEach(box => {
                box.addEventListener('click', () => {
                    const currentRating = parseInt(box.dataset.rating);
                    ratingInput.value = currentRating;


                    starBoxes.forEach(s => {
                        const sRating = parseInt(s.dataset.rating);
                        if (sRating <= currentRating) {

                            s.classList.remove('gray');

                        } else {

                            s.classList.add('gray');
                        }
                    });
                });
            });

            // Form Submit Logic
            if (reviewForm) {
                reviewForm.addEventListener('submit', function(e) {
                    if (!isLoggedIn) {
                        e.preventDefault();
                        loginModal.style.display = 'flex';
                    }
                });
            }


            window.onclick = function(event) {
                if (event.target == loginModal) {
                    loginModal.style.display = "none";
                }
            }
        </script>

</body>
</html>