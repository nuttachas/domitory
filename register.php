<?php
session_start();
include('server.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="assets/css/register.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&family=Prompt:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">
</head>
<body>
    <form action="register_db.php" method="post">
        <h1>สมัครสมาชิก</h1>

        <!-- แสดงผลข้อผิดพลาด -->
        <?php if (isset($_SESSION['error'])) : ?>
            <div class="error">
                <h3><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></h3>
            </div>
        <?php endif ?>
        
        <div class="input-group2">
            <div class="input">
                <label for="firstname">ชื่อจริง</label>
                <input type="text" name="firstname" placeholder="ชื่อจริง">
            </div>

            <div class="input">
                <label for="lastname">นามสกุล</label>
                <input type="text" name="lastname" placeholder="นามสกุล">
            </div>
        </div>
    
        <div class="input-group">
            <label for="email">อีเมล</label>
            <input type="email" name="email" placeholder="อีเมล">
        </div>

        <div class="input-group">
            <label for="phone">เบอร์โทรศัพท์</label>
            <input type="tel" name="phone" placeholder="xxx-xxx-xxxx">
        </div>

        <div class="input-group">
            <label for="username">ชื่อผู้ใช้</label>
            <input type="text" name="username" placeholder="ชื่อผู้ใช้">
        </div>
        
        <div class="input-group2">
        <div class="input">
            <label for="password_1">รหัสผ่าน</label>
            <input type="password" name="password_1" placeholder="รหัสผ่าน">
        </div>

        <div class="input">
            <label for="password_2">ยืนยันรหัสผ่าน</label>
            <input type="password" name="password_2" placeholder="ยืนยันรหัสผ่าน">
        </div>
        </div>

        <div class="input-group">
            <label for="role">ประเภทผู้ใช้</label>
            <select name="role" id="role">
                <option value="owner">เจ้าของหอพัก</option>
                <option value="renter">ผู้เช่า</option>
            </select>
        </div>
        
        <div class="input-group">
            <button type="submit" name="reg_user" class="btn">ลงทะเบียน</button>
        </div>
    </form>
</body>
</html>
