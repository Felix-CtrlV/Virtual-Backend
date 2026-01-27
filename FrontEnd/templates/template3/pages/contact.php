<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us | Brandflow Agency</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
</head>
<body>
    <section class="contact-wrapper">
        <div class="section-header">
            <h1>CONTACT INFO</h1>
            <div class="gold-divider"></div>
            <p>Might that from set to her it reflection design attention happened refute. Support have rattling from commas, can dense, of magicians rationale.</p>
        </div>

        <div class="info-grid">
            <div class="side-title">
                <div class="title-wrapper">
                    <h2>
                        <span class="light-text">GET IN</span> 
                        <span class="bold-text">TOUCH</span>
                    </h2>
                    <div class="decorative-line"></div>
                </div>
            </div>

            <div class="cards-container">
                <div class="info-box">
                    <div class="icon-wrapper">
                        <i class="fa-solid fa-location-dot"></i>
                    </div>
                    <h3>Shop Location</h3>
                    <p>Ward 7, Pyay Road, Kamaryut Township, Yangon</p>
                    <a href="#" class="link">Direction <i class="fa-solid fa-arrow-right"></i></a>
                </div>

                <div class="info-box">
                    <div class="icon-wrapper">
                        <i class="fa-solid fa-calendar-days"></i>
                    </div>
                    <h3>Working Hours</h3>
                    <p>Sun to Fri: 10am to 6pm<br>Sat: 10am to 2pm</p>
                    <a href="#" class="link">Learn more <i class="fa-solid fa-arrow-right"></i></a>
                </div>

                <div class="info-box">
                    <div class="icon-wrapper">
                        <i class="fa-solid fa-comments"></i>
                    </div>
                    <h3>Communication</h3>
                    <p>+95933445577<br>adora@gmail.com</p>
                    <a href="#" class="link">Support <i class="fa-solid fa-arrow-right"></i></a>
                </div>
            </div>
        </div>

        <div class="form-outer-box">
            <div class="form-flex">
                <div class="form-inputs animate-left">
                    <form action="contact.php" method="POST">
                        <div class="input-group">
                            <input type="text" name="name" placeholder="What's your Name?" required>
                            <span class="focus-border"></span>
                        </div>
                        <div class="input-group">
                            <input type="email" name="email" placeholder="Your Email" required>
                            <span class="focus-border"></span>
                        </div>
                        <div class="input-group">
                            <textarea name="message" rows="4" placeholder="How can we help you?" required></textarea>
                            <span class="focus-border"></span>
                        </div>
                        <button type="submit" name="submit" class="submit-btn">
                            <span>Send Message</span>
                            <i class="fa-solid fa-paper-plane"></i>
                        </button>
                    </form>
                </div>

                <div class="form-info-text animate-right">
                    <p class="query-tag">HAVE ANY QUERY?</p>
                    <h2>CONTACT <span class="accent">US</span></h2>
                    <p class="description">We’re here to help! Send us a message and our team will get back to you within 24 hours.</p>
                    <div class="social-links">
                        <i class="fab fa-facebook-f"></i>
                        <i class="fab fa-instagram"></i>
                        <i class="fab fa-linkedin-in"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php
    if(isset($_POST['submit'])){
        $name = htmlspecialchars($_POST['name']);
        $email = htmlspecialchars($_POST['email']);
        $message = htmlspecialchars($_POST['message']);
        
        // Logic for sending email would go here
        echo "<script>alert('Thank you, $name! Your message has been sent.');</script>";
    }
    ?>
</body>
</html>