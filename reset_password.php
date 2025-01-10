<?php
session_start();
include('server.php');

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // ตรวจสอบ token ในฐานข้อมูล
    $query = "SELECT * FROM users WHERE reset_token='$token' LIMIT 1";
    $result = mysqli_query($con, $query);
    
    if (mysqli_num_rows($result) > 0) {
        if (isset($_POST['update_password'])) {
            $new_password = $_POST['password'];
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT); // แฮชรหัสผ่าน
        
            // ใช้ prepared statement เพื่อป้องกัน SQL Injection
            $stmt = $con->prepare("UPDATE users SET password=?, reset_token=NULL WHERE reset_token=?");
            $stmt->bind_param("ss", $hashed_password, $token);
            $stmt->execute();
            $stmt->close();
            
            $_SESSION['message'] = "รหัสผ่านของคุณถูกรีเซ็ตเรียบร้อยแล้ว";
            header('location: login.php');
            exit();
        }
        
    } else {
        $_SESSION['error'] = "Token ไม่ถูกต้องหรือหมดอายุ";
    }
} else {
    $_SESSION['error'] = "ไม่มี token ที่ระบุ";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รีเซ็ตรหัสผ่าน</title>
    <link rel="stylesheet" href="assets/css/login.css">
</head>

<body>
    <form action="reset_password.php?token=<?php echo $_GET['token']; ?>" method="POST">
        <h1>รีเซ็ตรหัสผ่าน</h1>
        
        <?php if (isset($_SESSION['error'])) : ?>
            <div class="error">
                <h3><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></h3>
            </div>
        <?php endif ?>
        
        <label for="password">รหัสผ่านใหม่</label>
        <br>
        <input id="password" name="password" type="password" placeholder="รหัสผ่านใหม่" required/>
        <br><br>

        <button type="submit" name="update_password">อัปเดตรหัสผ่าน</button>
        <br><br>

        <button type="button" onclick="window.location.href='login.php'">กลับไปที่หน้าเข้าสู่ระบบ</button>
    </form>
</body>
</html>