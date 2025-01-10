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

// กำหนดตัวแปรการค้นหา
$room_number = '';
$month = '';
$year = '';
$summary_type = 'monthly'; // ค่าเริ่มต้นคือรายเดือน
$expense_type = 'all'; // ค่าเริ่มต้นคือทั้งหมด

// ตรวจสอบว่ามีการส่งข้อมูลค้นหามาหรือไม่
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $room_number = isset($_POST['room_number']) ? mysqli_real_escape_string($con, $_POST['room_number']) : '';
    $month = isset($_POST['month']) ? mysqli_real_escape_string($con, $_POST['month']) : '';
    $year = isset($_POST['year']) ? mysqli_real_escape_string($con, $_POST['year']) : '';
    $summary_type = isset($_POST['summary_type']) ? mysqli_real_escape_string($con, $_POST['summary_type']) : 'monthly';
    $expense_type = isset($_POST['expense_type']) ? mysqli_real_escape_string($con, $_POST['expense_type']) : 'all';
}

// กำหนดอาร์เรย์สำหรับพารามิเตอร์
$params = [$user_id];

// สร้างการ query สำหรับสรุปค่าใช้จ่าย
$summary_query = "
    SELECT room_number,
        SUM(electricity_cost) AS total_electricity,
        SUM(water_cost) AS total_water,
        SUM(internet_cost) AS total_internet,
        SUM(room_cost) AS total_room,
        SUM(total_cost) AS grand_total
    FROM billing_statements
    WHERE user_id = ?
";

// เพิ่มเงื่อนไขสำหรับการกรองตามหมายเลขห้อง
if (!empty($room_number)) {
    $summary_query .= " AND room_number = ?";
    $params[] = $room_number;
}

// เพิ่มเงื่อนไขสำหรับเดือนและปี
if ($summary_type == 'monthly') {
    if (!empty($month)) {
        $summary_query .= " AND month = ?";
        $params[] = $month;
    }
    if (!empty($year)) {
        $summary_query .= " AND year = ?";
        $params[] = $year;
    }
} elseif ($summary_type == 'yearly') {
    if (!empty($year)) {
        $summary_query .= " AND year = ?";
        $params[] = $year;
    }
}

// กรองตามประเภทค่าใช้จ่าย (เฉพาะค่าไฟ ค่าเน็ต และค่าน้ำ)
if ($expense_type !== 'all') {
    if ($expense_type == 'electricity') {
        $summary_query .= " AND electricity_cost > 0";
    } elseif ($expense_type == 'water') {
        $summary_query .= " AND water_cost > 0";
    } elseif ($expense_type == 'internet') {
        $summary_query .= " AND internet_cost > 0";
    }
}

$summary_query .= " GROUP BY room_number";

// ตรวจสอบว่าการเตรียมคำสั่งทำได้หรือไม่
$stmt = $con->prepare($summary_query);
if ($stmt === false) {
    die('Prepare failed: ' . htmlspecialchars($con->error));
}

// bind parameters dynamically
$stmt->bind_param(str_repeat('s', count($params)), ...$params);
$stmt->execute();
$summary_result = $stmt->get_result();

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

