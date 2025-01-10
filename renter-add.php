<?php
session_start();
include('server.php');

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

// ตรวจสอบว่ามีข้อความแจ้งเตือนใน session หรือไม่
if (isset($_SESSION['message'])) {
    echo '
    <script src="https://code.jquery.com/jquery-2.1.3.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert-dev.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.css">';

    $type = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : 'warning'; // ใช้ประเภทที่เก็บไว้
    $icon = ''; // กำหนดค่าเริ่มต้น

    // กำหนดไอคอนตามประเภทข้อความ
    if ($type === 'success') {
        $icon = 'success'; // ไอคอนสำเร็จ
    } elseif ($type === 'error') {
        $icon = 'error'; // ไอคอนผิดพลาด
    } else {
        $icon = 'warning'; // ไอคอนคำเตือน
    }

    echo '<script>
        setTimeout(function() {
            swal({
                title: "'.$_SESSION['message'].'",
                icon: "'.$icon.'", // ใช้ไอคอนที่เหมาะสม
                buttons: false,
                timer: 2000
            }).then(function() {
                window.location = "renter-add.php"; // หน้าที่ต้องการให้กระโดดไป
            });
        }, 100);
    </script>';

    // ล้างข้อความแจ้งเตือนจาก session
    unset($_SESSION['message']);
    unset($_SESSION['message_type']); // ล้างประเภทข้อความ
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/renter-add.css">
    <link rel="stylesheet" href="assets/css/home.css">
    <link rel="stylesheet" href="assets/css/dropdown.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Noto+Sans+Thai:wght@300&family=Prompt:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <script src="dropdown.js"></script>
    <title>ลงทะเบียนผู้เช่า</title>
</head>
<body>
    <header class="header">
        <nav>
            <a href="selectrole.php" class="logo">ระบบบริหารจัดการหอพัก</a>
            <ul>
                <li class="dropdown">
                    <div class="dropbtn">
                        <i class="fa-solid fa-circle-user fa-2x"></i>
                    </div>
                    <div class="dropdown-content">
                        <a href="#">
                            <p><?php echo htmlspecialchars($user['username']); ?></p>
                        </a>
                        <a href="edit-profile.php">
                            <i class="fa-solid fa-user-pen"></i>
                            <p>แก้ไขข้อมูลส่วนตัว</p>
                        </a>
                        <a href="logout.php">
                            <i class="fa-solid fa-right-from-bracket"></i>
                            <p>ออกจากระบบ</p>
                        </a>
                    </div>
                </li>
            </ul>
        </nav>
    </header>

<main>

    <!-- แสดงข้อความแจ้งเตือนเฉพาะเมื่อเป็น POST -->
    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['message'])) {
        echo "<p>" . $_SESSION['message'] . "</p>";
        unset($_SESSION['message']);
    }
    ?>

    <form action="save-renter.php" method="post"> <!-- เปลี่ยน action เป็น save-renter.php -->
    <h1>ลงทะเบียนผู้เช่า</h1>
        <div class="input-group">
            <label for="apartment_id">ID หอพัก</label>
            <input type="text" name="apartment_id" placeholder="กรอก ID หอพัก" required>
        </div>

        <div class="input-group">
            <label for="renter_name">ชื่อ-นามสกุล</label>
            <input type="text" name="renter_name" placeholder="ชื่อ-นามสกุล" required>
        </div>

        <div class="input-group">
            <label for="renter_tel">เลขห้องพัก</label>
            <input type="text" name="renter_room" placeholder="กรอกเลขห้องพัก" required>
        </div>

        <div class="input-group">
            <label for="renter_tel">เบอร์โทรผู้เช่า</label>
            <input type="tel" name="renter_tel" placeholder="xxx-xxx-xxxx" required>
        </div>

        <div class="input-group">
            <label for="renter_email">อีเมลผู้เช่า</label>
            <input type="email" name="renter_email" placeholder="อีเมลผู้เช่า" required>
        </div>

        <button type="submit" class="btn">ลงทะเบียน</button>
        <button onclick="window.location.href='selectrole.php'" class="btn back-btn">กลับไปหน้าหลัก</button>
    </form>
</main>

</body>
</html>
