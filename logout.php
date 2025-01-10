<?php
// เริ่มต้น session
session_start();

// ล้างข้อมูลทั้งหมดใน session
session_unset();

// ทำลาย session
session_destroy();

// เปลี่ยนเส้นทางกลับไปยังหน้า login.php
header("Location: index.php");
exit();
?>
