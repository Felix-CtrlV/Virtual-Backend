<?php
$message = "";

include("../BackEnd/config/dbconfig.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Handle registration logic here
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
</head>

<body>

    <div class="container">

        <div class="left-panel">
            <h1>Join Us</h1>
            <p class="sub-text">Create your supplier account. <a href="supplierLogin.php">Log In</a></p>

            <div class="progress-bar">
                <div class="dot active" id="d1"></div>
                <div class="dot" id="d2"></div>
                <div class="dot" id="d3"></div>
            </div>

            <form id="regForm" method="POST" enctype="multipart/form-data" action="utils/registeredsuppliers.php">

                <div class="step-group active" id="step1">
                    <h3>Personal Details</h3>
                    <div class="input-group">
                        <input type="text" id="name" name="name" placeholder="Full Name" required>
                    </div>
                    <div class="input-group">
                        <input type="email" id="email" name="email" placeholder="Email Address" required>
                    </div>
                    <div class="input-group">
                        <input type="text" id="address" name="address" placeholder="Address" required>
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
                            <span id="banner-ph">Upload Banner (1200x300)</span>
                            <img id="prev_banner" src="">
                        </label>
                        <input type="file" name="bannerimage" id="u_banner" accept="image/*"
                            onchange="previewImage(this, 'prev_banner', 'p_hero_bg', 'banner-ph')">

                        <div class="logo-upload-box">
                            <label class="logo-inner" for="u_logo">
                                <i class="fas fa-camera" id="logo-icon"></i>
                                <img id="prev_logo" src="">
                            </label>
                            <input type="file" name="logoimage" id="u_logo" accept="image/*"
                                onchange="previewImage(this, 'prev_logo', null, 'logo-icon')">
                        </div>
                    </div>

                    <div class="input-group" style="margin-top: 10px;">
                        <input type="text" name="shopname" id="shop_name" placeholder="Shop Name" oninput="liveUpdate()"
                            required>
                    </div>
                    <div class="input-group">
                        <input type="text" name="tags" placeholder="Tags (e.g. Clothing, Tech)">
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
                    <h3>Design & Theme</h3>

                    <span class="input-label">Select Template</span>
                    <div class="template-grid">
                        <?php while ($template = mysqli_fetch_assoc($templateResult)) { ?>
                            <div class="template-card"
                                style="background-size: cover; background-position: center; background-image: url(assets/template_preview/<?= $template['preview_image'] ?>);"
                                data-template-id="<?= $template['template_id'] ?>">
                                <div class="t-name"><?= $template['template_name'] ?></div>
                            </div>
                        <?php } ?>

                        <input type="hidden" name="selected_template" id="selected_template" value="">
                    </div>

                    <span class="input-label">Theme Colors</span>
                    <div class="color-picker-row">
                        <div class="color-item" onclick="document.getElementById('c_primary').click()">
                            <div class="color-preview" id="cp_primary" style="background: #7d6de3;"></div>
                            <span class="color-label">Primary</span>
                            <input name="primary" type="color" id="c_primary" value="#7d6de3"
                                oninput="updateColor('cp_primary', this.value, 'primary')">
                        </div>
                        <div class="color-item" onclick="document.getElementById('c_secondary').click()">
                            <div class="color-preview" id="cp_secondary" style="background: #ff00e6;"></div>
                            <span class="color-label">Accent</span>
                            <input name="secondary" type="color" id="c_secondary" value="#ff00e6"
                                oninput="updateColor('cp_secondary', this.value, 'secondary')">
                        </div>
                    </div>

                    <div class="input-group">
                        <input type="text" name="about" id="web_headline" placeholder="Website Headline"
                            oninput="liveUpdate()">
                    </div>

                    <div class="btn-row">
                        <button type="button" class="back-btn-form" onclick="prevStep(2)">Back</button>
                        <button type="submit" class="submit-btn">Create Account</button>
                    </div>
                </div>

            </form>
        </div>

        <div class="right-panel" id="visualPanel">
            <div class="logo-icon"><i class="fas fa-vr-cardboard"></i></div>

            <div class="quote-box" id="staticQuote">
                <h2>Where Malls,<br>Transcend Reality.</h2>
            </div>

            <div id="live-preview-wrapper">
                <div class="browser-header">
                    <div class="b-dot r"></div>
                    <div class="b-dot y"></div>
                    <div class="b-dot g"></div>
                </div>
                <div class="p-body">
                    <div class="p-nav" id="p_nav" style="background: #7d6de3;">
                        <span style="font-weight:bold" id="p_shopname">Shop Name</span>
                        <i class="fas fa-bars"></i>
                    </div>
                    <div class="p-hero" id="p_hero_bg"
                        style="background-image: url('https://via.placeholder.com/600x200/222/555');">
                        <div class="p-hero-overlay"></div>
                        <div class="p-content">
                            <h2 id="p_headline">Your Headline</h2>
                            <button class="p-btn" id="p_btn" style="background: #ff00e6;">Shop Now</button>
                        </div>
                    </div>
                    <div style="padding: 20px;">
                        <div style="height: 10px; width: 60%; background: #eee; margin-bottom: 10px;"></div>
                        <div style="height: 10px; width: 80%; background: #eee; margin-bottom: 10px;"></div>
                        <div style="display:flex; gap:10px; margin-top:20px;">
                            <div style="flex:1; height:80px; background:#f4f4f4; border-radius:4px;"></div>
                            <div style="flex:1; height:80px; background:#f4f4f4; border-radius:4px;"></div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <script>
        // 1. Password Toggle
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

        // 2. Wizard Navigation
        function nextStep(step) {
            document.querySelectorAll('.step-group').forEach(el => el.classList.remove('active'));
            document.getElementById('step' + step).classList.add('active');

            // Update Dots
            document.querySelectorAll('.dot').forEach((el, index) => {
                if (index < step) el.classList.add('active');
                else el.classList.remove('active');
            });

            // Handle Right Panel Logic
            const staticQuote = document.getElementById('staticQuote');
            const livePreview = document.getElementById('live-preview-wrapper');

            if (step === 3) {
                staticQuote.style.display = 'none';
                livePreview.style.display = 'flex';
                liveUpdate(); // Ensure data is fresh
            } else {
                staticQuote.style.display = 'block';
                livePreview.style.display = 'none';
            }
        }

        function prevStep(step) {
            nextStep(step);
        }

        function previewImage(input, imgId, heroBgId, placeholderId) {
            if (!input.files || !input.files[0]) return;

            const file = input.files[0];

            if (!file.type.startsWith('image/')) {
                alert('Please select an image file');
                input.value = '';
                return;
            }

            const reader = new FileReader();

            reader.onload = function (e) {
                const img = document.getElementById(imgId);
                if (img) {
                    img.src = e.target.result;
                    img.style.display = 'block';
                }

                if (placeholderId) {
                    const ph = document.getElementById(placeholderId);
                    if (ph) ph.style.display = 'none';
                }

                if (heroBgId) {
                    const hero = document.getElementById(heroBgId);
                    if (hero) {
                        hero.style.backgroundImage = `url(${e.target.result})`;
                    }
                }
            };

            reader.readAsDataURL(file);
        }


        // 4. Live Text & Color Updates
        function liveUpdate() {
            const name = document.getElementById('shop_name').value;
            const headline = document.getElementById('web_headline').value;

            if (name) document.getElementById('p_shopname').innerText = name;
            if (headline) document.getElementById('p_headline').innerText = headline;
        }

        function updateColor(previewId, colorVal, type) {
            // Update the little circle in the form
            document.getElementById(previewId).style.background = colorVal;

            // Update the Live Preview
            if (type === 'primary') {
                document.getElementById('p_nav').style.background = colorVal;
            } else if (type === 'secondary') {
                document.getElementById('p_btn').style.background = colorVal;
            }
        }

        // 5. Template Selection
        function selectTemplate(card, type) {
            document.querySelectorAll('.template-card').forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');

            // Simple layout shift for demo
            const nav = document.getElementById('p_nav');
            if (type === 'classic') {
                nav.style.justifyContent = 'center';
            } else {
                nav.style.justifyContent = 'space-between';
            }
        }
    </script>
    <script>
        const cards = document.querySelectorAll('.template-card');
        const hiddenInput = document.getElementById('selected_template');

        cards.forEach(card => {
            card.addEventListener('click', () => {
                // Remove selected class from all
                cards.forEach(c => c.classList.remove('selected'));

                // Add selected class to clicked card
                card.classList.add('selected');

                // Store selected template ID in hidden input
                hiddenInput.value = card.dataset.templateId;
            });
        });
    </script>


</body>

</html>