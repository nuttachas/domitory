<?php
    session_start();
    include('server.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="assets/css/login.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&family=Noto+Sans+Thai:wght@300&family=Prompt:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">
</head>
<body>
    <form action="login_db.php" method="POST">
        <?php if (isset($_SESSION['error'])) : ?>
            <div class="error">
                <h3>
                    <?php
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                    ?>
                </h3>
            </div>
        <?php endif ?>

        <!-- Add the message section here -->
        <?php if (isset($_SESSION['message'])) : ?>
            <div class="message">
                <h3><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></h3>
            </div>
        <?php endif ?>

        <h1>Login</h1>
        <br><br>
        
        <label for="username">ชื่อผู้ใช้</label>
        <br>
        <input id="username" name="username" type="text" placeholder="ชื่อผู้ใช้" required/>
        <br>

        <label for="password">รหัสผ่าน</label>
        <br>
        <label class="password-container">
            <input id="password" name="password" type="password" placeholder="รหัสผ่าน" required/>
            <a href="forgotpw.php" class="forgot-password">ลืมรหัสผ่าน</a><br>
        </label>
        <br><br>

        <button type="submit" name="login_user">Login</button>
        <h2>or</h2>
        <button type="button" onclick="window.location.href='register.php'">Register</button>
    </form>
</body>
</html>
