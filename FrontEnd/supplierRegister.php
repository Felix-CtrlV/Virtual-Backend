<?php
include("../BackEnd/config/dbconfig.php");

// 1. Capture Duration from URL (default to 1 if missing)
$duration = isset($_GET['duration']) ? intval($_GET['duration']) : 1;
$calculated_amount = 1000 * $duration;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $phone = $_POST['phone'] ?? '';
    $company_name = $_POST['shopname'];
    $tags = $_POST['tags'] ?? '';
    $description = $_POST['shopdescription'] ?? '';
    $account_number = $_POST['account_number'] ?? '';
    $template_id = intval($_POST['selected_template']);
    $primary_color = $_POST['primary'] ?? '#7d6de3';
    $secondary_color = $_POST['secondary'] ?? '#ff00e6';
    $banner_type = $_POST['banner_type'] ?? 'image';
    $months_to_add = intval($_POST['selected_duration']);
    $renting_price = 1000 * $months_to_add;

    // 1. INSERT SUPPLIER with all fields
    $sql_supplier = "INSERT INTO suppliers (name, email, password, company_name, tags, description, address, phone, account_number, template_id, renting_price, status) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
    $stmt = $conn->prepare($sql_supplier);
    $stmt->bind_param("sssssssssids", $name, $email, $password, $company_name, $tags, $description, $address, $phone, $account_number, $template_id, $renting_price);

    if ($stmt->execute()) {
        $new_supplier_id = $conn->insert_id;

        // 2. Handle file uploads
        $upload_dir = "../uploads/shops/$new_supplier_id/";
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $logo_name = '';
        $banner_name = '';

        // Upload logo
        if (isset($_FILES['logoimage']) && $_FILES['logoimage']['error'] === UPLOAD_ERR_OK) {
            $logo_ext = pathinfo($_FILES['logoimage']['name'], PATHINFO_EXTENSION);
            $logo_name = 'logo.' . $logo_ext;
            move_uploaded_file($_FILES['logoimage']['tmp_name'], $upload_dir . $logo_name);
        }

        // Upload banner (image or video)
        if (isset($_FILES['bannerimage']) && $_FILES['bannerimage']['error'] === UPLOAD_ERR_OK) {
            $banner_ext = pathinfo($_FILES['bannerimage']['name'], PATHINFO_EXTENSION);
            $banner_name = 'banner.' . $banner_ext;
            move_uploaded_file($_FILES['bannerimage']['tmp_name'], $upload_dir . $banner_name);
        }

        // 3. INSERT RENT PAYMENT
        $paid_date = date('Y-m-d');
        $due_date = date('Y-m-d', strtotime("+$months_to_add month"));
        $total_amount = $renting_price;

        $sql_rent = "INSERT INTO rent_payments (supplier_id, paid_date, due_date, paid_amount, month) VALUES (?, ?, ?, ?, ?)";
        $stmt_rent = $conn->prepare($sql_rent);
        $stmt_rent->bind_param("issdi", $new_supplier_id, $paid_date, $due_date, $total_amount, $months_to_add);
        $stmt_rent->execute();

        // 4. INSERT SHOP ASSETS
        $sql_assets = "INSERT INTO shop_assets (supplier_id, logo, banner, primary_color, secondary_color, about, description, template_type) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $about_text = $description;
        $stmt_assets = $conn->prepare($sql_assets);
        $stmt_assets->bind_param("isssssss", $new_supplier_id, $logo_name, $banner_name, $primary_color, $secondary_color, $about_text, $description, $banner_type);
        $stmt_assets->execute();

        header("Location: supplierLogin.php?msg=registered");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
}

