<?php
session_start();
include('server.php');

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบหรือไม่
if (!isset($_SESSION['username'])) {
    header('location: index.php');
    exit();
}

// ดึงข้อมูลผู้ใช้ที่เข้าสู่ระบบ
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

// ดึงข้อมูลใบเสร็จตาม ID ที่ได้รับจาก URL
if (!isset($_GET['id'])) {
    die('ไม่พบ ID ใบเสร็จ');
}

$receipt_id = intval($_GET['id']);

// ดึงข้อมูลใบเสร็จจากฐานข้อมูล
$receipt_query = "SELECT * FROM receipts WHERE id = ?";
$stmt = $con->prepare($receipt_query);
$stmt->bind_param('i', $receipt_id);
$stmt->execute();
$receipt_result = $stmt->get_result();

if ($receipt_result->num_rows === 0) {
    die('ไม่พบข้อมูลใบเสร็จ');
}

$receipt = $receipt_result->fetch_assoc();

// ตรวจสอบว่าสลิปการโอนเงินถูกอัปโหลดแล้วหรือไม่
$check_slip_query = "SELECT * FROM payment_slips WHERE receipt_id = ?";
$check_slip_stmt = $con->prepare($check_slip_query);
$check_slip_stmt->bind_param('i', $receipt_id);
$check_slip_stmt->execute();
$check_slip_result = $check_slip_stmt->get_result();

$slip_uploaded = $check_slip_result->num_rows > 0;

// ดึงข้อมูลบัญชีธนาคารของเจ้าของหอที่ตรงกับ apartment_id
$apartment_id = $receipt['apartment_id'];

$bank_query = "SELECT * FROM bank_accounts WHERE apartment_id = ?";
$bank_stmt = $con->prepare($bank_query);
$bank_stmt->bind_param('i', $apartment_id);
$bank_stmt->execute();
$bank_result = $bank_stmt->get_result();

if ($bank_result->num_rows === 0) {
    die('ไม่พบข้อมูลบัญชีธนาคาร');
}

$bank_account = $bank_result->fetch_assoc();

// ดึงข้อมูลหอพักจาก apartment_id
$apartment_query = "SELECT * FROM apartments WHERE id = ?";
$apartment_stmt = $con->prepare($apartment_query);
$apartment_stmt->bind_param('i', $apartment_id);
$apartment_stmt->execute();
$apartment_result = $apartment_stmt->get_result();

if ($apartment_result->num_rows === 0) {
    die('ไม่พบข้อมูลหอพัก');
}

$apartment = $apartment_result->fetch_assoc();

// แสดง QR Code จากข้อมูลบัญชีธนาคารที่ดึงมา
$qr_code_url = htmlspecialchars($bank_account['qr_code']); 

// สร้างตัวแปรสำหรับการแจ้งเตือน
$notification_shown = false;

