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

// ดึง apartment_id จาก session
if (!isset($_SESSION['apartment_id'])) {
    echo "กรุณาเลือกหอพักก่อนจัดการข้อมูล";
    exit();
}

$apartment_id = $_SESSION['apartment_id'];

// ตรวจสอบว่าเจ้าของหอพักที่ล็อกอินมีสิทธิ์จัดการหอพักนี้หรือไม่
$owner_check_query = "SELECT * FROM apartments WHERE id = ? AND created_by = ?";
$stmt = $con->prepare($owner_check_query);
$stmt->bind_param('ii', $apartment_id, $user_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows == 0) {
    die('คุณไม่มีสิทธิ์ในการแก้ไขอัตราค่าบริการสำหรับหอพักนี้');
}

// ดึงข้อมูลอัตราค่าบริการ
$sql = "SELECT * FROM service_rates WHERE apartment_id = ?";
$stmt = $con->prepare($sql);
$stmt->bind_param('i', $apartment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "ไม่มีข้อมูลอัตราค่าบริการสำหรับหอพักนี้";
    exit();
}

// ดึงข้อมูลอัตราค่าบริการเพื่อแสดงในฟอร์ม
$service_rate = $result->fetch_assoc();

// ตรวจสอบการส่งฟอร์มแก้ไขข้อมูล
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ตรวจสอบข้อมูลจากฟอร์ม
    $electrical_unit = mysqli_real_escape_string($con, $_POST['electrical_unit']);
    $electrical_unit_type = mysqli_real_escape_string($con, $_POST['electrical_unit_type']);
    $water_unit_value = mysqli_real_escape_string($con, $_POST['water_unit_value']);
    $water_unit_type = mysqli_real_escape_string($con, $_POST['water_unit_type']);
    
    // ตรวจสอบประเภทอินเทอร์เน็ตและกำหนดค่าเป็น 0 ถ้าเลือกฟรี
    $internet_unit_type = mysqli_real_escape_string($con, $_POST['internet_unit_type']);
    $internet_unit = $internet_unit_type === 'free' ? 0 : mysqli_real_escape_string($con, $_POST['internet_unit']);
    
    $room_unit = mysqli_real_escape_string($con, $_POST['room_unit']);
    
    // แก้ไขข้อมูลในฐานข้อมูล
    $update_query = "UPDATE service_rates SET
        electrical_unit = ?,
        electrical_unit_type = ?,
        water_unit_value = ?,
        water_unit_type = ?,
        internet_unit = ?,
        internet_unit_type = ?,
        room_unit = ?
        WHERE apartment_id = ?";
        
    $stmt = $con->prepare($update_query);
    $stmt->bind_param('issssssi', $electrical_unit, $electrical_unit_type, $water_unit_value, $water_unit_type, $internet_unit, $internet_unit_type, $room_unit, $apartment_id);
    
    if ($stmt->execute()) {
        // Redirect หลังจากแก้ไขข้อมูลสำเร็จ
        header('location: servicerates.php');
        exit();
    } else {
        die('Error updating data: ' . mysqli_error($con));
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขอัตราค่าบริการ</title>
    <script src="https://kit.fontawesome.com/433b24fc29.js" crossorigin="anonymous"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@100;200;300;400;500;600;700;800;900&family=Prompt:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/home.css">
    <link rel="stylesheet" href="assets/css/dropdown.css">
    <link rel="stylesheet" href="assets/css/edit-service.css">
    <script src="dropdown.js"></script>
    <script src="assets/js/menu.js"></script>
    <script>
    function toggleWaterInput() {
        const waterUnitType = document.getElementById('water_unit_type').value;
        const waterUnitValueInput = document.getElementById('water_unit_value');

        if (waterUnitType === 'monthly') {
            waterUnitValueInput.placeholder = 'เหมารายเดือน (บาท/เดือน)';
        } else if (waterUnitType === 'per_unit') {
            waterUnitValueInput.placeholder = 'คิดตามจริง (บาท/ยูนิต)';
        } else {
            waterUnitValueInput.placeholder = '';
        }

        const electricalUnitType = document.getElementById('electrical_unit_type').value;
        const electricalUnitInput = document.getElementById('electrical_unit');

        if (electricalUnitType === 'monthly') {
            electricalUnitInput.placeholder = 'เหมารายเดือน (บาท/เดือน)';
        } else if (electricalUnitType === 'per_unit') {
            electricalUnitInput.placeholder = 'คิดตามจริง (บาท/ยูนิต)';
        } else {
            electricalUnitInput.placeholder = '';
        }

        const internetUnitType = document.getElementById('internet_unit_type').value;
        const internetUnitInput = document.getElementById('internet_unit');

        if (internetUnitType === 'monthly') {
            internetUnitInput.placeholder = 'เหมารายเดือน (บาท/เดือน)';
        } else if (internetUnitType === 'free') {
            internetUnitInput.placeholder = 'ฟรี';
            internetUnitInput.value = 0;
        } else {
            internetUnitInput.placeholder = '';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        toggleWaterInput();
        document.getElementById('water_unit_type').addEventListener('change', toggleWaterInput);
        document.getElementById('electrical_unit_type').addEventListener('change', toggleWaterInput);
        document.getElementById('internet_unit_type').addEventListener('change', toggleWaterInput);
    });
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
    <form method="POST" action="">
    <h1>แก้ไขอัตราค่าบริการ</h1>
        <div class="form-group">
            <label for="electrical_unit_type">เลือกวิธีคิดค่าไฟฟ้า</label>
            <select id="electrical_unit_type" name="electrical_unit_type">
                <option value="monthly" <?php echo ($service_rate['electrical_unit_type'] == 'monthly') ? 'selected' : ''; ?>>เหมารายเดือน (บาท/เดือน)</option>
                <option value="per_unit" <?php echo ($service_rate['electrical_unit_type'] == 'per_unit') ? 'selected' : ''; ?>>คิดตามจริง (บาท/ยูนิต)</option>
            </select>

            <label for="electrical_unit">หน่วยไฟฟ้า</label>
            <input type="number" id="electrical_unit" name="electrical_unit" value="<?php echo htmlspecialchars($service_rate['electrical_unit']); ?>" required>
        </div>


        <div class="form-group">
        <label for="electrical_unit_type">เลือกวิธีคิดค่าน้ำ</label>
            <select id="water_unit_type" name="water_unit_type">
                <option value="monthly" <?php echo ($service_rate['water_unit_type'] == 'monthly') ? 'selected' : ''; ?>>เหมารายเดือน (บาท/เดือน)</option>
                <option value="per_unit" <?php echo ($service_rate['water_unit_type'] == 'per_unit') ? 'selected' : ''; ?>>คิดตามจริง (บาท/ยูนิต)</option>
            </select>
            <label for="water_unit_value">หน่วยน้ำ</label>
            <input type="number" id="water_unit_value" name="water_unit_value" value="<?php echo htmlspecialchars($service_rate['water_unit_value']); ?>" required>
        </div>

        <div class="form-group">
        <label for="electrical_unit_type">เลือกวิธีคิดค่าอินเทอร์เน็ต</label>
            <select id="internet_unit_type" name="internet_unit_type">
                <option value="monthly" <?php echo ($service_rate['internet_unit_type'] == 'monthly') ? 'selected' : ''; ?>>เหมารายเดือน (บาท/เดือน)</option>
                <option value="free" <?php echo ($service_rate['internet_unit_type'] == 'free') ? 'selected' : ''; ?>>ฟรี</option>
            </select>
            <label for="internet_unit">หน่วยอินเทอร์เน็ต</label>
            <input type="number" id="internet_unit" name="internet_unit" value="<?php echo htmlspecialchars($service_rate['internet_unit']); ?>" required>
        </div>

        <div class="form-group">
            <label for="room_unit">ราคาห้องพัก (บาท/เดือน)</label>
            <input type="number" id="room_unit" name="room_unit" value="<?php echo htmlspecialchars($service_rate['room_unit']); ?>" required>
        </div>

        <button type="submit" class="submit-button">บันทึก</button>
        <a href="servicerates.php">ยกเลิก</a>
    </form>
</div>
</body>
</html>