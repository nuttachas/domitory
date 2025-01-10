<?php
session_start();
include('server.php');

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบหรือไม่
if (!isset($_SESSION['username'])) {
    header('location: index.php');
    exit();
}

// ตรวจสอบว่ามี ID ของผู้เช่าใน URL หรือไม่
if (!isset($_GET['id'])) {
    die('ID ไม่ถูกต้อง');
}

$id = intval($_GET['id']);

// ดึงค่า apartment_id จาก session เพื่อตรวจสอบสิทธิ์
$apartment_id = $_SESSION['apartment_id'];

// ตรวจสอบว่าผู้เช่าอยู่ใน apartment ของเจ้าของจริงหรือไม่
$check_query = "SELECT id FROM renter WHERE id = ? AND apartment_id = ?";
$check_stmt = $con->prepare($check_query);
$check_stmt->bind_param('ii', $id, $apartment_id);
$check_stmt->execute();
$check_stmt->store_result();

if ($check_stmt->num_rows == 0) {
    die('ไม่พบข้อมูลผู้เช่าหรือไม่มีสิทธิ์ในการลบข้อมูลนี้');
}

// หากพบข้อมูลและมีสิทธิ์ลบ ทำการลบข้อมูล
$delete_query = "DELETE FROM renter WHERE id = ?";
$delete_stmt = $con->prepare($delete_query);
$delete_stmt->bind_param('i', $id);

if ($delete_stmt->execute()) {
    // เพิ่ม SweetAlert เพื่อแสดงข้อความเมื่อการลบสำเร็จ
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
                title: "ลบข้อมูลสำเร็จ",
                type: "success"
            }, function() {
                window.location = "manage.php"; // หน้าที่ต้องการให้กระโดดไป
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
                window.location = "manage.php"; // หน้าที่ต้องการให้กระโดดไป
            });
        }, 1000);
    </script>';
}

$delete_stmt->close();
$con->close(); // ปิดการเชื่อมต่อฐานข้อมูล
?>