// ตรวจสอบการอัปโหลดสลิปการโอนเงิน
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['slip'])) {
    if ($slip_uploaded) {
        if (!$notification_shown) {
        }
    } else {
        $target_dir = "uploads/"; // โฟลเดอร์ที่เก็บสลิป
        $target_file = $target_dir . basename($_FILES["slip"]["name"]);
        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // ตรวจสอบข้อผิดพลาดในการอัปโหลด
        if ($_FILES["slip"]["error"] !== UPLOAD_ERR_OK) {
            $uploadOk = 0;
            if (!$notification_shown) {
                echo '<script>
                    setTimeout(function() {
                        swal({
                            title: "เกิดข้อผิดพลาดในการอัปโหลดไฟล์.",
                            type: "error"
                        });
                    }, 1000);
                </script>';
                $notification_shown = true;
            }
        }

        // ตรวจสอบว่าไฟล์เป็นภาพหรือไม่
        $check = getimagesize($_FILES["slip"]["tmp_name"]);
        if ($check === false) {
            $uploadOk = 0;
        }

        // ตรวจสอบขนาดไฟล์
        if ($_FILES["slip"]["size"] > 500000) { // จำกัดขนาดไฟล์ที่ 500KB
            $uploadOk = 0;
        }

        // อนุญาตเฉพาะไฟล์รูปภาพที่กำหนด
        if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
            $uploadOk = 0;
        }

        // ตรวจสอบว่า $uploadOk ถูกตั้งค่าเป็น 0 โดยมีข้อผิดพลาด
        if ($uploadOk == 0) {
            // แจ้งเตือนข้อผิดพลาดด้วย SweetAlert
            if (!$notification_shown) {
                echo '<script>
                    setTimeout(function() {
                        swal({
                            title: "ไม่สามารถอัปโหลดไฟล์ของคุณได้.",
                            type: "error"
                        });
                    }, 1000);
                </script>';
                $notification_shown = true;
            }
        } else {
           // พยายามอัปโหลดไฟล์
if (move_uploaded_file($_FILES["slip"]["tmp_name"], $target_file)) {
    // บันทึกข้อมูลสลิปการโอนในฐานข้อมูล
    $slip_query = "INSERT INTO payment_slips (receipt_id, slip_image) VALUES (?, ?)";
    $slip_stmt = $con->prepare($slip_query);
    $slip_stmt->bind_param('is', $receipt_id, $target_file);
    
    if ($slip_stmt->execute()) {
        // อัปเดตสถานะในตาราง receipts
        $update_status_query = "UPDATE receipts SET status = 'paid' WHERE id = ?";
        $update_status_stmt = $con->prepare($update_status_query);
        $update_status_stmt->bind_param('i', $receipt_id);
        $update_status_stmt->execute();

        // เด้งกลับไปยังหน้า renter_check_bill.php
        header('Location: renter_check_bill.php');
        exit();
    }
}
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ชำระเงิน</title>
    <script src="https://code.jquery.com/jquery-2.1.3.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert-dev.js"></script>
    <link rel="stylesheet" href="assets/css/SweetAlert.css">
    <script src="https://kit.fontawesome.com/433b24fc29.js" crossorigin="anonymous"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&family=Prompt:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/home.css">
    <link rel="stylesheet" href="assets/css/payment_page.css">
    <link rel="stylesheet" href="assets/css/dropdown.css">
    <script src="dropdown.js"></script>
    <script src="assets/js/menu.js"></script>
    <script>
        // ฟังก์ชันแสดงรูปใหญ่เมื่อคลิก
        function showImage(src) {
            const modal = document.getElementById("imageModal");
            const modalImg = document.getElementById("modalImg");
            modal.style.display = "block";
            modalImg.src = src;
        }

        // ฟังก์ชันปิด modal
        function closeModal() {
            const modal = document.getElementById("imageModal");
            modal.style.display = "none";
        }

        window.onload = function() {
            const modal = document.getElementById("imageModal");
            const span = document.getElementsByClassName("close")[0];

            span.onclick = function() {
                closeModal();
            }

            window.onclick = function(event) {
                if (event.target == modal) {
                    closeModal();
                }
            }
        }
    </script>
</head>
<body>

<div class="container">
<form method="POST" action="" enctype="multipart/form-data">
    <h1>ชำระเงินสำหรับใบเสร็จหมายเลข <?php echo htmlspecialchars($receipt['id']); ?></h1>
    <h2>ยอดรวม: <?php echo htmlspecialchars(number_format($receipt['total_cost'], 2)); ?> บาท</h2>
    
    <!-- แสดงข้อมูลบัญชีธนาคาร -->
    <h3>ชื่อบัญชี: <?php echo htmlspecialchars($bank_account['account_name']); ?></h3>
    <h3>เลขที่บัญชี: <?php echo htmlspecialchars($bank_account['account_number']); ?></h3>
    <h3>ธนาคาร: <?php echo htmlspecialchars($bank_account['bank_name']); ?></h3>
    
    <!-- แสดงข้อมูลหอพัก -->
    <h3>ชื่อหอพัก: <?php echo htmlspecialchars($apartment['name']); ?></h3>
    
        <!-- แสดง QR Code ของบัญชีธนาคาร -->
    <div class="qr-code">
        <img src="<?php echo $qr_code_url; ?>" alt="QR Code for Payment" onclick="showImage('<?php echo $qr_code_url; ?>')" style="cursor: pointer;">
    </div>

    <!-- Modal สำหรับแสดงรูปใหญ่ -->
    <div id="imageModal" class="modal">
        <span class="close">&times;</span>
        <img class="modal-content" id="modalImg">
    </div>
    
    <p>กรุณาสแกน QR code ด้านบนเพื่อทำการชำระเงิน</p>
    
    <!-- ฟอร์มอัปโหลดสลิปการโอนเงิน -->
    <form action="" method="post" enctype="multipart/form-data">
        <label for="slip">อัปโหลดสลิปการโอนเงิน:</label>
        <input type="file" name="slip" accept="image/*" required>
        <button type="submit">อัปโหลด</button>
        <a href="renter_check_bill.php">ยกเลิก</a>
    </form>
</form>
</div>
</body>
</html>