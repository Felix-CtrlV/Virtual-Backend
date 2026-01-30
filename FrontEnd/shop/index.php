<?php
session_start();
$isLoggedIn = isset($_SESSION['customer_logged_in']) && $_SESSION['customer_logged_in'] === true;
// ======================================================
// SHOP ENTRY POINT WITH GLOBAL LOADING SCREEN (FIXED)
// ======================================================

// Enable errors during development (TURN OFF IN PROD)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. Start output buffering
ob_start();

include '../../BackEnd/config/dbconfig.php';

// 2. Get supplier ID
$supplier_id = isset($_GET['supplier_id']) ? (int) $_GET['supplier_id'] : 0;

if ($supplier_id <= 0) {
    die("Invalid supplier ID.");
}

// 3. Get supplier + template
$stmt = mysqli_prepare($conn, "
   SELECT s.*, c.*, t.template_folder
FROM suppliers s
JOIN companies c ON s.supplier_id = c.supplier_id
JOIN templates t ON c.template_id = t.template_id
WHERE s.supplier_id = ?
");

if (!$stmt) {
    die("DB prepare failed: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "i", $supplier_id);
mysqli_stmt_execute($stmt);
$result   = mysqli_stmt_get_result($stmt);
$supplier = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$supplier) {
    die("Supplier not found.");
}

// 4. Load template
$template_path = "../templates/" . $supplier['template_folder'] . "/index.php";

if (!file_exists($template_path)) {
    die("Template not found: " . htmlspecialchars($template_path));
}

include $template_path;

// 5. Capture page output
$page_content = ob_get_clean();

// 3. Define the Loader HTML, CSS, and JS

// HTML STRUCTURE (Unchanged)
$loader_html = '
<div id="global-loading-screen">
    <div class="gs-spinner-wrapper">
        <div class="gs-spinner">
            <div></div><div></div><div></div><div></div><div></div><div></div>
        </div>
        <div class="gs-spinner2">
            <div></div><div></div><div></div><div></div><div></div><div></div>
        </div>
    </div>
</div>';


$loader_css = '
<style>
    #global-loading-screen {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: #1a1a1a;
        z-index: 9999999;
        display: flex;
        justify-content: center;
        align-items: center;
        transition: opacity 0.6s ease-out, visibility 0.6s;
    }

    #global-loading-screen.loaded {
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
    }

    .gs-spinner-wrapper {
        position: relative;
        width: 0;
        height: 0;
    }

    .gs-spinner {
        width: 16px;
        height: 16px;
        animation: spinner-y0fdc1 15s forwards infinite ease;
        position: absolute;
        top: 50%; left: 50%;
        margin-left: -8px;
        margin-top: -8px;
        transform-style: preserve-3d;
    }

    .gs-spinner > div {
        height: 100%;
        position: absolute;
        width: 100%;
        border: 1px solid #f8c828;
    }

    .gs-spinner div:nth-of-type(1) {
        transform: translateZ(-8px) rotateY(180deg);
    }

    .gs-spinner div:nth-of-type(2) {
        transform: rotateY(-270deg) translateX(50%);
        transform-origin: top right;
    }

    .gs-spinner div:nth-of-type(3) {
        transform: rotateY(270deg) translateX(-50%);
        transform-origin: center left;
    }

    .gs-spinner div:nth-of-type(4) {
        transform: rotateX(90deg) translateY(-50%);
        transform-origin: top center;
    }

    .gs-spinner div:nth-of-type(5) {
        transform: rotateX(-90deg) translateY(50%);
        transform-origin: bottom center;
    }

    .gs-spinner div:nth-of-type(6) {
        transform: translateZ(8px);
    }

    .gs-spinner2 {
        width: 32px;
        height: 32px;
        animation: spinner-y0fdc2 15s forwards infinite ease;
        position: absolute;
        top: 50%; left: 50%;
        margin-left: -16px;
        margin-top: -16px;
        transform-style: preserve-3d;
    }

    .gs-spinner2 > div {
        height: 100%;
        position: absolute;
        width: 100%;
        border: 1px solid #ffffff;
    }

    .gs-spinner2 div:nth-of-type(1) {
        transform: translateZ(-16px) rotateY(180deg);
    }

    .gs-spinner2 div:nth-of-type(2) {
        transform: rotateY(-270deg) translateX(50%);
        transform-origin: top right;
    }

    .gs-spinner2 div:nth-of-type(3) {
        transform: rotateY(270deg) translateX(-50%);
        transform-origin: center left;
    }

    .gs-spinner2 div:nth-of-type(4) {
        transform: rotateX(90deg) translateY(-50%);
        transform-origin: top center;
    }

    .gs-spinner2 div:nth-of-type(5) {
        transform: rotateX(-90deg) translateY(50%);
        transform-origin: bottom center;
    }

    .gs-spinner2 div:nth-of-type(6) {
        transform: translateZ(16px);
    }

    @keyframes spinner-y0fdc1 {
        0% { transform: rotate(0deg) rotateX(0deg) rotateY(0deg); }
        24% { transform: rotate(0deg) rotateX(360deg) rotateY(360deg); }
        25% { transform: rotate(0deg) rotateX(360deg) rotateY(360deg); }
        50% { transform: rotate(225deg) rotateX(360deg) rotateY(360deg); }
        75% { transform: rotate(0deg) rotateX(360deg) rotateY(360deg); }
        76% { transform: rotate(0deg) rotateX(360deg) rotateY(360deg); }
        96% { transform: rotate(0deg) rotateX(0deg) rotateY(0deg); }
        100% { transform: rotate(0deg) rotateX(0deg) rotateY(0deg); }
    }

    @keyframes spinner-y0fdc2 {
        0% { transform: rotate(45deg) rotateX(0deg) rotateY(0deg); }
        24% { transform: rotate(45deg) rotateX(-360deg) rotateY(-360deg); }
        25% { transform: rotate(45deg) rotateX(-360deg) rotateY(-360deg); }
        50% { transform: rotate(-180deg) rotateX(-360deg) rotateY(-360deg); }
        75% { transform: rotate(45deg) rotateX(-360deg) rotateY(-360deg); }
        76% { transform: rotate(45deg) rotateX(-360deg) rotateY(-360deg); }
        96% { transform: rotate(45deg) rotateX(0deg) rotateY(0deg); }
        100% { transform: rotate(45deg) rotateX(0deg) rotateY(0deg); }
    }
