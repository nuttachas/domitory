<?php
session_start();
include('server.php');

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบหรือไม่
if (!isset($_SESSION['username'])) {
    header('location: index.php');
    exit();
}

// รับค่าจากฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['receipt_id'])) {
    $receipt_id = intval($_POST['receipt_id']);
    $user_id = $_SESSION['user_id']; // ควรมีการบันทึก user_id ลงใน session

    // บันทึกข้อมูลการชำระเงินลงในฐานข้อมูล
    $payment_query = "INSERT INTO payments (receipt_id, user_id, payment_date) VALUES (?, ?, NOW())";
    $stmt = $con->prepare($payment_query);
    $stmt->bind_param('ii', $receipt_id, $user_id);
    
    if ($stmt->execute()) {
        echo "ชำระเงินเสร็จเรียบร้อย";
    } else {
        echo "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . htmlspecialchars($stmt->error);
    }
} else {
    echo "ไม่มีข้อมูลในการชำระเงิน";
}
?>
