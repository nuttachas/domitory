<?php 
session_start();
include('server.php');

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบหรือไม่
if (!isset($_SESSION['username']) || !isset($_GET['id'])) {
    header('location: index.php');
    exit();
}

$receipt_id = (int) $_GET['id'];
$query = "SELECT * FROM billing_statements WHERE id = ?";
$stmt = $con->prepare($query);
$stmt->bind_param('i', $receipt_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "ไม่พบใบเสร็จ";
    exit();
}

$receipt = $result->fetch_assoc();
$user_role = $_SESSION['role']; // สมมติว่าบทบาทผู้ใช้เก็บในเซสชัน
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
    <link rel="stylesheet" href="assets/css/view_receipt_owner.css">
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

    <div class="container">
    <form method="POST" action="">
    <h2>รายละเอียดบิลค่าเช่า</h2>
    <p>หมายเลขห้อง: <?php echo htmlspecialchars($receipt['room_number']); ?></p>
    <table>
        <thead>
            <tr>
                <th>รายละเอียด</th>
                <th>ค่าใช้จ่าย</th>
            </tr>
        </thead>
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
    <a href="check_receipt.php" class="btn">กลับไปยังหน้าตรวจสอบใบเสร็จ</a>
    </form>
</div>

</body>
</html>