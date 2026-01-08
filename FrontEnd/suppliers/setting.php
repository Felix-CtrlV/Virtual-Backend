<?php
session_start();
include("partials/nav.php");

// 1. FIX: Correct Supplier ID Logic
// We try to get the ID from the session. If not set, we default to 6 (for testing/demo purposes).
$supplier_id = isset($_SESSION['supplierid']) ? $_SESSION['supplierid'] : 6;

$assetsStmt = $conn->prepare("SELECT sa.*, s.* FROM shop_assets sa JOIN suppliers s ON sa.supplier_id = s.supplier_id WHERE sa.supplier_id = ?");
$assetsStmt->bind_param("i", $supplier_id);
$assetsStmt->execute();
$assetsResult = $assetsStmt->get_result();
$currentSettings = $assetsResult->fetch_assoc();
$assetsStmt->close();

// Default values if no settings exist yet
$currentSettings = [
    'shop_name' => $currentSettings ? $currentSettings['company_name'] : 'My Shop',
    'logo' => '../uploads/shops/' . $supplier_id . '/' . ($currentSettings ? $currentSettings['logo'] : ''), // Empty means show placeholder
    'banner_type' => 'image', // or 'video'
    'banner_file' => '../uploads/shops/' . $supplier_id . '/' . ($currentSettings ? $currentSettings['banner'] : ''),
    'primary_color' => $currentSettings ? $currentSettings['primary_color'] : '#333333',
    'secondary_color' => $currentSettings ? $currentSettings['secondary_color'] : '#FFD55A'
];

