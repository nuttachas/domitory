<?php   
session_start();
include('server.php');

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบหรือไม่
if (!isset($_SESSION['username'])) {
    header('location: index.php');
    exit();
}

// ดึงข้อมูลผู้ใช้ที่เข้าสู่ระบบ
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

// ดึงข้อมูลของผู้เช่าและหมายเลขห้องที่ผู้เช่าลงทะเบียน
$renter_query = "
    SELECT r.id AS renter_id, r.name, a.name AS apartment_name, r.room_number, r.apartment_id
    FROM renter r
    JOIN apartments a ON r.apartment_id = a.id
    WHERE r.user_id = ?
";

// เตรียมคำสั่ง SQL
$stmt = $con->prepare($renter_query);
if ($stmt === false) {
    die('Prepare failed: ' . htmlspecialchars($con->error));
}

$stmt->bind_param('i', $user_id);
$stmt->execute();
$renter_result = $stmt->get_result();
if ($renter_result === false) {
    die('Execute failed: ' . htmlspecialchars($stmt->error));
}

if ($renter_result->num_rows === 0) {
    echo "<p style='color:red;'>ไม่พบข้อมูลผู้เช่าของคุณ</p>";
    exit();
}

// ดึงหมายเลขห้องที่ผู้เช่าลงทะเบียน
$renter_data = $renter_result->fetch_assoc();
$room_number = $renter_data['room_number'];

// ดึงข้อมูลใบเสร็จเฉพาะหมายเลขห้องที่ผู้เช่าลงทะเบียนและต้องอยู่ในหอพักที่ถูกต้อง
$receipts_query = "
    SELECT r.*, r.room_number
    FROM receipts r
    JOIN apartments a ON r.apartment_id = a.id
    WHERE r.room_number = ? AND a.id = ?
    ORDER BY r.year DESC,
            FIELD(r.month, 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม') DESC
    LIMIT 1
";

// เตรียมคำสั่ง SQL สำหรับใบเสร็จ
$stmt = $con->prepare($receipts_query);
if ($stmt === false) {
    die('Prepare failed: ' . htmlspecialchars($con->error));
}

// ดึง apartment_id ของผู้เช่า
$apartment_id = $renter_data['apartment_id'];

$stmt->bind_param('si', $room_number, $apartment_id);
$stmt->execute();
$receipts_result = $stmt->get_result();
if ($receipts_result === false) {
    die('Execute failed: ' . htmlspecialchars($stmt->error));
}

// แสดงผล

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตรวจสอบใบเสร็จ</title>
    <script src="https://kit.fontawesome.com/433b24fc29.js" crossorigin="anonymous"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&family=Prompt:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/home.css">
    <link rel="stylesheet" href="assets/css/renter_check_bill.css">
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
        <a href="renter_billing_history.php" class="sidebar_item">
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
        <h1>ตรวจสอบบิลค่าเช่า</h1>
        <h2>ข้อมูลหอพัก: <?php echo htmlspecialchars($renter_data['apartment_name']); ?></h2>
        <h3>หมายเลขห้อง: <?php echo htmlspecialchars($room_number); ?></h3>

        <?php if ($receipts_result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>เดือน</th>
                        <th>ปี</th>
                        <th>ยอดรวม</th>
                        <th>ดูรายละเอียด</th>
                        <th>รายละเอียดการชำระเงิน</th>
                        <th>สถานะการชำระเงิน</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($receipt = $receipts_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($receipt['month']); ?></td>
                        <td><?php echo htmlspecialchars($receipt['year']); ?></td>
                        <td><?php echo htmlspecialchars(number_format($receipt['total_cost'], 2)); ?> บาท</td>
                        <td>
                            <a href="view_receipt_renter.php?id=<?php echo $receipt['id']; ?>" class="detail-btn">ดูรายละเอียด</a>
                            <?php if ($receipt['viewed'] == 0): ?>
                                <span class="new-notification">new</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="payment_page.php?id=<?php echo $receipt['id']; ?>" class="pay-btn">ชำระเงิน</a>
                        </td>
                        <td>
                            <?php if ($receipt['status'] == 'paid'): ?>
                                <span class="paid">ชำระเงินแล้ว</span>
                            <?php else: ?>
                                <span class="unpaid">ยังไม่ได้ชำระเงิน</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style='color:red;'>ไม่พบใบเสร็จสำหรับหมายเลขห้องนี้</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
