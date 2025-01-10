<?php
session_start();
include('server.php');

$errors = array();

if (isset($_POST['reg_user'])) {
    // รับค่าจากฟอร์ม
    $firstname = mysqli_real_escape_string($con, $_POST['firstname']);
    $lastname = mysqli_real_escape_string($con, $_POST['lastname']);
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $phone = mysqli_real_escape_string($con, $_POST['phone']);
    $username = mysqli_real_escape_string($con, $_POST['username']);
    $password_1 = mysqli_real_escape_string($con, $_POST['password_1']);
    $password_2 = mysqli_real_escape_string($con, $_POST['password_2']);
    $role = mysqli_real_escape_string($con, $_POST['role']); // รับประเภทผู้ใช้

    // ตรวจสอบว่าฟิลด์ทั้งหมดถูกกรอกหรือไม่
    if (empty($username)) {
        array_push($errors, "Username is required");
    }
    if (empty($email)) {
        array_push($errors, "Email is required");
    }
    if (empty($password_1)) {
        array_push($errors, "Password is required");
    }
    if ($password_1 != $password_2) {
        array_push($errors, "The two passwords do not match");
    }

    // ตรวจสอบว่าผู้ใช้หรืออีเมลนี้มีอยู่ในฐานข้อมูลแล้วหรือไม่
    if (count($errors) == 0) {
        $user_check_query = "SELECT * FROM users WHERE username = '$username' OR email = '$email' LIMIT 1";
        $result = mysqli_query($con, $user_check_query);

        if (!$result) {
            $_SESSION['error'] = "Query failed: " . mysqli_error($con);
            header("location: register.php");
            exit();
        }

        $user = mysqli_fetch_assoc($result);

        if ($user) { // ถ้าผู้ใช้หรืออีเมลมีอยู่แล้ว
            if ($user['username'] === $username) {
                array_push($errors, "Username already exists");
            }
            if ($user['email'] === $email) {
                array_push($errors, "Email already exists");
            }
        }
    }

    // ถ้าไม่มีข้อผิดพลาด ดำเนินการบันทึกข้อมูล
    if (count($errors) == 0) {
        // เข้ารหัสรหัสผ่านด้วย password_hash()
        $password = password_hash($password_1, PASSWORD_DEFAULT);

        // บันทึกข้อมูลลงฐานข้อมูล
        $sql = "INSERT INTO users (username, email, password, role, firstname, lastname, phone) VALUES ('$username', '$email', '$password', '$role', '$firstname', '$lastname', '$phone')";
        if (mysqli_query($con, $sql)) {
            // รับ user_id ที่เพิ่งเพิ่มเข้ามา
            $user_id = mysqli_insert_id($con);

            if ($role == 'owner') {
                // สำหรับเจ้าของหอพัก
                $sql_owner = "INSERT INTO owner (user_id, name) VALUES ('$user_id', 'Default Name')";
                mysqli_query($con, $sql_owner);
            } elseif ($role == 'renter') {
                // สำหรับผู้เช่า
                $owner_id = mysqli_real_escape_string($con, $_POST['owner_id']); // ต้องกรอกค่า owner_id
                $sql_renter = "INSERT INTO renter (user_id, owner_id, name) VALUES ('$user_id', '$owner_id', 'Default Name')";
                mysqli_query($con, $sql_renter);
            }

            $_SESSION['username'] = $username;
            $_SESSION['success'] = "You are now registered and logged in";
            header('location: index.php');
            exit();
        } else {
            $_SESSION['error'] = "Error inserting record: " . mysqli_error($con);
            header("location: register.php");
            exit();
        }
    } else {
        // ถ้ามีข้อผิดพลาด เปลี่ยนเส้นทางกลับไปหน้า register.php พร้อมข้อผิดพลาด
        $_SESSION['error'] = implode(", ", $errors);
        header("location: register.php");
        exit();
    }
}
?>
