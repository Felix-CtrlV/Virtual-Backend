<?php
if (!isset($conn)) {
  include '../../../BackEnd/config/dbconfig.php';
}

$supplier_id = (int) $supplier['supplier_id'];

// Fetch reviews with customer profile images
$review_stmt = mysqli_prepare($conn, "
    SELECT r.rating, r.review, r.created_at, c.image 
    FROM reviews r 
    LEFT JOIN customers c ON r.customer_id = c.customer_id 
    WHERE r.supplier_id = ?
    ORDER BY r.created_at DESC
");
mysqli_stmt_bind_param($review_stmt, "i", $supplier_id);
mysqli_stmt_execute($review_stmt);
$review_result = mysqli_stmt_get_result($review_stmt);

$ratings = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
$reviews = [];
$total_rating = 0;
$total_count = 0;

while ($row = mysqli_fetch_assoc($review_result)) {
  $rating = (int) $row['rating'];
  $ratings[$rating]++;
  $total_rating += $rating;
  $total_count++;
  $reviews[] = $row;
}

$average_rating = $total_count > 0 ? round($total_rating / $total_count, 1) : 0;
mysqli_stmt_close($review_stmt);
?>
<?php
// Handle form submission
if (isset($_POST['submit_review'])) {
    $rating = (int) $_POST['rating'];
    $review_text = trim($_POST['review']);
    $customer_id = 1; // Replace with actual logged-in user ID if available

    // Only proceed if rating is greater than 0 to prevent empty star submissions
    if ($rating > 0 && !empty($review_text)) {
        $insert_stmt = mysqli_prepare($conn, "INSERT INTO reviews (supplier_id, customer_id, review, rating, created_at) VALUES (?, ?, ?, ?, NOW())");
        mysqli_stmt_bind_param($insert_stmt, "iisi", $supplier_id, $customer_id, $review_text, $rating);
        mysqli_stmt_execute($insert_stmt);
        mysqli_stmt_close($insert_stmt);

        // Correct way to refresh: Redirect to the same page to clear POST data
        header("Location: " . $_SERVER['PHP_SELF'] . "?supplier_id=" . $supplier_id);
        exit(); 
    }
}
?>
<div class="review-wrapper">
  <div class="container py-5">
    <div class="row g-4">
      <!-- Rating Breakdown -->
      <div class="col-md-6 rating-breakdown">
    <h4>Rating Breakdown</h4>
    <?php
    $max_count = max($ratings);
    $star_names = [5 => 'FIVE', 4 => 'FOUR', 3 => 'THREE', 2 => 'TWO', 1 => 'ONE'];
    
    foreach ($star_names as $star => $label):
        $count = $ratings[$star];
        $percentage = $total_count > 0 ? ($count / $total_count) * 100 : 0;
        ?>
        <div class="rating-row d-flex align-items-center mb-3">
            <div class="star-text text-uppercase me-2" style="width: 60px; font-weight: 500;">
                <?= $label ?>
            </div>
            
            <div class="single-star me-3" style="color: #60708d;">★</div>
            
            <div class="flex-grow-1 mx-2">
                <div class="progress custom-progress" style="height: 8px; background-color: #7f8a9f44; border-radius: 10px;">
                    <div class="progress-bar" role="progressbar" 
                         style="width: <?= $percentage ?>%; background-color: #60708d; border-radius: 10px;">
                    </div>
                </div>
            </div>
            
            <div class="rating-count ms-3 text-end" style="width: 45px; font-weight: 500;">
                <?= $count > 1000 ? round($count / 1000, 1) . 'K' : $count ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

      <!-- Average Rating -->
      <div class="col-md-6 text-center average-rating">
        <h4>Average Rating</h4>
        <div class="stars">
          <?= str_repeat('★', round($average_rating)) . str_repeat('☆', 5 - round($average_rating)) ?>
        </div>
        <p class="display-5 fw-bold"><?= $average_rating ?></p>
        <p><?= $total_count ?> Ratings</p>
      </div>
    </div>

    <div class="row mt-5">
      <!-- Recent Feedbacks -->
       <h4>Recent Feedbacks</h4>
      <div class="col-md-6 recent-feedback">
  
  <?php foreach ($reviews as $feedback): ?>
    <div class="feedback-card d-flex align-items-start">
      
      <?php 
        // 1. Get the filename from the 'image' column
        $user_img = $feedback['image']; 
        
        // 2. Define the path to your uploads folder (adjust this path to your actual folder)
        $upload_path = "../assets/customer_profiles/";
        
        // 3. Set the final source: use default if database entry is empty
        $img_src = (!empty($user_img)) ? $upload_path . $user_img : $upload_path . "user_1_admin.jpg";
      ?>

      <img src="<?= $img_src ?>" 
           class="rounded-circle me-3" 
           alt="User" 
           style="width: 50px; height: 50px; object-fit: cover; border: 1px solid #eee;">
      
      <div>
        <strong>Customer</strong><br>
        <?= str_repeat('★', $feedback['rating']) . str_repeat('☆', 5 - $feedback['rating']) ?><br>
        <p><?= htmlspecialchars($feedback['review']) ?></p>
      </div>
    </div>
  <?php endforeach; ?>
</div>
      <!-- Add Review Form -->
      <div class="col-md-6 review-form">
        <h4>Add a Review</h4>
        <form method="POST" action="">
          <div class="mb-3">
            <label class="form-label">Add Your Rating</label>
            <div class="star-rating svg-stars">
              <input type="hidden" name="rating" id="rating" value="0">
              <?php for ($i = 1; $i <= 5; $i++): ?>
                <button type="button" class="svg-star" data-value="<?= $i ?>" aria-label="<?= $i ?> star">
                  <svg width="36" height="36" viewBox="0 0 24 24" aria-hidden="true">
                    <path class="star-fill"
                      d="M12 .587l3.668 7.431 8.2 1.193-5.934 5.787 1.402 8.164L12 18.896 4.664 23.162l1.402-8.164L.132 9.211l8.2-1.193z" />
                  </svg>
                </button>
              <?php endfor; ?>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Write Your Review</label>
            <textarea name="review" class="form-control" rows="4" required></textarea>
          </div>
          <button type="submit" name="submit_review" class="btn btn-warnin">Submit</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const container = document.querySelector('.svg-stars');
    if (!container) return;

    const stars = Array.from(container.querySelectorAll('.svg-star'));
    const ratingInput = document.getElementById('rating');

    function setSelection(value) {
      stars.forEach((btn, idx) => {
        btn.classList.toggle('selected', idx < value);
      });
      ratingInput.value = value;
    }

    stars.forEach(btn => {
      btn.addEventListener('click', () => {
        const value = parseInt(btn.getAttribute('data-value'), 10);
        setSelection(value);
      });

      btn.addEventListener('mouseenter', () => {
        const value = parseInt(btn.getAttribute('data-value'), 10);
        stars.forEach((b, idx) => b.classList.toggle('active', idx < value));
      });
      btn.addEventListener('mouseleave', () => {
        stars.forEach(b => b.classList.remove('active'));
      });
    });
  });
</script>