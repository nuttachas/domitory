<?php
session_start();
include('server.php');

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบหรือไม่
if (!isset($_SESSION['username'])) {
    header('location: index.php');
    exit();
}

// ดึงข้อมูลของผู้ใช้ที่ล็อกอิน
$username = mysqli_real_escape_string($con, $_SESSION['username']);
$user_query = "SELECT user_id, username FROM users WHERE username = '$username' LIMIT 1";
$user_result = mysqli_query($con, $user_query);

if (!$user_result) {
    die('Error executing query: ' . mysqli_error($con));
}

if (mysqli_num_rows($user_result) == 0) {
    die('User not found.');
}

$user = mysqli_fetch_assoc($user_result);
$user_id = $user['user_id'];

// รับ apartment_id จาก session
if (!isset($_SESSION['apartment_id'])) {
    echo "<p style='color:red;'>กรุณาเลือกหอพักก่อนจัดการข้อมูล</p>";
    exit();
}

// ตรวจสอบการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // รับค่าจากฟอร์ม
    $account_name = $_POST['account_name'];
    $account_number = $_POST['account_number'];
    $bank_name = $_POST['bank_name'];
    $account_type = $_POST['account_type'];
    
    // รับ apartment_id จาก session
    $apartment_id = $_SESSION['apartment_id'];

    // อัปโหลดภาพ QR Code
    $target_dir = "uploads/";
    $file_name = basename($_FILES["qr_code"]["name"]);
    $target_file = $target_dir . uniqid() . '_' . $file_name; // สร้างชื่อไฟล์ใหม่

    // ตรวจสอบว่าไดเรกทอรีมีอยู่จริง
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true); // สร้างไดเรกทอรีถ้าไม่มี
    }

    // ย้ายไฟล์
    if (move_uploaded_file($_FILES["qr_code"]["tmp_name"], $target_file)) {
        // บันทึกข้อมูลในฐานข้อมูล
        $sql = "INSERT INTO bank_accounts (account_name, account_number, bank_name, account_type, qr_code, apartment_id) 
                VALUES ('$account_name', '$account_number', '$bank_name', '$account_type', '$target_file', '$apartment_id')";
    
        if (mysqli_query($con, $sql)) {
            // แสดง SweetAlert เมื่อสำเร็จ
            echo '
                <script src="https://code.jquery.com/jquery-2.1.3.min.js"></script>
                <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert-dev.js"></script>
                <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.css">
                <link rel="stylesheet" href="assets/css/SweetAlert.css">
                <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
                <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
                <link href="https://fonts.googleapis.com/css2?family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&family=Prompt:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">
                <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">';
            echo '<script>
                setTimeout(function() {
                    swal({
                        title: "บัญชีธนาคารถูกเพิ่มเรียบร้อยแล้ว!",
                        type: "success"
                    }, function() {
                       window.location = "bank_account.php"; // หน้าที่ต้องการให้กระโดดไป
                    });
                }, 1000);
            </script>';
        } else {
            // แสดง SweetAlert เมื่อเกิดข้อผิดพลาด
            echo '<script>
                setTimeout(function() {
                    swal({
                        title: "เกิดข้อผิดพลาดในการเพิ่มบัญชีธนาคาร",
                        type: "error"
                    }, function() {
                       window.location = "bank_account.php"; // หน้าที่ต้องการให้กระโดดไป
                    });
                }, 1000);
            </script>';
        }
    } else {
        echo "เกิดข้อผิดพลาดในการอัปโหลดไฟล์";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มผู้ใช้งาน</title>
    <script src="https://kit.fontawesome.com/433b24fc29.js" crossorigin="anonymous"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&family=Prompt:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/home.css">
    <link rel="stylesheet" href="assets/css/add_bank_account.css">
    <link rel="stylesheet" href="assets/css/dropdown.css">
    <script src="dropdown.js"></script>
    <script src="assets/js/menu.js"></script>
</head>
<body>
<div class="sidebar">
    <div class="sidebar_header">
        <i class="fa-solid fa-building-user fa-2x"></i>
    </div>
    <div class="sidebar_menu">
        <a href="selectrole.php" class="sidebar_item">
            <div class="sidebar_icon">
                <i class="fa-solid fa-house"></i>
            </div>
            <h4>หน้าหลัก</h4>
        </a>
        <a href="roomlayout.php" class="sidebar_item">
            <div class="sidebar_icon">
                <i class="fa-solid fa-grip"></i>
            </div>
            <h4>ผังห้อง</h4>
        </a>
        <a href="servicerates.php" class="sidebar_item">
            <div class="sidebar_icon">
                <i class="fa-solid fa-gears"></i>
            </div>
            <h4>ตั้งค่าหน่วยบริการ</h4>
        </a>
        <a href="billing_statement.php" class="sidebar_item">
            <div class="sidebar_icon">
                <i class="fa-solid fa-file-pen"></i>
            </div>
            <h4>สร้างบิลค่าเช่า</h4>
        </a>
        <a href="check_receipt.php" class="sidebar_item">
            <div class="sidebar_icon">
                <i class="fa-solid fa-file-import"></i>
            </div>
            <h4>แจ้งบิลค่าเช่า</h4>
        </a>
        <a href="billing_history.php" class="sidebar_item">
            <div class="sidebar_icon">
                <i class="fa-solid fa-clock"></i>
            </div>
            <h4>ประวัติบิลค่าเช่า</h4>
        </a>
        <a href="manage.php" class="sidebar_item">
            <div class="sidebar_icon">
                <i class="fa-solid fa-users-gear"></i>
            </div>
            <h4>จัดการผู้ใช้งาน</h4>
        </a>
        <a href="bank_account.php" class="sidebar_item">
            <div class="sidebar_icon">
                <i class="fa-solid fa-money-check-dollar"></i>
            </div>
            <h4>บัญชีธนาคาร</h4>
        </a>
        <a href="check_payment_slip.php" class="sidebar_item">
            <div class="sidebar_icon">
                <i class="fa-solid fa-receipt"></i>
            </div>
            <h4>ตรวจสอบสลิป</h4>
        </a>
        <a href="logout.php" class="sidebar_item">
            <div class="sidebar_icon">
                <i class="fa-solid fa-right-from-bracket"></i>
            </div>
            <h4>ออกจากระบบ</h4>
        </a>
    </div>
</div>

<div class="container">
    <header class="header">
        <button class="btn-toggle"><i class="fa-solid fa-bars"></i></button>
        <nav>
            <a href="selectrole.php" class="logo"></a>
            <ul>
                <li class="dropdown">
                    <div class="dropbtn">
                        <i class="fa-solid fa-circle-user fa-2x"></i>
                    </div>
                    <div class="dropdown-content">
                        <a href="#">
                            <p><?php echo htmlspecialchars($user['username']); ?></p>
                        </a>
                        <a href="edit-profile.php">
                            <i class="fa-solid fa-user-pen"></i>
                            <p>แก้ไขข้อมูลส่วนตัว</p>
                        </a>
                        <a href="logout.php">
                            <i class="fa-solid fa-right-from-bracket"></i>
                            <p>ออกจากระบบ</p>
                        </a>
                    </div>
                </li>
            </ul>
        </nav>
    </header>

    <form action="add_bank_account.php" method="post" enctype="multipart/form-data">
        <h1>เพิ่มบัญชีธนาคาร</h1>
        
        <label for="account_name">ชื่อบัญชี:</label>
        <input type="text" id="account_name" name="account_name" required>

        <label for="account_number">หมายเลขบัญชี:</label>
        <input type="text" id="account_number" name="account_number" required>

        <label for="bank_name">ชื่อธนาคาร:</label>
        <input type="text" id="bank_name" name="bank_name" required>

        <label for="qr_code">อัปโหลด QR Code:</label>
        <input type="file" id="qr_code" name="qr_code" accept="image/*" required>

        <button type="submit">เพิ่มบัญชีธนาคาร</button>
    </form>
</div>
</body>
</html>