<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Plan - Supplier Registration</title>
    <link rel="stylesheet" href="assets/Css/supplierregister.css">
    <style>
        :root {
            --primary: #7d6de3;
            --primary-hover: #6a59d1;
            --bg-dark: #121214;
            --card-bg: #1c1c21;
            --border: #2d2d35;
            --text-main: #ffffff;
            --text-muted: #a1a1aa;
        }

        body {
            margin: 0;
            padding: 0;
            background-color: var(--bg-dark);
            color: var(--text-main);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            /* Subtle background glow effect */
            background-image:
                radial-gradient(circle at 20% 20%, rgba(125, 109, 227, 0.05) 0%, transparent 40%),
                radial-gradient(circle at 80% 80%, rgba(125, 109, 227, 0.05) 0%, transparent 40%);
        }

        .header-section {
            text-align: center;
            margin-bottom: 40px;
        }

        .header-section h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .header-section p {
            color: var(--text-muted);
            font-size: 1.1rem;
        }

        .pricing-wrapper {
            display: flex;
            gap: 25px;
            padding: 20px;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
        }

        .price-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 40px 30px;
            text-align: center;
            width: 320px;
            transition: all 0.3s ease;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 450px;
        }

        .price-card:hover {
            /* transform: translateY(-12px); */
            /* border-color: var(--primary); */
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }

        .price-card.featured {
            border-color: var(--primary);
            box-shadow: 0 0 20px rgba(125, 109, 227, 0.15);
        }

        .best-value {
            position: absolute;
            top: -15px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, #ff00e6, #7d6de3);
            color: white;
            padding: 6px 20px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 1px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        }

        .p-duration {
            font-size: 1.25rem;
            color: var(--text-muted);
            font-weight: 500;
            margin-bottom: 15px;
        }

        .p-price {
            font-size: 3.5rem;
            font-weight: 800;
            color: var(--text-main);
            margin-bottom: 15px;
        }

        .p-sub {
            color: var(--text-muted);
            font-size: 0.95rem;
            line-height: 1.5;
            margin-bottom: 30px;
        }

        .p-btn {
            background: #2d2d35;
            color: white;
            padding: 16px 20px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
            display: block;
            border: 1px solid transparent;
        }

        .price-card:hover {
            border-color: var(--primary);
        }

        .price-card:hover .p-btn {
            background: var(--primary);
            box-shadow: 0 8px 15px rgba(125, 109, 227, 0.3);
        }


        .featured .p-btn {
            background: var(--primary);
        }

        .featured .p-btn:hover {
            background: var(--primary-hover);
        }

        .login-footer {
            margin-top: 50px;
            font-size: 1rem;
            color: var(--text-muted);
        }

        .login-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            margin-left: 5px;
            transition: 0.2s;
        }

        .login-footer a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .pricing-wrapper {
                flex-direction: column;
            }

            .price-card {
                width: 100%;
                max-width: 350px;
            }
        }
    </style>
</head>

<body>

    <div class="header-section">
        <h1>Open Your Shop</h1>
        <p>Choose a subscription plan to start selling in our mall.</p>
    </div>

    <div class="pricing-wrapper">

        <div class="price-card">
            <div>
                <div class="p-duration">1 Month</div>
                <div class="p-price">$1,000</div>
                <div class="p-sub">Perfect for short-term pop-up shops or testing your product market.</div>
            </div>
            <a href="supplierRegister.php?duration=1" class="p-btn">Select Plan</a>
        </div>

        <div class="price-card featured">
            <div class="best-value">MOST POPULAR</div>
            <div>
                <div class="p-duration">6 Months</div>
                <div class="p-price">$6,000</div>
                <div class="p-sub">The balanced choice for growing businesses. Secure your spot for the season.</div>
            </div>
            <a href="supplierRegister.php?duration=6" class="p-btn">Select Plan</a>
        </div>

        <div class="price-card">
            <div>
                <div class="p-duration">1 Year</div>
                <div class="p-price">$12,000</div>
                <div class="p-sub">Maximum commitment for established brands. Includes premium placement.</div>
            </div>
            <a href="supplierRegister.php?duration=12" class="p-btn">Select Plan</a>
        </div>

    </div>

    <div class="login-footer">
        Already have an account? <a href="supplierLogin.php">Login here</a>
    </div>

</body>

</html>