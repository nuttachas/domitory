<?php
session_start();
include('server.php');

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบหรือไม่
if (!isset($_SESSION['username'])) {
    header('location: index.php');
    exit();
}

// ตรวจสอบว่ามี apartment_id ในคำขอ POST หรือไม่
if (!isset($_POST['apartment_id'])) {
    $_SESSION['error'] = 'ไม่พบข้อมูลการเชื่อมต่อหอพักที่ต้องการลบ';
    header('location: selectrole.php');
    exit();
}

// รับ apartment_id จากฟอร์ม
$apartment_id = $_POST['apartment_id'];

// ดึงข้อมูลของผู้ใช้ที่ล็อกอิน
$username = mysqli_real_escape_string($con, $_SESSION['username']);
$user_query = "SELECT user_id FROM users WHERE username = '$username' LIMIT 1";
$user_result = mysqli_query($con, $user_query);
$user = mysqli_fetch_assoc($user_result);

$user_id = $user['user_id'];

// ลบข้อมูลผู้เช่าที่เชื่อมต่อกับหอพัก
$delete_query = "DELETE FROM renter WHERE apartment_id = ? AND user_id = ?";
$stmt = mysqli_prepare($con, $delete_query);

if (!$stmt) {
    die('Error preparing statement: ' . mysqli_error($con));
}

mysqli_stmt_bind_param($stmt, 'ii', $apartment_id, $user_id);

// เพิ่ม SweetAlert
echo '
    <script src="https://code.jquery.com/jquery-2.1.3.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert-dev.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.css">';

if (mysqli_stmt_execute($stmt)) {
    echo '<script>
        setTimeout(function() {
            swal({
                title: "ลบข้อมูลสำเร็จ",
                type: "success"
            }, function() {
               window.location = "selectrole.php"; // หน้าที่ต้องการให้กระโดดไป
            });
        }, 1000);
    </script>';
} else {
    echo '<script>
        setTimeout(function() {
        swal({
            title: "เกิดข้อผิดพลาด",
            type: "error"
        }, function() {
               window.location = "selectrole.php"; // หน้าที่ต้องการให้กระโดดไป
        });
        }, 1000);
    </script>';
}

// ปิดการเชื่อมต่อ
mysqli_stmt_close($stmt);
mysqli_close($con);
?>