$templatequery = "select * from templates";
$templateResult = mysqli_query($conn, $templatequery);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier Registration</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/Css/supplierregister.css">
    <style>
        /* Override for Step 3 Layout inside the form panel */
        .step-3-content {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .step-3-content h3 {
            margin-bottom: 10px;
        }
        
        .banner-mode-selector label {
            transition: all 0.3s ease;
        }
        
        .banner-mode-selector input[type="radio"]:checked + label,
        .banner-mode-selector label:has(input[type="radio"]:checked) {
            border-color: #7d6de3 !important;
            background: #f0f0ff;
        }
        
        .banner-upload-box video {
            max-width: 100%;
            max-height: 200px;
            border-radius: 8px;
        }
        
        .banner-mode-selector label:hover {
            border-color: #7d6de3;
            background: #f9f9ff;
        }
    </style>
</head>

<body>

    <div class="container">

        <div class="left-panel">
            <h1>Join Us</h1>
            <p class="sub-text">Creating account with <strong><?php echo $duration; ?> Month</strong> Plan. Total: $<?php echo number_format($calculated_amount); ?>.</p>

            <div class="progress-bar">
                <div class="dot active" id="d1"></div>
                <div class="dot" id="d2"></div>
                <div class="dot" id="d3"></div>
            </div>

            <form id="regForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="selected_duration" value="<?php echo $duration; ?>">

                <div class="step-group active" id="step1">
                    <h3>Personal Details</h3>
                    <div class="input-group">
                        <input type="text" name="name" placeholder="Full Name" required>
                    </div>
                    <div class="input-group">
                        <input type="email" name="email" placeholder="Email Address" required>
                    </div>
                    <div class="input-group">
                        <input type="text" name="address" placeholder="Address" required>
                    </div>
                    <div class="input-group password-container">
                        <input type="password" id="password" name="password" placeholder="Password" required>
                        <i class="fa-regular fa-eye eye-icon" onclick="togglePass('password', this)"></i>
                    </div>
                    <div class="input-group">
                        <input type="text" name="phone" placeholder="Phone Number">
                    </div>
                    <button type="button" class="submit-btn" onclick="nextStep(2)">Next Step</button>
                </div>

                <div class="step-group" id="step2">
                    <h3>Shop Details</h3>
                    
                    <div class="banner-mode-selector" style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 10px; font-weight: 600;">Banner Type:</label>
                        <div style="display: flex; gap: 15px;">
                            <label style="flex: 1; padding: 12px; border: 2px solid #ddd; border-radius: 8px; cursor: pointer; text-align: center; transition: all 0.3s;">
                                <input type="radio" name="banner_type" value="image" checked onchange="toggleBannerType('image')" style="margin-right: 8px;">
                                <i class="fas fa-image" style="margin-right: 5px;"></i> Image
                            </label>
                            <label style="flex: 1; padding: 12px; border: 2px solid #ddd; border-radius: 8px; cursor: pointer; text-align: center; transition: all 0.3s;">
                                <input type="radio" name="banner_type" value="video" onchange="toggleBannerType('video')" style="margin-right: 8px;">
                                <i class="fas fa-video" style="margin-right: 5px;"></i> Video
                            </label>
                        </div>
                    </div>
                    
                    <div class="shop-visual-header">
                        <label class="banner-upload-box" for="u_banner" id="banner-label">
                            <span id="banner-ph">Upload Banner Image</span>
                            <img id="prev_banner" src="" style="display: none;">
                            <video id="prev_banner_video" src="" style="display: none; width: 100%; max-height: 200px;"></video>
                        </label>
                        <input type="file" name="bannerimage" id="u_banner" accept="image/*,video/*" onchange="previewBanner(this)">
                        <input type="hidden" name="template_type" id="template_type" value="image">

                        <div class="logo-upload-box">
                            <label class="logo-inner" for="u_logo">
                                <i class="fas fa-camera" id="logo-icon"></i>
                                <img id="prev_logo" src="">
                            </label>
                            <input type="file" name="logoimage" id="u_logo" accept="image/*" onchange="previewImage(this, 'prev_logo')">
                        </div>
                    </div>

                    <div class="input-group" style="margin-top: 10px;">
                        <input type="text" name="shopname" placeholder="Company/Shop Name" required>
                    </div>
                    <div class="input-group">
                        <input type="text" name="tags" placeholder="Tags (e.g., Fashion, Electronics)" required>
                    </div>
                    <div class="input-group">
                        <textarea rows="3" name="shopdescription" placeholder="Shop Description" required></textarea>
                    </div>
                    <div class="input-group">
                        <input type="text" name="account_number" placeholder="Account Number">
                    </div>

                    <div class="btn-row">
                        <button type="button" class="back-btn-form" onclick="prevStep(1)">Back</button>
                        <button type="button" class="submit-btn" onclick="nextStep(3)">Next Step</button>
                    </div>
                </div>

                <div class="step-group" id="step3">
                    <div class="step-3-content">
                        <h3>Select Template</h3>

                        <div class="template-grid">
                            <?php while ($template = mysqli_fetch_assoc($templateResult)) { ?>
                                <div class="template-card"
                                    style="background-image: url(assets/template_preview/<?= $template['preview_image'] ?>); background-size: cover;"
                                    data-template-id="<?= $template['template_id'] ?>">
                                    <div class="t-name" style="background: rgba(0,0,0,0.7); color:white; padding: 2px 5px;"><?= $template['template_name'] ?></div>
                                </div>
                            <?php } ?>
                            <input type="hidden" name="selected_template" id="selected_template" value="">
                        </div>

                        <span class="input-label">Theme Colors</span>
                        <div class="color-picker-row">
                            <div class="color-item" onclick="document.getElementById('c_primary').click()">
                                <div class="color-preview" id="cp_primary" style="background: #7d6de3;"></div>
                                <span class="color-label">Primary</span>
                                <input name="primary" type="color" id="c_primary" value="#7d6de3" oninput="updateColor('cp_primary', this.value)">
                            </div>
                            <div class="color-item" onclick="document.getElementById('c_secondary').click()">
                                <div class="color-preview" id="cp_secondary" style="background: #ff00e6;"></div>
                                <span class="color-label">Accent</span>
                                <input name="secondary" type="color" id="c_secondary" value="#ff00e6" oninput="updateColor('cp_secondary', this.value)">
                            </div>
                        </div>

                        <div class="btn-row">
                            <button type="button" class="back-btn-form" onclick="prevStep(2)">Back</button>
                            <button type="submit" class="submit-btn">Create & Pay $<?php echo $calculated_amount; ?></button>
                        </div>
                    </div>
                </div>

            </form>
        </div>

        <div class="right-panel" id="visualPanel">
            <div class="logo-icon"><i class="fas fa-vr-cardboard"></i></div>

            <div class="quote-box" id="staticQuote">
                <h2>Where Malls,<br>Transcend Reality.</h2>
            </div>

            <div id="live-preview-wrapper" style="display:none;">
            </div>
        </div>

    </div>

    <script>
        function togglePass(id, icon) {
            const input = document.getElementById(id);
            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                input.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }

        function nextStep(step) {
            // 1. Handle Form Sections
            document.querySelectorAll('.step-group').forEach(el => el.classList.remove('active'));
            document.getElementById('step' + step).classList.add('active');

            // 2. Handle Dots
            document.querySelectorAll('.dot').forEach((el, index) => {
                if (index < step) el.classList.add('active');
                else el.classList.remove('active');
            });

            // 3. Handle Right Panel Logic
            const quote = document.getElementById('staticQuote');

            // Per request: On Step 3, "remove the preview, just leave the photo".
            // Since the photo is the background-image of .right-panel, we just need to ensure
            // no overlay content (like the quote or the browser preview) is blocking it.
            if (step === 3) {
                quote.style.display = 'none'; // Hide the text
                // We do NOT show the #live-preview-wrapper
            } else {
                quote.style.display = 'block'; // Show text on step 1 & 2
            }
        }

        function prevStep(step) {
            nextStep(step);
        }

        function previewImage(input, imgId) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById(imgId).src = e.target.result;
                    document.getElementById(imgId).style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        function toggleBannerType(type) {
            const bannerInput = document.getElementById('u_banner');
            const bannerLabel = document.getElementById('banner-label');
            const bannerPh = document.getElementById('banner-ph');
            const templateType = document.getElementById('template_type');
            
            templateType.value = type;
            
            if (type === 'video') {
                bannerInput.accept = 'video/*';
                bannerPh.textContent = 'Upload Banner Video';
                document.getElementById('prev_banner').style.display = 'none';
                document.getElementById('prev_banner_video').style.display = 'none';
            } else {
                bannerInput.accept = 'image/*';
                bannerPh.textContent = 'Upload Banner Image';
                document.getElementById('prev_banner').style.display = 'none';
                document.getElementById('prev_banner_video').style.display = 'none';
            }
        }
        
        function previewBanner(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const bannerType = document.querySelector('input[name="banner_type"]:checked').value;
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    if (bannerType === 'video') {
                        document.getElementById('prev_banner').style.display = 'none';
                        const videoEl = document.getElementById('prev_banner_video');
                        videoEl.src = e.target.result;
                        videoEl.style.display = 'block';
                        videoEl.controls = true;
                    } else {
                        document.getElementById('prev_banner_video').style.display = 'none';
                        const imgEl = document.getElementById('prev_banner');
                        imgEl.src = e.target.result;
                        imgEl.style.display = 'block';
                    }
                    document.getElementById('banner-ph').style.display = 'none';
                }
                reader.readAsDataURL(file);
            }
        }

        function updateColor(previewId, colorVal) {
            document.getElementById(previewId).style.background = colorVal;
        }

        // Template Selection Logic
        const cards = document.querySelectorAll('.template-card');
        const hiddenInput = document.getElementById('selected_template');

        cards.forEach(card => {
            card.addEventListener('click', () => {
                cards.forEach(c => c.classList.remove('selected'));
                card.classList.add('selected');
                hiddenInput.value = card.dataset.templateId;
            });
        });
    </script>
</body>

</html>