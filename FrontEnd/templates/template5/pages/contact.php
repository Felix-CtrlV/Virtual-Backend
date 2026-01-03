<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Our Concierge | MALLTIVERSE Luxury Watches</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;1,400&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-gold: #c5a059;
            --dark-bg: #0a0a0a;
            --input-bg: rgba(255, 255, 255, 0.05);
            --text-gray: #a0a0a0;
        }

        body {
            margin: 0;
            background-color: var(--dark-bg);
            font-family: 'Poppins', sans-serif;
            color: #fff;
        }

        .contact-page {
            padding: 80px 5%;
            max-width: 1200px;
            margin: 0 auto;
        }

        .header-section {
            text-align: center;
            margin-bottom: 60px;
        }

        .header-section h1 {
            font-family: 'Playfair Display', serif;
            font-size: 3rem;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #c5a059 0%, #f1d38e 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .grid-container {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 50px;
        }

        /* Left Side: Info */
        .info-card {
            background: rgba(255, 255, 255, 0.02);
            padding: 40px;
            border-left: 3px solid var(--primary-gold);
        }

        .info-card h2 {
            font-family: 'Playfair Display', serif;
            color: var(--primary-gold);
            margin-bottom: 25px;
        }

        .service-list {
            list-style: none;
            padding: 0;
            margin-bottom: 40px;
        }

        .service-list li {
            margin-bottom: 15px;
            color: var(--text-gray);
            font-size: 14px;
        }

        .service-list i {
            color: var(--primary-gold);
            margin-right: 10px;
        }

        .contact-details div {
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
        }

        .contact-details i {
            margin-top: 5px;
            margin-right: 15px;
            color: var(--primary-gold);
        }

        /* Right Side: Form */
        .contact-form {
            background: rgba(255, 255, 255, 0.03);
            padding: 50px;
            border-radius: 2px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--primary-gold);
            margin-bottom: 8px;
        }

        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            background: var(--input-bg);
            border: 1px solid rgba(197, 160, 89, 0.2);
            padding: 12px;
            color: #fff;
            outline: none;
            font-family: inherit;
            transition: 0.3s;
        }

        .form-group input:focus, .form-group textarea:focus {
            border-color: var(--primary-gold);
            background: rgba(255, 255, 255, 0.08);
        }

        .submit-btn {
            width: 100%;
            padding: 15px;
            background: var(--primary-gold);
            border: none;
            color: #000;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            cursor: pointer;
            transition: 0.4s;
        }

        .submit-btn:hover {
            background: #fff;
            box-shadow: 0 0 20px rgba(197, 160, 89, 0.4);
        }

        @media (max-width: 900px) {
            .grid-container { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <div class="contact-page">
        <div class="header-section">
            <h1>Contact Our Concierge</h1>
            <p style="color: var(--text-gray);">Exceptional service for exceptional timepieces.</p>
        </div>

        <div class="grid-container">
            <div class="info-side">
                <div class="info-card">
                    <h2>VIP Services</h2>
                    <ul class="service-list">
                        <li><i class="fas fa-gem"></i> Private Viewing Appointments</li>
                        <li><i class="fas fa-tools"></i> Professional Watch Restoration</li>
                        <li><i class="fas fa-certificate"></i> Authentication & Appraisal</li>
                        <li><i class="fas fa-truck-moving"></i> Insured Global Shipping</li>
                    </ul>

                    <h2>Our Boutique</h2>
                    <div class="contact-details">
                        <div>
                            <i class="fas fa-map-marker-alt"></i>
                            <span>No. 123, Pyay Road, Kamayut Township,<br>Yangon, Myanmar.</span>
                        </div>
                        <div>
                            <i class="fas fa-clock"></i>
                            <span>Mon - Sat: 10:00 AM - 7:00 PM<br>Sunday: By Appointment Only</span>
                        </div>
                        <div>
                            <i class="fas fa-phone-alt"></i>
                            <span>+95 9 123 456 789</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="contact-form">
                <form action="#" method="POST">
                    <div class="form-group">
                        <label>Your Name</label>
                        <input type="text" placeholder="Enter your full name" required>
                    </div>

                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" placeholder="email@example.com" required>
                    </div>

                    <div class="form-group">
                        <label>Service Type</label>
                        <select>
                            <option>General Inquiry</option>
                            <option>Book a Private Viewing</option>
                            <option>Maintenance & Repair</option>
                            <option>Authentication Service</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Message</label>
                        <textarea rows="5" placeholder="How can our consultants assist you?"></textarea>
                    </div>

                    <button type="submit" class="submit-btn">Send Inquiry</button>
                </form>
            </div>
        </div>
    </div>

</body>
</html>