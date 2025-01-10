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

// รับ apartment_id จาก session
$apartment_id = $_SESSION['apartment_id'];

// ดึงข้อมูลบัญชีธนาคาร
$sql = "SELECT * FROM bank_accounts WHERE apartment_id = '$apartment_id'";
$result = mysqli_query($con, $sql);

if (!$result) {
    die('Error executing query: ' . mysqli_error($con));
}

// ตรวจสอบว่ามีบัญชีธนาคารหรือไม่
if (mysqli_num_rows($result) == 0) {
    echo "<p style='color:red;'>ไม่มีข้อมูลบัญชีธนาคารเพื่อแก้ไข</p>";
    exit();
}

$bank_account = mysqli_fetch_assoc($result);

// ตรวจสอบการส่งข้อมูลฟอร์ม
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $account_name = mysqli_real_escape_string($con, $_POST['account_name']);
    $account_number = mysqli_real_escape_string($con, $_POST['account_number']);
    $bank_name = mysqli_real_escape_string($con, $_POST['bank_name']);
    $account_type = mysqli_real_escape_string($con, $_POST['account_type']);
    
    // เริ่มต้นด้วยการใช้ค่า qr_code ที่มีอยู่ในฐานข้อมูล
    $qr_code = $bank_account['qr_code'];

    // ตรวจสอบการอัพโหลดไฟล์ QR Code
    if (isset($_FILES['qr_code_file']) && $_FILES['qr_code_file']['error'] == 0) {
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($_FILES["qr_code_file"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // ตรวจสอบประเภทไฟล์
        if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
            echo "ขออภัย, เฉพาะไฟล์ภาพ JPG, JPEG, PNG & GIF เท่านั้นที่อนุญาต";
            exit();
        }

        // อัพโหลดไฟล์
        if (move_uploaded_file($_FILES["qr_code_file"]["tmp_name"], $target_file)) {
            $qr_code = $target_file; // อัพเดท URL ของ QR Code เป็นที่อยู่ของไฟล์ที่อัพโหลด
        } else {
            echo "ขออภัย, เกิดข้อผิดพลาดในการอัปโหลดไฟล์";
            exit();
        }
    }

    // อัปเดตข้อมูลบัญชีธนาคารในฐานข้อมูล
    $update_query = "UPDATE bank_accounts SET account_name='$account_name', account_number='$account_number', bank_name='$bank_name', account_type='$account_type', qr_code='$qr_code' WHERE apartment_id = '$apartment_id'";

    if (mysqli_query($con, $update_query)) {
        // เมื่ออัปเดตเสร็จแล้ว ให้เปลี่ยนไปที่หน้า bank_account.php
        header('Location: bank_account.php');
        exit();
    } else {
        echo "เกิดข้อผิดพลาดในการอัปเดต: " . mysqli_error($con);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขบัญชีธนาคาร</title>
    <script src="https://kit.fontawesome.com/433b24fc29.js" crossorigin="anonymous"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&family=Prompt:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/home.css">
    <link rel="stylesheet" href="assets/css/edit_bank_account.css">
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

<div class="main_content">
    </div>
        <form method="POST" enctype="multipart/form-data">
        <h2>แก้ไขบัญชีธนาคาร</h2>
        <div class="form_group">
            <label for="account_name">ชื่อบัญชี:</label>
            <input type="text" name="account_name" id="account_name" value="<?php echo htmlspecialchars($bank_account['account_name']); ?>" required>
        </div>
        <div class="form_group">
            <label for="account_number">หมายเลขบัญชี:</label>
            <input type="text" name="account_number" id="account_number" value="<?php echo htmlspecialchars($bank_account['account_number']); ?>" required>
        </div>
        <div class="form_group">
            <label for="bank_name">ชื่อธนาคาร:</label>
            <input type="text" name="bank_name" id="bank_name" value="<?php echo htmlspecialchars($bank_account['bank_name']); ?>" required>
        </div>
        <div class="form_group">
            <label for="qr_code">QR Code:</label>
            <img src="<?php echo htmlspecialchars($bank_account['qr_code']); ?>" alt="QR Code" style="max-width: 200px; max-height: 200px;"><br>
            <input type="file" name="qr_code_file" id="qr_code_file" accept="image/*">
        </div>
        <button type="submit">บันทึก</button>
        <a href="bank_account.php">ยกเลิก</a>
    </form>
</div>
</body>
</html>