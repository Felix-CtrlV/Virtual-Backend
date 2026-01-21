<?php
include("../BackEnd/config/dbconfig.php");

// 1. Capture Duration from URL (default to 1 if missing)
$duration = isset($_GET['duration']) ? intval($_GET['duration']) : 1;
$calculated_amount = 1000 * $duration;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Secure hashing
    $phone = $_POST['phone'];

    $shopname = $_POST['shopname'];
    // ... handle other fields and file uploads as per your logic ...

    // 1. INSERT SUPPLIER (Simplified Query - Adjust columns to your actual table)
    $sql_supplier = "INSERT INTO suppliers (name, email, password, address, phone, shop_name) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql_supplier);
    $stmt->bind_param("ssssss", $name, $email, $password, $address, $phone, $shopname);

    if ($stmt->execute()) {
        $new_supplier_id = $conn->insert_id; // Get the ID of the new supplier

        // 2. INSERT RENT DATA
        // Logic: Paid = Now, Due = Now + X Months, Amount = 1000 * X
        $months_to_add = intval($_POST['selected_duration']);
        $total_amount = 1000 * $months_to_add;

        $paid_date = date('Y-m-d');
        $due_date = date('Y-m-d', strtotime("+$months_to_add month"));

        $sql_rent = "INSERT INTO rent_payment (supplier_id, paid_date, due_date, paid_amount, month) VALUES (?, ?, ?, ?, ?)";
        $stmt_rent = $conn->prepare($sql_rent);
        $stmt_rent->bind_param("issdi", $new_supplier_id, $paid_date, $due_date, $total_amount, $months_to_add);
        $stmt_rent->execute();

        // Redirect to success or login
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
                    <div class="shop-visual-header">
                        <label class="banner-upload-box" for="u_banner">
                            <span id="banner-ph">Upload Banner</span>
                            <img id="prev_banner" src="">
                        </label>
                        <input type="file" name="bannerimage" id="u_banner" accept="image/*" onchange="previewImage(this, 'prev_banner')">

                        <div class="logo-upload-box">
                            <label class="logo-inner" for="u_logo">
                                <i class="fas fa-camera" id="logo-icon"></i>
                                <img id="prev_logo" src="">
                            </label>
                            <input type="file" name="logoimage" id="u_logo" accept="image/*" onchange="previewImage(this, 'prev_logo')">
                        </div>
                    </div>

                    <div class="input-group" style="margin-top: 10px;">
                        <input type="text" name="shopname" placeholder="Shop Name" required>
                    </div>
                    <div class="input-group">
                        <textarea rows="3" name="shopdescription" placeholder="Shop Description"></textarea>
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