// 2. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect Inputs
    $shop_name = $_POST['shop_name'];
    $p_color = $_POST['primary_color'];
    $s_color = $_POST['secondary_color'];
    $media_mode = $_POST['media_mode']; // 'image' or 'video'

    // File Upload Logic (Simplified)
    // In a real app, you would move_uploaded_file() to your /uploads folder here.
    // $banner_path = ...
    // $logo_path = ...

    // DB Update Logic would go here:
    // UPDATE shop_settings SET ... WHERE supplier_id = $supplier_id

    // Refresh to show changes (Simulated)
    $currentSettings['shop_name'] = $shop_name;
    $currentSettings['primary_color'] = $p_color;
    $currentSettings['secondary_color'] = $s_color;
    $currentSettings['banner_type'] = $media_mode;

    $success_msg = "Store settings updated successfully!";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store Customization</title>
    <link rel="stylesheet" href="supplierCss.css">

    <style>
        /* --- Page Layout --- */
        .settings-container {
            max-width: 1200px;
            margin: 30px auto;
            display: grid;
            grid-template-columns: 1fr 400px;
            /* Editor on Left, Preview on Right */
            gap: 30px;
            align-items: start;
        }

        .card {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.8);
            border-radius: 25px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        }

        h2 {
            font-weight: 300;
            font-size: 1.8rem;
            margin-bottom: 5px;
        }

        .sub-text {
            color: #888;
            margin-bottom: 25px;
            display: block;
            font-size: 0.9rem;
        }

        /* --- Form Elements --- */
        .form-section {
            margin-bottom: 25px;
        }

        .form-label {
            display: block;
            font-weight: 600;
            margin-bottom: 10px;
            color: #444;
        }

        .input-text {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #eee;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.8);
            transition: 0.3s;
        }

        .input-text:focus {
            border-color: #333;
            outline: none;
        }

        /* --- Circular Color Pickers --- */
        .color-row {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .color-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
            cursor: pointer;
        }

        input[type="color"] {
            -webkit-appearance: none;
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            /* CIRCLE RADIUS */
            overflow: hidden;
            padding: 0;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }

        input[type="color"]:hover {
            transform: scale(1.1);
        }

        input[type="color"]::-webkit-color-swatch-wrapper {
            padding: 0;
        }

        input[type="color"]::-webkit-color-swatch {
            border: none;
            border-radius: 50%;
        }

        /* --- Toggle Switch (Image vs Video) --- */
        .mode-toggle {
            display: flex;
            background: #eee;
            padding: 5px;
            border-radius: 50px;
            width: fit-content;
            margin-bottom: 15px;
        }

        .mode-btn {
            padding: 8px 25px;
            border-radius: 50px;
            cursor: pointer;
            font-weight: 600;
            transition: 0.3s;
            user-select: none;
        }

        /* Active State logic handled by JS class */
        .mode-btn.active {
            background: #fff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            color: #000;
        }

        .mode-btn.inactive {
            color: #888;
        }

        /* Hidden Inputs */
        input[type="radio"] {
            display: none;
        }

        /* --- Modern Upload Area --- */
        .upload-zone {
            border: 2px dashed #ccc;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            background: #fafafa;
            cursor: pointer;
            transition: 0.3s;
            position: relative;
        }

        .upload-zone:hover {
            border-color: var(--secondary-color, #FFD55A);
            background: #fff;
        }

        .upload-icon {
            font-size: 24px;
            color: #aaa;
            margin-bottom: 8px;
        }

        /* --- YOUTUBE STYLE PREVIEW --- */
        .preview-sticky {
            position: sticky;
            top: 100px;
        }

        .yt-preview-container {
            background: #fff;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid #eee;
        }

        .yt-banner {
            width: 100%;
            height: 120px;
            background-color: #333;
            /* Fallback */
            position: relative;
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Banner Video Style */
        .yt-banner video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            position: absolute;
            top: 0;
            left: 0;
        }

        .yt-header-row {
            padding: 0 20px 20px 20px;
            margin-top: -35px;
            /* Overlap effect */
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .yt-logo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #fff;
            border: 4px solid #fff;
            object-fit: cover;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 10px;
        }

        .yt-name {
            font-weight: bold;
            font-size: 1.2rem;
            color: #000;
        }

        .yt-sub {
            font-size: 0.8rem;
            color: #666;
            margin-top: 2px;
        }

        .yt-nav-tabs {
            display: flex;
            justify-content: center;
            gap: 20px;
            border-top: 1px solid #eee;
            padding: 10px 0;
            margin-top: 15px;
        }

        .yt-tab {
            font-size: 0.85rem;
            font-weight: 600;
            color: #666;
            text-transform: uppercase;
            cursor: pointer;
        }

        .yt-tab.active {
            border-bottom: 2px solid #333;
            color: #333;
            padding-bottom: 5px;
        }

        .btn-save {
            background: #333;
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            margin-top: 20px;
            width: 100%;
            transition: 0.3s;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        @media (max-width: 900px) {
            .settings-container {
                grid-template-columns: 1fr;
            }

            .preview-sticky {
                position: relative;
                top: 0;
            }
        }
    </style>
</head>

<body>

    <div class="settings-container">

        <div class="card">
            <h2>Customization</h2>
            <span class="sub-text">Design your shop layout and branding. Supplier ID: #<?= $supplier_id ?></span>

            <?php if (isset($success_msg)): ?>
                <div style="background:#d4edda; color:#155724; padding:10px; border-radius:10px; margin-bottom:20px;">
                    <?= $success_msg ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">

                <div class="form-section">
                    <label class="form-label">Shop Name</label>
                    <input type="text" name="shop_name" id="shopNameInput" class="input-text"
                        value="<?= htmlspecialchars($currentSettings['shop_name']) ?>" required>
                </div>

                <div class="form-section">
                    <label class="form-label">Theme Colors</label>
                    <div class="color-row">
                        <div class="color-wrapper">
                            <input type="color" name="primary_color" id="primaryColorInput"
                                value="<?= $currentSettings['primary_color'] ?>" title="Primary Color">
                            <small>Primary</small>
                        </div>
                        <div class="color-wrapper">
                            <input type="color" name="secondary_color" id="secondaryColorInput"
                                value="<?= $currentSettings['secondary_color'] ?>" title="Secondary Color">
                            <small>Accent</small>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <label class="form-label">Profile Picture (Logo)</label>
                    <div class="upload-zone" onclick="document.getElementById('logoFile').click()">
                        <input type="file" name="logo" id="logoFile" hidden accept="image/*" onchange="previewLogo(this)">
                        <div class="upload-icon">📷</div>
                        <small>Click to upload logo</small>
                    </div>
                </div>

                <div class="form-section">
                    <label class="form-label">Banner Media</label>

                    <div class="mode-toggle">
                        <label class="mode-btn active" id="btn-image" onclick="setMode('image')">
                            Image Mode
                            <input type="radio" name="media_mode" value="image" checked>
                        </label>
                        <label class="mode-btn inactive" id="btn-video" onclick="setMode('video')">
                            Video Mode
                            <input type="radio" name="media_mode" value="video">
                        </label>
                    </div>

                    <div id="image-input-area">
                        <div class="upload-zone" onclick="document.getElementById('bannerImgFile').click()">
                            <input type="file" name="banner_image" id="bannerImgFile" hidden accept="image/*" onchange="previewBannerImg(this)">
                            <div class="upload-icon">🖼️</div>
                            <span>Upload Banner Image</span><br>
                            <small style="color:#aaa;">Recommended: 2048 x 1152 px</small>
                        </div>
                    </div>

                    <div id="video-input-area" style="display:none;">
                        <div class="upload-zone" onclick="document.getElementById('bannerVidFile').click()">
                            <input type="file" name="banner_video" id="bannerVidFile" hidden accept="video/*" onchange="previewBannerVid(this)">
                            <div class="upload-icon">🎥</div>
                            <span>Upload Background Video</span><br>
                            <small style="color:#aaa;">Max size: 50MB (MP4)</small>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-save">Publish Changes</button>
            </form>
        </div>

        <div class="preview-sticky">
            <h4 style="margin-bottom:15px; color:#666;">Live Preview</h4>

            <div class="yt-preview-container">
                <div class="yt-banner" id="previewBanner">
                    <video id="previewVideoEl" autoplay loop muted playsinline style="display:none;"></video>
                </div>

                <div class="yt-header-row">
                    <img src="<?= htmlspecialchars($currentSettings['logo']) ?>" id="previewLogo" class="yt-logo"">

                    <div class=" yt-name" id="previewName"><?= htmlspecialchars($currentSettings['shop_name']) ?>
                </div>
                <div class="yt-sub">@supplier_<?= $supplier_id ?> • 0 subscribers</div>
            </div>

            <div class="yt-nav-tabs">
                <div class="yt-tab active">Home</div>
                <div class="yt-tab">Products</div>
                <div class="yt-tab">About</div>
            </div>

            <div style="padding: 20px; background: #fafafa; height: 150px; display:flex; justify-content:center; align-items:center; color:#ccc;">
                Store Content Placeholder
            </div>
        </div>

        <div style="margin-top:20px; text-align:center; font-size:0.8rem; color:#888;">
            * This is how your store will look to customers.
        </div>
    </div>

    </div>

    <script>
        // 1. Handle Shop Name Live Update
        const nameInput = document.getElementById('shopNameInput');
        const namePreview = document.getElementById('previewName');

        nameInput.addEventListener('input', function() {
            namePreview.textContent = this.value || 'Shop Name';
        });

        // 2. Handle Color Updates (Primary = Save Button, Secondary = Accent)
        const pColorIn = document.getElementById('primaryColorInput');
        const sColorIn = document.getElementById('secondaryColorInput');
        const saveBtn = document.querySelector('.btn-save');
        const activeTabs = document.querySelector('.yt-tab.active');

        pColorIn.addEventListener('input', function() {
            saveBtn.style.backgroundColor = this.value;
        });

        sColorIn.addEventListener('input', function() {
            document.documentElement.style.setProperty('--secondary-color', this.value);
            if (activeTabs) activeTabs.style.color = this.value;
            if (activeTabs) activeTabs.style.borderColor = this.value;
        });

        // 3. MEDIA MODE TOGGLE (Fixes the "Unavailable to click" error)
        function setMode(mode) {
            // Toggle Buttons Visuals
            const btnImg = document.getElementById('btn-image');
            const btnVid = document.getElementById('btn-video');

            // Toggle Inputs
            const areaImg = document.getElementById('image-input-area');
            const areaVid = document.getElementById('video-input-area');

            // Toggle Preview Elements
            const prevBanner = document.getElementById('previewBanner');
            const prevVidEl = document.getElementById('previewVideoEl');

            if (mode === 'image') {
                // Visuals
                btnImg.classList.add('active');
                btnImg.classList.remove('inactive');
                btnVid.classList.add('inactive');
                btnVid.classList.remove('active');

                // Inputs
                areaImg.style.display = 'block';
                areaVid.style.display = 'none';

                // Preview Logic
                prevVidEl.style.display = 'none';
                prevBanner.style.backgroundSize = 'cover';
            } else {
                // Visuals
                btnVid.classList.add('active');
                btnVid.classList.remove('inactive');
                btnImg.classList.add('inactive');
                btnImg.classList.remove('active');

                // Inputs
                areaVid.style.display = 'block';
                areaImg.style.display = 'none';

                // Preview Logic
                prevVidEl.style.display = 'block';
                prevBanner.style.backgroundImage = 'none'; // Clear image to show video
                prevBanner.style.backgroundColor = '#000';
            }
        }

        // 4. PREVIEW: Logo Upload
        function previewLogo(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('previewLogo').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        // 5. PREVIEW: Banner Image
        function previewBannerImg(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const banner = document.getElementById('previewBanner');
                    banner.style.backgroundImage = `url('${e.target.result}')`;
                    // Ensure video is hidden if they upload an image
                    setMode('image');
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        // 6. PREVIEW: Banner Video
        function previewBannerVid(input) {
            if (input.files && input.files[0]) {
                const fileUrl = URL.createObjectURL(input.files[0]);
                const vidEl = document.getElementById('previewVideoEl');
                vidEl.src = fileUrl;
                // Ensure video mode is active
                setMode('video');
            }
        }

        // Initialize Default State
        setMode('image');
    </script>

</body>

</html>