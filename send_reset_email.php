<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'dormitory/vendor/autoload.php'; // สำหรับ Composer
session_start();
include('server.php'); // เชื่อมต่อกับฐานข้อมูล

if (isset($_POST['reset_password'])) {
    $username = $_POST['username'];

    // ตรวจสอบว่าชื่อผู้ใช้มีอยู่ในฐานข้อมูล
    $query = "SELECT * FROM users WHERE username=? LIMIT 1"; // ใช้ prepared statement
    $stmt = $con->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $to = $user['email']; // ดึงอีเมลจากฐานข้อมูล

        // สุ่ม token และบันทึกลงฐานข้อมูล
        $token = bin2hex(random_bytes(50));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour')); // หมดอายุใน 1 ชั่วโมง
        $stmt = $con->prepare("UPDATE users SET reset_token=?, reset_token_expiry=? WHERE username=?");
        $stmt->bind_param("sss", $token, $expiry, $username);
        $stmt->execute();
        $stmt->close();
        
        // ส่งอีเมลด้วย PHPMailer
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'supapit9177@gmail.com';
            $mail->Password   = 'rgsw lwqe azng hwql'; // ใช้รหัสผ่านที่ปลอดภัย
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 465;

            $mail->setFrom('supapit9177@gmail.com', 'Your Name');
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = 'Reset Password';
            $mail->Body    = "Click this link to reset your password: <a href='http://localhost/dormitory/reset_password.php?token=$token'>Reset Password</a>";
            
            $mail->SMTPDebug = 2; // เปิดการ Debugging
            $mail->Debugoutput = 'html'; // แสดงผลลัพธ์เป็น HTML

            $mail->send();
            $_SESSION['message'] = "ลิงก์สำหรับรีเซ็ตรหัสผ่านได้ถูกส่งไปยังอีเมลของคุณแล้ว";
            header('location: login.php');
            exit();
        } catch (Exception $e) {
            $_SESSION['error'] = "ไม่สามารถส่งอีเมลได้: {$mail->ErrorInfo}";
            header('location: forgotpw.php');
            exit();
        }
    } else {
        $_SESSION['error'] = "ชื่อผู้ใช้ไม่ถูกต้อง";
        header('location: forgotpw.php');
        exit();
    }
}
?>