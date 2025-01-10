<?php
session_start();
include('server.php');

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบหรือไม่
if (!isset($_SESSION['username'])) {
    header('location: index.php');
    exit();
}

// ตรวจสอบว่าผู้ใช้ได้เลือกหอพักหรือไม่
if (!isset($_SESSION['apartment_id'])) {
    echo "<p style='color:red;'>กรุณาเลือกหอพักก่อนจัดการข้อมูล</p>";
    exit();
}

// ดึงข้อมูลของผู้ใช้ที่ล็อกอิน
$username = mysqli_real_escape_string($con, $_SESSION['username']);
$user_query = "SELECT user_id, username, role FROM users WHERE username = '$username' LIMIT 1";
$user_result = mysqli_query($con, $user_query);

if (!$user_result) {
    die('Error executing query: ' . mysqli_error($con));
}

if (mysqli_num_rows($user_result) == 0) {
    die('User not found.');
}

$user = mysqli_fetch_assoc($user_result);
$user_id = $user['user_id'];

// รับ room_id จาก URL
if (isset($_GET['room_id'])) {
    $room_id = (int)$_GET['room_id'];

    // ดึงข้อมูลห้องที่ต้องการแก้ไข
    $room_query = "SELECT * FROM rooms WHERE id = $room_id AND apartment_id = {$_SESSION['apartment_id']}";
    $room_result = mysqli_query($con, $room_query);

    if (!$room_result) {
        die('Error executing query: ' . mysqli_error($con));
    }

    if (mysqli_num_rows($room_result) == 0) {
        die('ห้องไม่พบในฐานข้อมูล');
    }

    $room = mysqli_fetch_assoc($room_result);
} else {
    die('ไม่พบข้อมูลห้องที่จะแก้ไข');
}

// ตรวจสอบการอัปเดตข้อมูลห้อง
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roomNumber = mysqli_real_escape_string($con, $_POST['roomNumber']);
    $status = mysqli_real_escape_string($con, $_POST['status']);
    $floorNumber = (int)$_POST['floorNumber'];
    $renterCount = (int)$_POST['renterCount'];

    // SQL Update สำหรับการแก้ไขข้อมูลห้อง
    $sql = "UPDATE rooms SET room_number='$roomNumber', status='$status', floor_number=$floorNumber, renter_count=$renterCount WHERE id=$room_id";

    if (mysqli_query($con, $sql)) {
        $success_message = "แก้ไขข้อมูลสำเร็จ";
    } else {
        $error_message = "เกิดข้อผิดพลาดในการแก้ไขข้อมูลห้อง: " . mysqli_error($con);
    }
}

mysqli_close($con);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขข้อมูลห้อง</title>
    <script src="https://code.jquery.com/jquery-2.1.3.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert-dev.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.css">
    <link rel="stylesheet" href="assets/css/SweetAlert.css">
    <script src="https://kit.fontawesome.com/433b24fc29.js" crossorigin="anonymous"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@100;200;300;400;500;600;700;800;900&family=Prompt:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/home.css">
    <link rel="stylesheet" href="assets/css/edit_room.css">
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

    <div class="container">
        <form method="POST">
        <h1>แก้ไขข้อมูลห้อง</h1>
        <?php if (isset($error_message)): ?>
            <p style="color:red;"><?php echo $error_message; ?></p>
        <?php endif; ?>
        <form method="POST">
            <label for="roomNumber">หมายเลขห้อง:</label>
            <input type="text" id="roomNumber" name="roomNumber" value="<?php echo htmlspecialchars($room['room_number']); ?>" placeholder="หมายเลขห้อง" required>
            
            <label for="status">สถานะ:</label>
            <select name="status" required>
                <option value="vacant" <?php echo ($room['status'] === 'vacant') ? 'selected' : ''; ?>>ว่าง</option>
                <option value="occupied" <?php echo ($room['status'] === 'occupied') ? 'selected' : ''; ?>>ไม่ว่าง</option>
            </select>

            <label for="floorNumber">ชั้น:</label>
            <input type="number" id="floorNumber" name="floorNumber" value="<?php echo $room['floor_number']; ?>" placeholder="ชั้น" required>
            
            <label for="renterCount">จำนวนผู้เช่า:</label>
            <input type="number" id="renterCount" name="renterCount" value="<?php echo $room['renter_count']; ?>" placeholder="จำนวนผู้เช่า" required>
            
            <button type="submit">บันทึกการเปลี่ยนแปลง</button>
            <a href="roomlayout.php">ยกเลิก</a>
        </form>

        <?php if (isset($success_message)): ?>
            <script>
                setTimeout(function() {
                    swal({
                        title: "<?php echo $success_message; ?>",
                        type: "success"
                    }, function() {
                        window.location = "roomlayout.php"; // หน้าที่ต้องการให้กระโดดไป
                    });
                }, 1000);
            </script>
        <?php endif; ?>
    </div>
</body>
</html>