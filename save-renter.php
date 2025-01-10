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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $apartmentId = mysqli_real_escape_string($con, $_POST['apartment_id']);
    $renterName = mysqli_real_escape_string($con, $_POST['renter_name']);
    $renterTel = mysqli_real_escape_string($con, $_POST['renter_tel']);
    $renterEmail = mysqli_real_escape_string($con, $_POST['renter_email']);
    $renterRoom = mysqli_real_escape_string($con, $_POST['renter_room']); // เพิ่มการเก็บเลขห้องพัก

    // ตรวจสอบว่ามีหอพักที่มี ID ตรงกับที่ผู้ใช้กรอกไหม
    $sql_check_apartment = "SELECT id FROM apartments WHERE id = '$apartmentId'";
    $result = mysqli_query($con, $sql_check_apartment);

    if (mysqli_num_rows($result) > 0) {
        // ตรวจสอบว่าเลขห้องนี้มีอยู่แล้วในตาราง renter หรือไม่
        $sql_check_room = "SELECT room_number FROM renter WHERE apartment_id = '$apartmentId' AND room_number = '$renterRoom'";
        $result_room = mysqli_query($con, $sql_check_room);

        if (mysqli_num_rows($result_room) > 0) {
            $_SESSION['message'] = "เลขห้อง '$renterRoom' นี้มีอยู่แล้วในหอพักนี้ กรุณาเลือกเลขห้องใหม่";
            $_SESSION['message_type'] = "warning"; // เปลี่ยนเป็น warning
            header("Location: renter-add.php");
            exit();
        }

        // หากพบหอพักและเลขห้องไม่ซ้ำ ให้ทำการบันทึกข้อมูลผู้เช่าลงในฐานข้อมูล
        $sql_insert_renter = "INSERT INTO renter (apartment_id, user_id, username, name, phone, email, room_number)
            VALUES ('$apartmentId', '$user_id', '$username', '$renterName', '$renterTel', '$renterEmail', '$renterRoom')";
        
        if (mysqli_query($con, $sql_insert_renter)) {
            $_SESSION['message'] = "ลงทะเบียนผู้เช่าสำเร็จ";
            $_SESSION['message_type'] = "success"; // เก็บประเภทข้อความ
        } else {
            $_SESSION['message'] = "เกิดข้อผิดพลาดในการลงทะเบียนผู้เช่า: " . mysqli_error($con);
            $_SESSION['message_type'] = "error"; // เปลี่ยนเป็น error
        }
    } else {
        $_SESSION['message'] = "ไม่พบหอพักที่มี ID ดังกล่าว";
        $_SESSION['message_type'] = "error"; // เปลี่ยนเป็น error
    }

    // ส่งผู้ใช้กลับไปหน้าเดิมพร้อมกับข้อความแจ้งเตือน
    header("Location: renter-add.php");
    exit();
}

// ปิดการเชื่อมต่อ
mysqli_close($con);
?>