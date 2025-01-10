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

// กำหนดค่า $receipt_id จาก GET parameter ก่อน
$receipt_id = $_GET['id'];

// ดึงข้อมูลใบเสร็จ
$query = "SELECT * FROM receipts WHERE id = ?";
$stmt = $con->prepare($query);
$stmt->bind_param('i', $receipt_id);
$stmt->execute();
$result = $stmt->get_result();

// ตรวจสอบว่าใบเสร็จมีอยู่จริงหรือไม่
if ($result->num_rows === 0) {
    echo "<p style='color:red;'>ไม่พบข้อมูลใบเสร็จนี้</p>";
    exit();
}

$receipt = $result->fetch_assoc();

// Update viewed status in the receipts table
$update_query = "UPDATE receipts SET viewed = 1 WHERE id = ?";
$stmt = $con->prepare($update_query);
$stmt->bind_param('i', $receipt_id);
$stmt->execute();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดบิลค่าเช่า</title>
    <script src="https://kit.fontawesome.com/433b24fc29.js" crossorigin="anonymous"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&family=Prompt:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/home.css">
    <link rel="stylesheet" href="assets/css/view_receipt_renter.css">
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
        <a href="renter_check_bill.php" class="sidebar_item">
            <div class="sidebar_icon">
                <i class="fa-solid fa-grip"></i>
            </div>
            <h4>ตรวจสอบบิลค่าเช่า</h4>
        </a>
        <a href="billing_history.php" class="sidebar_item">
            <div class="sidebar_icon">
                <i class="fa-solid fa-clock"></i>
            </div>
            <h4>ประวัติบิลค่าเช่า</h4>
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

    <div class="container">
    <form method="POST" action="">
    <h2>รายละเอียดบิลค่าเช่า</h2>
    <p>หมายเลขห้อง: <?php echo htmlspecialchars($receipt['room_number']); ?></p>
    <table>
        <tbody>
            <tr>
                <td>เดือน:</td>
                <td><?php echo htmlspecialchars($receipt['month']); ?></td>
            </tr>
            <tr>
                <td>ปี:</td>
                <td><?php echo htmlspecialchars($receipt['year']); ?></td>
            </tr>
            <tr>
                <td>เลขมิเตอร์ไฟฟ้าครั้งนี้:</td>
                <td><?php echo htmlspecialchars($receipt['electricity_meter']); ?></td>
            </tr>
            <tr>
                <td>เลขมิเตอร์ไฟฟ้าครั้งก่อน:</td>
                <td><?php echo htmlspecialchars($receipt['electricity_meter_previous']); ?></td>
            </tr>
            <tr>
                <td>ค่าไฟฟ้า:</td>
                <td><?php echo htmlspecialchars(number_format($receipt['electricity_cost'], 2)); ?> บาท</td>
            </tr>
            <tr>
                <td>เลขมิเตอร์น้ำครั้งนี้:</td>
                <td><?php echo htmlspecialchars($receipt['water_meter']); ?></td>
            </tr>
            <tr>
                <td>เลขมิเตอร์น้ำครั้งก่อน:</td>
                <td><?php echo htmlspecialchars($receipt['water_meter_previous']); ?></td>
            </tr>
            <tr>
                <td>ค่าน้ำ:</td>
                <td><?php echo htmlspecialchars(number_format($receipt['water_cost'], 2)); ?> บาท</td>
            </tr>
            <tr>
                <td>ค่าอินเทอร์เน็ต:</td>
                <td><?php echo htmlspecialchars(number_format($receipt['internet_cost'], 2)); ?> บาท</td>
            </tr>
            <tr>
                <td>ค่าเช่าห้อง:</td>
                <td><?php echo htmlspecialchars(number_format($receipt['room_cost'], 2)); ?> บาท</td>
            </tr>
            <tr>
                <td><strong>รวม:</strong></td>
                <td><strong><?php echo htmlspecialchars(number_format($receipt['total_cost'], 2)); ?> บาท</strong></td>
            </tr>
        </tbody>
    </table>
    <a href="renter_check_bill.php" class="btn">กลับไปยังหน้าตรวจสอบใบเสร็จ</a>
    </form>
</div>

</body>
</html>
