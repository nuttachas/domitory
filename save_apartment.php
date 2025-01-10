<?php
session_start();
include('server.php');

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบหรือไม่
if (!isset($_SESSION['username'])) {
    die('Username not found in session.');
}

// ตรวจสอบการเชื่อมต่อฐานข้อมูล
if (!$con) {
    die('Database connection failed: ' . mysqli_connect_error());
}

// ดึงข้อมูลของผู้ใช้ที่ล็อกอิน
$username = mysqli_real_escape_string($con, $_SESSION['username']);
$user_query = "SELECT user_id FROM users WHERE username = '$username' LIMIT 1";
$user_result = mysqli_query($con, $user_query);

if (!$user_result) {
    die('Error executing query: ' . mysqli_error($con));
}

if (mysqli_num_rows($user_result) == 0) {
    die('No user found with the username: ' . $username);
}

$user = mysqli_fetch_assoc($user_result);
$user_id = $user['user_id'];
echo 'User ID: ' . $user_id; // แสดงผล ID เพื่อการตรวจสอบ

// รับข้อมูลจากฟอร์ม
$apartmentname = $_POST['apartmentname'];
$apartmentaddress = $_POST['apartmentaddress'];
$prov_id = $_POST['Ref_prov_id'];
$amphure_id = $_POST['Ref_dist_id']; // แก้ไขให้ตรงกับชื่อคอลัมน์
$district_id = $_POST['Ref_subdist_id'];
$zip_code = $_POST['zip_code'];
$ownertel = $_POST['ownertel'];
$owneremail = $_POST['owneremail'];
$layer = $_POST['layer'];
$bill_day = $_POST['bill_day'];
$payment_end_day = $_POST['payment_end_day'];

// เตรียมคำสั่ง SQL
$sql = "INSERT INTO apartments (name, address, province_id, amphure_id, district_id, zip_code, phone_number, email, layers, bill_day, payment_end_day, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = mysqli_prepare($con, $sql);

if (!$stmt) {
    die('Error preparing statement: ' . mysqli_error($con));
}

// ใช้ bind_param กับคอลัมน์ที่มี 12 ตัวแปร (ssiiissssssi)
mysqli_stmt_bind_param($stmt, 'ssiiissssssi', $apartmentname, $apartmentaddress, $prov_id, $amphure_id, $district_id, $zip_code, $ownertel, $owneremail, $layer, $bill_day, $payment_end_day, $user_id);

// ประมวลผลคำสั่ง SQL
if (mysqli_stmt_execute($stmt)) {
    // ดึง ID ของแถวล่าสุดที่เพิ่ม
    $apartmentId = mysqli_insert_id($con);

    // เก็บ ID ของหอพักในเซสชัน
    $_SESSION['apartment_id'] = $apartmentId;

    // เปลี่ยนเส้นทางไปยังหน้าความสำเร็จ
    header('Location: success.php');
    exit();
} else {
    die('Error executing statement: ' . mysqli_stmt_error($stmt));
}

// ปิดคำสั่ง
mysqli_stmt_close($stmt);

// ปิดการเชื่อมต่อ
mysqli_close($con);
?>