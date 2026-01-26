<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Duration</title>
    <link rel="stylesheet" href="assets/Css/supplierregister.css">
    <style>
        .pricing-wrapper {
            display: flex;
            gap: 30px;
            padding: 50px;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .price-card {
            background: #24242a;
            border: 1px solid #3e3e46;
            border-radius: 16px;
            padding: 40px 30px;
            text-align: center;
            width: 300px;
            transition: 0.3s;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 400px;
        }

        .price-card:hover {
            transform: translateY(-10px);
            border-color: #7d6de3;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }

        .p-duration { font-size: 24px; color: #fff; font-weight: 600; margin-bottom: 10px; }
        .p-price { font-size: 42px; color: #7d6de3; font-weight: bold; margin-bottom: 20px; }
        .p-sub { color: #888; font-size: 14px; margin-bottom: 30px; }
        
        .p-btn {
            background: transparent;
            border: 1px solid #7d6de3;
            color: #fff;
            padding: 15px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
            display: block;
        }

        .p-btn:hover { background: #7d6de3; }

        .best-value {
            position: absolute;
            top: -12px; left: 50%;
            transform: translateX(-50%);
            background: #ff00e6;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <div class="pricing-wrapper">
        
        <div class="price-card">
            <div>
                <div class="p-duration">1 Month</div>
                <div class="p-price">$1,000</div>
                <div class="p-sub">Standard access for one month.</div>
            </div>
            <a href="supplierRegister.php?duration=1" class="p-btn">Select Plan</a>
        </div>

        <div class="price-card" style="border-color: #7d6de3;">
            <div class="best-value">BEST VALUE</div>
            <div>
                <div class="p-duration">6 Months</div>
                <div class="p-price">$6,000</div>
                <div class="p-sub">Half-year commitment.</div>
            </div>
            <a href="supplierRegister.php?duration=6" class="p-btn"  style="background: #7d6de3;">Select Plan</a>
        </div>

        <div class="price-card">
            <div>
                <div class="p-duration">12 Months</div>
                <div class="p-price">$12,000</div>
                <div class="p-sub">Full year access with priority support.</div>
            </div>
            <a href="supplierRegister.php?duration=12" class="p-btn">Select Plan</a>
        </div>

    </div>

</body>
</html>