$expense_types = [
    'all' => 'ทั้งหมด',
    'electricity' => 'ค่าไฟฟ้า',
    'water' => 'ค่าน้ำ',
    'internet' => 'ค่าอินเทอร์เน็ต'
];
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สรุปบิลค่าเช่า</title>
    <script src="https://kit.fontawesome.com/433b24fc29.js" crossorigin="anonymous"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&family=Prompt:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/home.css">
    <link rel="stylesheet" href="assets/css/summary.css">
    <link rel="stylesheet" href="assets/css/dropdown.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
    <h1>สรุปบิลค่าเช่า</h1>
        <input type="text" name="room_number" placeholder="หมายเลขห้อง" value="<?php echo htmlspecialchars($room_number); ?>">
        <select name="summary_type">
            <option value="monthly" <?php echo ($summary_type == 'monthly') ? 'selected' : ''; ?>>รายเดือน</option>
            <option value="yearly" <?php echo ($summary_type == 'yearly') ? 'selected' : ''; ?>>รายปี</option>
        </select>
        <select name="month">
            <option value="">กรุณาเลือกเดือน</option>
            <?php foreach ($months as $month_name => $display): ?>
                <option value="<?php echo $month_name; ?>" <?php echo ($month == $month_name) ? 'selected' : ''; ?>>
                    <?php echo $display; ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="number" name="year" placeholder="ปี" value="<?php echo htmlspecialchars($year); ?>">
        <select name="expense_type">
            <?php foreach ($expense_types as $key => $display): ?>
                <option value="<?php echo $key; ?>" <?php echo ($expense_type == $key) ? 'selected' : ''; ?>>
                    <?php echo $display; ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">ค้นหา</button>
    </form>

    <?php if ($summary_result && mysqli_num_rows($summary_result) > 0): ?>
    <table>
        <thead>
            <tr>
                <th>หมายเลขห้อง</th>
                <?php if ($expense_type == 'electricity' || $expense_type == 'all'): ?>
                    <th>ค่าไฟฟ้ารวม</th>
                <?php endif; ?>
                <?php if ($expense_type == 'water' || $expense_type == 'all'): ?>
                    <th>ค่าน้ำรวม</th>
                <?php endif; ?>
                <?php if ($expense_type == 'internet' || $expense_type == 'all'): ?>
                    <th>ค่าอินเทอร์เน็ตรวม</th>
                <?php endif; ?>
                <?php if ($expense_type == 'room' || $expense_type == 'all'): ?>
                    <th>ค่าห้องรวม</th>
                <?php endif; ?>
                <th>รวมทั้งหมด</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($summary_result)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['room_number']); ?></td>
                    <?php if ($expense_type == 'electricity' || $expense_type == 'all'): ?>
                        <td><?php echo number_format($row['total_electricity'], 2); ?> บาท</td>
                    <?php endif; ?>
                    <?php if ($expense_type == 'water' || $expense_type == 'all'): ?>
                        <td><?php echo number_format($row['total_water'], 2); ?> บาท</td>
                    <?php endif; ?>
                    <?php if ($expense_type == 'internet' || $expense_type == 'all'): ?>
                        <td><?php echo number_format($row['total_internet'], 2); ?> บาท</td>
                    <?php endif; ?>
                    <?php if ($expense_type == 'room' || $expense_type == 'all'): ?>
                        <td><?php echo number_format($row['total_room'], 2); ?> บาท</td>
                    <?php endif; ?>
                    <td><?php echo number_format($row['grand_total'], 2); ?> บาท</td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p>ไม่มีข้อมูลค่าใช้จ่ายสำหรับการสรุป กรุณาตรวจสอบข้อมูลที่กรอกหรือเงื่อนไขการค้นหา</p>
<?php endif; ?>
</div>

    <canvas id="expenseChart"></canvas>
    <script>
        const ctx = document.getElementById('expenseChart').getContext('2d');
        const chartData = {
            labels: [],
            datasets: [{
                label: 'ค่าไฟฟ้ารวม',
                data: [],
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                borderColor: 'rgba(255, 99, 132, 1)',
                borderWidth: 1
            },
            {
                label: 'ค่าน้ำรวม',
                data: [],
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            },
            {
                label: 'ค่าอินเทอร์เน็ตรวม',
                data: [],
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            },
            {
                label: 'ค่าห้องรวม',
                data: [],
                backgroundColor: 'rgba(255, 206, 86, 0.2)',
                borderColor: 'rgba(255, 206, 86, 1)',
                borderWidth: 1
            }]
        };

        <?php
        mysqli_data_seek($summary_result, 0); // reset result pointer
        while ($row = mysqli_fetch_assoc($summary_result)): ?>
            chartData.labels.push('<?php echo htmlspecialchars($row['room_number']); ?>');
            <?php if ($expense_type == 'electricity' || $expense_type == 'all'): ?>
                chartData.datasets[0].data.push(<?php echo $row['total_electricity']; ?>);
            <?php endif; ?>
            <?php if ($expense_type == 'water' || $expense_type == 'all'): ?>
                chartData.datasets[1].data.push(<?php echo $row['total_water']; ?>);
            <?php endif; ?>
            <?php if ($expense_type == 'internet' || $expense_type == 'all'): ?>
                chartData.datasets[2].data.push(<?php echo $row['total_internet']; ?>);
            <?php endif; ?>
            <?php if ($expense_type == 'room' || $expense_type == 'all'): ?>
                chartData.datasets[3].data.push(<?php echo $row['total_room']; ?>);
            <?php endif; ?>
        <?php endwhile; ?>

        const expenseChart = new Chart(ctx, {
            type: 'bar',
            data: chartData,
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</div>
</body>
</html>
