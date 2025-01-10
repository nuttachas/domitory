<?php
session_start();
include('server.php');

if (isset($_POST['reset_password'])) {
    $username = $_POST['username'];
    
    // ตรวจสอบว่าชื่อผู้ใช้มีอยู่ในฐานข้อมูล
    $query = "SELECT * FROM users WHERE username='$username' LIMIT 1"; // ปรับให้ตรงกับชื่อตารางและคอลัมน์ในฐานข้อมูล
    $result = mysqli_query($con, $query);
    
    if (mysqli_num_rows($result) > 0) {
        // การสร้างลิงค์รีเซ็ตรหัสผ่านที่ส่งไปยังอีเมล (สามารถปรับให้เหมาะสมได้)
        $token = bin2hex(random_bytes(50)); // สุ่ม token
        $query = "UPDATE users SET reset_token='$token' WHERE username='$username'";
        mysqli_query($con, $query);
        
        // ส่งอีเมล (ใช้ PHPMailer หรือ mail() ของ PHP)
        // ตัวอย่างการส่งอีเมล (จะต้องปรับให้เข้ากับระบบส่งอีเมลที่คุณใช้)
        $to = "user@example.com"; // เปลี่ยนให้เป็นอีเมลจริงจากฐานข้อมูล
        $subject = "Reset Password";
        $message = "Click this link to reset your password: http://yourdomain.com/reset_password.php?token=$token";
        mail($to, $subject, $message);

        $_SESSION['message'] = "ลิงค์สำหรับรีเซ็ตรหัสผ่านได้ถูกส่งไปยังอีเมลของคุณแล้ว";
        header('location: login.php');
        exit();
    } else {
        $_SESSION['error'] = "ชื่อผู้ใช้ไม่ถูกต้อง";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลืมรหัสผ่าน</title>
    <link rel="stylesheet" href="assets/css/login.css">
</head>

<body>
    <form action="forgotpw.php" method="POST">
        <h1>ลืมรหัสผ่าน</h1>
        
        <?php if (isset($_SESSION['error'])) : ?>
            <div class="error">
                <h3><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></h3>
            </div>
        <?php endif ?>
        
        <label for="username">ชื่อผู้ใช้</label>
        <br>
        <input id="username" name="username" type="text" placeholder="ชื่อผู้ใช้" required/>
        <br><br>

        <button type="submit" name="reset_password">ส่งลิงค์รีเซ็ตรหัสผ่าน</button>
        <br><br>

        <button type="button" onclick="window.location.href='login.php'">กลับไปที่หน้าเข้าสู่ระบบ</button>
    </form>
</body>
</html>