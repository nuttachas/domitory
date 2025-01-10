<?php 
session_start();
include('server.php');

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบหรือไม่
if (!isset($_SESSION['username'])) {
    header('location: index.php');
    exit();
}

// รับ apartment_id จาก session
if (!isset($_SESSION['apartment_id'])) {
    echo "<p style='color:red;'>กรุณาเลือกหอพักก่อนจัดการข้อมูล</p>";
    exit();
}

// ดึงข้อมูลของผู้ใช้ที่ล็อกอิน
$username = mysqli_real_escape_string($con, $_SESSION['username']);
$user_query = "SELECT user_id, username, role FROM users WHERE username = '$username' LIMIT 1";
$user_result = mysqli_query($con, $user_query);

if (!$user_result) {
    die('Error executing query: ' . mysqli_error($con));
}

if (mysqli_num_rows($user_result) == 0) {
    die('User not found.');
}

$user = mysqli_fetch_assoc($user_result);
$user_id = $user['user_id'];

// ตรวจสอบว่าเป็นเจ้าของหอหรือไม่
if ($user['role'] !== 'owner') {
    echo "<p style='color:red;'>คุณไม่มีสิทธิ์เข้าถึงหน้านี้</p>";
    exit();
}

// ตรวจสอบใบเสร็จที่มีอยู่ โดยเลือกใบเสร็จล่าสุดของแต่ละเดือน
$receipts_query = "
    SELECT b.*
    FROM billing_statements b
    INNER JOIN (
        SELECT room_number, MONTH(created_at) as month, YEAR(created_at) as year, MAX(created_at) as latest_date
        FROM billing_statements
        WHERE user_id = ? AND apartment_id = ?
        GROUP BY room_number, MONTH(created_at), YEAR(created_at)
    ) latest_receipts ON b.room_number = latest_receipts.room_number 
        AND MONTH(b.created_at) = latest_receipts.month 
        AND YEAR(b.created_at) = latest_receipts.year 
        AND b.created_at = latest_receipts.latest_date
    ORDER BY b.created_at DESC
";

$stmt = $con->prepare($receipts_query);
$stmt->bind_param('ii', $user_id, $_SESSION['apartment_id']);
$stmt->execute();
$receipts_result = $stmt->get_result();

// ตรวจสอบการส่งข้อมูลจากฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['receipt_ids']) && !empty($_POST['receipt_ids'])) {
    $receipt_ids = $_POST['receipt_ids'];

    // ดึงข้อมูลใบเสร็จที่เลือกเพื่อนำไปบันทึก
    foreach ($receipt_ids as $receipt_id) {
        // ดึงข้อมูลใบเสร็จจาก billing_statements
        $query = "SELECT * FROM billing_statements WHERE id = ?";
        $stmt = $con->prepare($query);
        $stmt->bind_param('i', $receipt_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $receipt = $result->fetch_assoc();

            // บันทึกลงตาราง receipts
            $insert_query = "
                INSERT INTO receipts (
                    user_id, room_number, month, year, electricity_cost, water_cost, internet_cost, room_cost, total_cost
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
            $stmt = $con->prepare($insert_query);
            $stmt->bind_param(
                'issiiiiii',
                $receipt['user_id'],
                $receipt['room_number'],
                $receipt['month'],
                $receipt['year'],
                $receipt['electricity_cost'],
                $receipt['water_cost'],
                $receipt['internet_cost'],
                $receipt['room_cost'],
                $receipt['total_cost']
            );
            $stmt->execute();
        }
    }

    // แสดงข้อความสำเร็จ
    echo 
    "<p style='color:green;'>ส่งใบเสร็จที่เลือกสำเร็จแล้ว</p>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แจ้งบิลค่าเช่า</title>
    <script src="https://kit.fontawesome.com/433b24fc29.js" crossorigin="anonymous"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@100;200;300;400;500;600;700;800;900&family=Prompt:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/home.css">
    <link rel="stylesheet" href="assets/css/dropdown.css">
    <link rel="stylesheet" href="assets/css/check_receipt.css">
    <script src="dropdown.js"></script>
    <script src="assets/js/menu.js"></script>
    <script>
        // ฟังก์ชันเลือกหรือยกเลิกการเลือกทั้งหมด
        function toggleSelectAll(source) {
            checkboxes = document.querySelectorAll('.receipt-checkbox');
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = source.checked;
            });
        }
    </script>
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
    <h2>แจ้งบิลค่าเช่า</h2>

    <?php if (mysqli_num_rows($receipts_result) > 0): ?>
        <form method="post" action="save_receipts.php">
            <table>
                <thead>
                    <tr>
                        <th><input type="checkbox" onclick="toggleSelectAll(this)"> เลือกทั้งหมด</th>
                        <th>หมายเลขห้อง</th>
                        <th>เดือน</th>
                        <th>ปี</th>
                        <th>ค่าไฟฟ้า</th>
                        <th>ค่าน้ำ</th>
                        <th>ค่าอินเทอร์เน็ต</th>
                        <th>ค่าเช่าห้อง</th>
                        <th>รวม</th>
                        <th>รายละเอียด</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($receipt = mysqli_fetch_assoc($receipts_result)): ?>
                        <tr>
                            <td><input type="checkbox" class="receipt-checkbox" name="receipt_ids[]" value="<?php echo $receipt['id']; ?>"></td>
                            <td><?php echo htmlspecialchars($receipt['room_number']); ?></td>
                            <td><?php echo htmlspecialchars($receipt['month']); ?></td>
                            <td><?php echo htmlspecialchars($receipt['year']); ?></td>
                            <td><?php echo htmlspecialchars(number_format($receipt['electricity_cost'], 2)); ?> บาท</td>
                            <td><?php echo htmlspecialchars(number_format($receipt['water_cost'], 2)); ?> บาท</td>
                            <td><?php echo htmlspecialchars(number_format($receipt['internet_cost'], 2)); ?> บาท</td>
                            <td><?php echo htmlspecialchars(number_format($receipt['room_cost'], 2)); ?> บาท</td>
                            <td><?php echo htmlspecialchars(number_format($receipt['total_cost'], 2)); ?> บาท</td>
                            <td>
                                <a href="view_receipt_owner.php?id=<?php echo $receipt['id']; ?>" class="detail-btn">ดูรายละเอียด</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <button type="submit">ส่งใบเสร็จที่เลือก</button>
        </form>
    <?php else: ?>
        <p>ไม่มีใบเสร็จให้แสดง</p>
    <?php endif; ?>
</div>
</body>
</html>