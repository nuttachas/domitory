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

// เริ่มต้นค่าตัวแปรการค้นหา
$room_number = '';
$month = '';
$year = '';

// ตรวจสอบว่ามีการส่งข้อมูลค้นหามาหรือไม่
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $room_number = mysqli_real_escape_string($con, $_POST['room_number']);
    $month = mysqli_real_escape_string($con, $_POST['month']);
    $year = mysqli_real_escape_string($con, $_POST['year']);
}

// ดึงข้อมูลใบเสร็จ
$billing_query = "SELECT * FROM billing_statements WHERE user_id = ?";
$params = [$user_id];

if (!empty($room_number)) {
    $billing_query .= " AND room_number = ?";
    $params[] = $room_number;
}
if (!empty($month)) {
    $billing_query .= " AND month = ?";
    $params[] = $month;
}
if (!empty($year)) {
    $billing_query .= " AND year = ?";
    $params[] = $year;
}

$billing_query .= " ORDER BY year DESC, month DESC";
$stmt = $con->prepare($billing_query);

// bind parameters dynamically
$stmt->bind_param(str_repeat('s', count($params)), ...$params);
$stmt->execute();
$billing_result = $stmt->get_result();

// ประกาศตัวแปร $months เพื่อใช้ในการแสดงชื่อเดือน
$months = [
    "มกราคม" => "มกราคม",
    "กุมภาพันธ์" => "กุมภาพันธ์",
    "มีนาคม" => "มีนาคม",
    "เมษายน" => "เมษายน",
    "พฤษภาคม" => "พฤษภาคม",
    "มิถุนายน" => "มิถุนายน",
    "กรกฎาคม" => "กรกฎาคม",
    "สิงหาคม" => "สิงหาคม",
    "กันยายน" => "กันยายน",
    "ตุลาคม" => "ตุลาคม",
    "พฤศจิกายน" => "พฤศจิกายน",
    "ธันวาคม" => "ธันวาคม"
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติบิลค่าเช่า</title>
    <script src="https://kit.fontawesome.com/433b24fc29.js" crossorigin="anonymous"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&family=Prompt:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/home.css">
    <link rel="stylesheet" href="assets/css/billing_history.css">
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

<h1>ประวัติบิลค่าเช่า</h1>
<form method="POST" action="">
    <input type="text" name="room_number" placeholder="หมายเลขห้อง" value="<?php echo htmlspecialchars($room_number); ?>">
    <select name="month">
        <option value="">กรุณาเลือกเดือน</option>
        <?php foreach ($months as $month_name => $display): ?>
            <option value="<?php echo $month_name; ?>" <?php echo ($month == $month_name) ? 'selected' : ''; ?>>
                <?php echo $display; ?>
            </option>
        <?php endforeach; ?>
    </select>
    <input type="number" name="year" placeholder="ปี" value="<?php echo htmlspecialchars($year); ?>">
    
    <div class="button-group">
        <button type="submit">ค้นหา</button>
        <button type="button" onclick="location.href='billing_history.php'">กลับ</button><br>
        <button type="button" onclick="location.href='summary.php'">สรุปรายการ</button>
    </div>
</form>

<?php if (mysqli_num_rows($billing_result) > 0): ?>
    <table>
        <thead>
            <tr>
                <th>หมายเลขห้อง</th>
                <th>เดือน</th>
                <th>ปี</th>
                <th>มิเตอร์ไฟฟ้า</th>
                <th>มิเตอร์ไฟครั้งก่อน</th>
                <th>ค่าไฟฟ้า</th>
                <th>มิเตอร์น้ำ</th>
                <th>ค่าน้ำ</th>
                <th>ค่าอินเทอร์เน็ต</th>
                <th>ค่าเช่าห้อง</th>
                <th>รวมค่าใช้จ่าย</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($billing_result)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['room_number']); ?></td>
                    <td><?php echo htmlspecialchars($months[$row['month']]); ?></td>
                    <td><?php echo htmlspecialchars($row['year']); ?></td>
                    <td><?php echo htmlspecialchars($row['electricity_meter']); ?> หน่วย</td>
                    <td><?php echo htmlspecialchars($row['electricity_meter_previous']); ?> หน่วย</td>
                    <td><?php echo number_format($row['electricity_cost'], 2); ?> บาท</td>
                    <td><?php echo htmlspecialchars($row['water_meter']); ?> หน่วย</td>
                    <td><?php echo number_format($row['water_cost'], 2); ?> บาท</td>
                    <td><?php echo number_format($row['internet_cost'], 2); ?> บาท</td>
                    <td><?php echo number_format($row['room_cost'], 2); ?> บาท</td>
                    <td><?php echo number_format($row['total_cost'], 2); ?> บาท</td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>ไม่มีใบแจ้งหนี้ในประวัติของคุณ</p>
<?php endif; ?>
</div>
</body>
</html>