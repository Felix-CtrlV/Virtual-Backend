<?php
include("../BackEnd/config/dbconfig.php");

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $password = $_POST['password'];
    
    // Hash Password
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    
    // Default Values
    $status = "Active"; 
    $created_at = date('Y-m-d H:i:s');
    $imageName = "default_user.png"; // Default image

    // Handle Image Upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            // Generate unique name
            $imageName = "cust_" . time() . "." . $ext;
            $uploadDir = "assets/customer_profiles/";
            
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            
            move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadDir . $imageName);
        }
    }

    // Insert Query
    $sql = "INSERT INTO customers (name, email, password, phone, address, image, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ssssssss", $name, $email, $hashed, $phone, $address, $imageName, $status, $created_at);
        
        if ($stmt->execute()) {
            header("Location: customerLogin.php?msg=success");
            exit();
        } else {
            $message = "Registration failed: " . $conn->error;
        }
        $stmt->close();
    } else {
        $message = "Database error.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Registration</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/Css/customerAuth.css">
</head>

<body>

    <div class="container">
        
        <div class="left-panel scrollable-panel">
            <h1>Create Account</h1>
            <p class="sub-text">Already a member? <a href="customerLogin.php">Log In</a></p>
            
            <?php if($message): ?>
                <div class="alert-box"><?php echo $message; ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                
                <div class="profile-upload-center">
                    <label for="u_image" class="profile-circle">
                        <img id="prev_image" src="assets/images/default_avatar.png" onerror="this.src='https://via.placeholder.com/150'">
                        <div class="overlay"><i class="fas fa-camera"></i></div>
                    </label>
                    <input type="file" name="profile_image" id="u_image" accept="image/*" onchange="previewImage(this)">
                    <p class="tiny-text">Upload Profile Picture</p> 
                </div>

                <div class="input-group">
                    <input type="text" name="name" placeholder="Full Name" required>
                </div>
                
                <div class="input-group">
                    <input type="email" name="email" placeholder="Email Address" required>
                </div>

                <div class="row-group">
                    <div class="input-group">
                        <input type="text" name="phone" placeholder="Phone Number" required>
                    </div>
                    <div class="input-group">
                        <input type="text" name="address" placeholder="Address" required>
                    </div>
                </div>

                <div class="input-group password-container">
                    <input type="password" id="reg_pass" name="password" placeholder="Create Password" required>
                    <i class="fa-regular fa-eye eye-icon" onclick="togglePass('reg_pass', this)"></i>
                </div>

                <button type="submit" class="submit-btn">Register</button>
            </form>
        </div>

        <div class="right-panel register-visual">
            <div class="logo-icon"><i class="fas fa-shopping-bag"></i></div>
            <div class="quote-box">
                <h2>Join the Revolution<br>of Virtual Shopping.</h2>
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

        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('prev_image').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>

</body>
</html>