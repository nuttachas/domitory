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

// ตรวจสอบว่ามี ID ของผู้เช่าใน URL หรือไม่
if (!isset($_GET['id'])) {
    die('ID ไม่ถูกต้อง');
}

$id = intval($_GET['id']);

// ดึงข้อมูลผู้เช่าจากฐานข้อมูล
$query = "SELECT room_number, name, username, email, phone FROM renter WHERE id = ?";
$stmt = $con->prepare($query);
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($room_number, $name, $username, $email, $phone);

if ($stmt->num_rows == 0) {
    die('ไม่พบข้อมูลผู้เช่าที่ต้องการแก้ไข');
}

$stmt->fetch();
$stmt->close();

// ตรวจสอบว่ามีการส่งฟอร์มเพื่ออัปเดตข้อมูลหรือไม่
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // รับข้อมูลจากฟอร์ม
    $updated_room_number = mysqli_real_escape_string($con, $_POST['room_number']);
    $updated_name = mysqli_real_escape_string($con, $_POST['name']);
    $updated_username = mysqli_real_escape_string($con, $_POST['username']);
    $updated_email = mysqli_real_escape_string($con, $_POST['email']);
    $updated_phone = mysqli_real_escape_string($con, $_POST['phone']);

    // อัปเดตข้อมูลในฐานข้อมูล
    $update_query = "UPDATE renter SET room_number = ?, name = ?, username = ?, email = ?, phone = ? WHERE id = ?";
    $update_stmt = $con->prepare($update_query);
    $update_stmt->bind_param('sssssi', $updated_room_number, $updated_name, $updated_username, $updated_email, $updated_phone, $id);

    // เพิ่ม SweetAlert
    echo '
        <script src="https://code.jquery.com/jquery-2.1.3.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert-dev.js"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.css">
        <link rel="stylesheet" href="assets/css/SweetAlert.css">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&family=Prompt:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">';

    if ($update_stmt->execute()) {
        echo '<script>
            setTimeout(function() {
                swal({
                    title: "แก้ไขข้อมูลสำเร็จ",
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
    $update_stmt->close();
    $con = null; // ปิดการเชื่อมต่อฐานข้อมูล
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขข้อมูลผู้ใช้</title>
    <script src="https://kit.fontawesome.com/433b24fc29.js" crossorigin="anonymous"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@100;200;300;400;500;600;700;800;900&family=Prompt:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/home.css">
    <link rel="stylesheet" href="assets/css/edit-renter.css">
    <link rel="stylesheet" href="assets/css/edit-user.css">
    <link rel="stylesheet" href="assets/css/dropdown.css">
    <script src="dropdown.js"></script>
    <script src="assets/js/menu.js"></script>
</head>
<body>
<div class="sidebar">
    <div class="sidebar_header">
        <i class="fa-solid fa-building-user fa-2x"></i>
    </div>
    <div class="sidebar_menu">
        <a href="selectrole.php" class="sidebar_item">
            <div class="sidebar_icon">
                <i class="fa-solid fa-house"></i>
            </div>
            <h4>หน้าหลัก</h4>
        </a>
        <a href="roomlayout.php" class="sidebar_item">
            <div class="sidebar_icon">
                <i class="fa-solid fa-grip"></i>
            </div>
            <h4>ผังห้อง</h4>
        </a>
        <a href="servicerates.php" class="sidebar_item">
            <div class="sidebar_icon">
                <i class="fa-solid fa-gears"></i>
            </div>
            <h4>ตั้งค่าหน่วยบริการ</h4>
        </a>
        <a href="billing_statement.php" class="sidebar_item">
            <div class="sidebar_icon">
                <i class="fa-solid fa-file-pen"></i>
            </div>
            <h4>สร้างบิลค่าเช่า</h4>
        </a>
        <a href="check_receipt.php" class="sidebar_item">
            <div class="sidebar_icon">
                <i class="fa-solid fa-file-import"></i>
            </div>
            <h4>แจ้งบิลค่าเช่า</h4>
        </a>
        <a href="billing_history.php" class="sidebar_item">
            <div class="sidebar_icon">
                <i class="fa-solid fa-clock"></i>
            </div>
            <h4>ประวัติบิลค่าเช่า</h4>
        </a>
        <a href="manage.php" class="sidebar_item">
            <div class="sidebar_icon">
                <i class="fa-solid fa-users-gear"></i>
            </div>
            <h4>จัดการผู้ใช้งาน</h4>
        </a>
        <a href="bank_account.php" class="sidebar_item">
            <div class="sidebar_icon">
                <i class="fa-solid fa-money-check-dollar"></i>
            </div>
            <h4>บัญชีธนาคาร</h4>
        </a>
        <a href="check_payment_slip.php" class="sidebar_item">
            <div class="sidebar_icon">
                <i class="fa-solid fa-receipt"></i>
            </div>
            <h4>ตรวจสอบสลิป</h4>
        </a>
        <a href="logout.php" class="sidebar_item">
            <div class="sidebar_icon">
                <i class="fa-solid fa-right-from-bracket"></i>
            </div>
            <h4>ออกจากระบบ</h4>
        </a>
    </div>
</div>

<div class="container">
    <header class="header">
        <button class="btn-toggle"><i class="fa-solid fa-bars"></i></button>
        <nav>
            <a href="selectrole.php" class="logo"></a>
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
        <form action="" method="POST">
        <h1>แก้ไขข้อมูลผู้เช่า</h1>
            <label for="room_number">เลขห้องพัก</label>
            <input type="text" id="room_number" name="room_number" value="<?= htmlspecialchars($room_number); ?>">

            <label for="name">ชื่อผู้เช่า</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($name); ?>">

            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="<?= htmlspecialchars($username); ?>">

            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($email); ?>">

            <label for="phone">เบอร์โทรศัพท์</label>
            <input type="tel" id="renter_tel" name="phone" value="<?= htmlspecialchars($phone); ?>">

            <button type="submit">บันทึกการเปลี่ยนแปลง</button>
            <a href="manage.php">ยกเลิก</a>
        </form>
    </main>

</body>
</html>