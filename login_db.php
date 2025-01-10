<?php
session_start();
include('server.php');

$errors = array();

if (isset($_POST['login_user'])) {
    // รับค่าจากฟอร์ม
    $username = mysqli_real_escape_string($con, $_POST['username']);
    $password = mysqli_real_escape_string($con, $_POST['password']);

    // ตรวจสอบว่าชื่อผู้ใช้และรหัสผ่านถูกกรอกหรือไม่
    if (empty($username)) {
        array_push($errors, "Username is required");
    }
    if (empty($password)) {
        array_push($errors, "Password is required");
    }

    // ถ้าไม่มีข้อผิดพลาด
    if (count($errors) == 0) {
        // ตรวจสอบชื่อผู้ใช้ในฐานข้อมูล
        $query = "SELECT * FROM users WHERE username = ? LIMIT 1";
        $stmt = mysqli_prepare($con, $query);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (!$result) {
            die("Query failed: " . mysqli_error($con));
        }

        $user = mysqli_fetch_assoc($result);

      // ถ้าผู้ใช้มีอยู่และตรวจสอบรหัสผ่านที่ถูกเข้ารหัส
if ($user && password_verify($password, $user['password'])) {
    $_SESSION['user_id'] = $user['user_id']; // บันทึก user_id
    $_SESSION['username'] = $username;
    $_SESSION['role'] = $user['role']; // บันทึกบทบาทของผู้ใช้
    $_SESSION['success'] = "You are now logged in";
    header("location: selectrole.php");
    exit();
}else {
            array_push($errors, "Wrong Username or Password");
            $_SESSION['error'] = "Wrong Username or Password!";
            header("location: login.php");
            exit();
        }
    } else {
        $_SESSION['error'] = implode(", ", $errors);
        header("location: login.php");
        exit();
    }
}
?>