</style>';


$loader_js = '
<script>
    document.addEventListener("DOMContentLoaded", function() {
        var loader = document.getElementById("global-loading-screen");
        
        // --- 1. ENTRY LOGIC: FADE OUT ---
        // Hide loader gently when the page is fully loaded
        window.addEventListener("load", function() {
            if(loader) {
                // Restore transition in case it was removed by a click
                loader.style.transition = ""; 
                loader.classList.add("loaded");
                
                // Remove from display flow after animation
                setTimeout(function(){
                    loader.style.display = "none";
                }, 600);
            }
        });

        // --- 2. EXIT LOGIC: INSTANT SHOW ---
        // Show loader immediately when user clicks a link
        var links = document.querySelectorAll("a");
        links.forEach(function(link) {
            link.addEventListener("click", function(e) {
                var target = link.getAttribute("target");
                var href = link.getAttribute("href");

                // Check if it is a valid internal link
                // (Not a new tab, not empty, not just an anchor/hash)
                if (target !== "_blank" && href && href.trim() !== "" && !href.startsWith("#") && !href.startsWith("javascript")) {
                    if(loader) {
                        // 1. Make sure it is part of layout
                        loader.style.display = "flex"; 
                        // 2. Remove transition so it appears INSTANTLY (no fade-in delay)
                        loader.style.transition = "none";
                        // 3. Make visible
                        loader.style.opacity = "1";
                        loader.style.visibility = "visible";
                        // 4. Ensure class is removed
                        loader.classList.remove("loaded");
                    }
                }
            });
        });

        // --- 3. BROWSER BACK BUTTON FIX ---
        // If user presses back button, the page might load from cache with loader still visible.
        // We force it to hide if the page is shown from "bfcache"
        window.addEventListener("pageshow", function(event) {
            if (event.persisted && loader) {
                loader.classList.add("loaded");
                loader.style.display = "none";
            }
        });
    });
</script>';


$final_content = preg_replace(
    '/<\/head>/i',
    $loader_css . '</head>',
    $page_content,
    1
);

$final_content = preg_replace(
    '/<body([^>]*)>/i',
    '<body$1>' . $loader_html,
    $final_content,
    1
);
$final_js = $loader_js . '<script>const USER_LOGGED_IN = ' . ($isLoggedIn ? 'true' : 'false') . '; const IS_LOGGED_IN = USER_LOGGED_IN;
</script>';

$final_content = preg_replace(
    '/<\/body>/i',
    $final_js . '</body>',
    $final_content,
    1
);

echo $final_content;
