<?php
session_start();
include('server.php');

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบหรือไม่
if (!isset($_SESSION['username'])) {
    header('location: index.php');
    exit();
}

// รับ apartment_id จาก URL และตั้งค่าใน session
if (isset($_GET['apartment_id'])) {
    $_SESSION['apartment_id'] = $_GET['apartment_id'];
}

// ตรวจสอบว่าผู้ใช้ได้เลือกหอพักหรือไม่
if (!isset($_SESSION['apartment_id'])) {
    echo "<p style='color:red;'>กรุณาเลือกหอพักก่อนจัดการข้อมูล</p>";
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

// เพิ่ม SweetAlert
echo '
    <script src="https://code.jquery.com/jquery-2.1.3.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert-dev.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.css">
    <link rel="stylesheet" href="assets/css/SweetAlert.css">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@100;200;300;400;500;600;700;800;900&family=Prompt:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room = $_POST['room'];
    $month = $_POST['month'];
    $year = $_POST['year'];
    $electricity_type = $_POST['electricity_type'];
    $water_type = $_POST['water_type'];
    $internet_type = $_POST['internet_type'];

    // ตรวจสอบว่ามีการเลือกห้องหรือไม่
    if (empty($room)) {
        echo "<p style='color:red;'>กรุณาเลือกห้องก่อนบันทึกข้อมูล</p>";
    } else {
        // ดึงข้อมูลจาก servicerates
        $service_rates_query = "SELECT * FROM service_rates WHERE apartment_id = ?";
        $stmt = $con->prepare($service_rates_query);
        $stmt->bind_param('i', $_SESSION['apartment_id']);
        $stmt->execute();
        $service_rates = $stmt->get_result()->fetch_assoc();

        // คำนวณค่าไฟฟ้า
        $electricity_cost = 0;
        if ($electricity_type == 'flat') {
            $electricity_cost = (float)$service_rates['electrical_unit'];
        } else {
            // คำนวณจากมิเตอร์
            $last_meter_value = $_POST['electricity_meter1'] ?? 0;
            $previous_meter_value = $_POST['electricity_meter2'] ?? 0;
            $electricity_usage = $last_meter_value - $previous_meter_value;
            $electricity_cost = $electricity_usage * (float)$service_rates['electrical_unit'];
        }

        // คำนวณค่าน้ำ
        $water_cost = 0;
        if ($water_type == 'flat') {
            $water_cost = (float)$service_rates['water_unit_value'];
        } else {
            // คำนวณจากมิเตอร์
            $last_water_value = $_POST['water_meter1'] ?? 0;
            $previous_water_value = $_POST['water_meter2'] ?? 0;
            $water_usage = $last_water_value - $previous_water_value;
            $water_cost = $water_usage * (float)$service_rates['water_unit_value'];
        }

        // ค่าอินเทอร์เน็ต
        $internet_cost = 0;
        if ($internet_type == 'flat') {
            $internet_cost = (float)$service_rates['internet_unit'];
        }

        // ค่าเช่าห้อง
        $room_cost = (float)$service_rates['room_unit'];

        // รวมราคา
        $total_cost = $electricity_cost + $water_cost + $internet_cost + $room_cost;

        // รับค่ามิเตอร์ที่กรอกในฟอร์ม
        $electricity_meter_current = $_POST['electricity_meter1'] ?? 0; // ค่ามิเตอร์ไฟฟ้าที่กรอกในฟอร์ม
        $electricity_meter_previous = $_POST['electricity_meter2'] ?? 0; // ค่ามิเตอร์ไฟฟ้าครั้งก่อนที่กรอกในฟอร์ม
        $water_meter_current = $_POST['water_meter1'] ?? 0; // ค่ามิเตอร์น้ำที่กรอกในฟอร์ม
        $water_meter_previous = $_POST['water_meter2'] ?? 0; // ค่ามิเตอร์น้ำครั้งก่อนที่กรอกในฟอร์ม

        // บันทึกข้อมูลลงในตาราง billing_statements
        $insert_query = "INSERT INTO billing_statements (user_id, apartment_id, room_number, month, year, electricity_cost, water_cost, internet_cost, room_cost, total_cost, electricity_meter, electricity_meter_previous, water_meter, water_meter_previous) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $insert_stmt = $con->prepare($insert_query);
        $insert_stmt->bind_param('iiissddddddddd', $user_id, $_SESSION['apartment_id'], $room, $month, $year, $electricity_cost, $water_cost, $internet_cost, $room_cost, $total_cost, $electricity_meter_current, $electricity_meter_previous, $water_meter_current, $water_meter_previous);

         // ดำเนินการ INSERT
    if ($insert_stmt->execute()) {
        echo '<script>
            setTimeout(function() {
                swal({
                    title: "ข้อมูลบิลถูกบันทึกเรียบร้อยแล้ว!",
                    type: "success"
                }, function() {
                    window.location = "billing_statement.php"; // หน้าที่ต้องการให้กระโดดไป
                });
            }, 1000);
        </script>';
    } else {
        // แสดงข้อผิดพลาด
        echo '<script>
            setTimeout(function() {
                swal({
                    title: "เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . mysqli_error($con) . '",
                    type: "error"
                }, function() {
                    window.location = "billing_statement.php"; // หน้าที่ต้องการให้กระโดดไป
                });
            }, 1000);
        </script>';
    }
}
}

// ดึงข้อมูลห้องจาก roomlayout
$rooms_query = "SELECT * FROM rooms WHERE apartment_id = ?";
$stmt = $con->prepare($rooms_query);
$stmt->bind_param('i', $_SESSION['apartment_id']);
$stmt->execute();
$rooms_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สร้างใบแจ้งหนี้</title>
    <script src="https://kit.fontawesome.com/433b24fc29.js" crossorigin="anonymous"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@100;200;300;400;500;600;700;800;900&family=Prompt:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/home.css">
    <link rel="stylesheet" href="assets/css/dropdown.css">
    <link rel="stylesheet" href="assets/css/billing_statement.css">
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

<div class="content">
<form method="POST" action="">
<h1>สร้างบิลค่าเช่า</h1>
<div class="form-row">
    <div class="form-group">
        <label for="room">หมายเลขห้อง:</label>
        <select name="room" id="room" required>
            <option value="" disabled selected>กรุณาเลือกหมายเลขห้อง</option>
            <?php while ($room = $rooms_result->fetch_assoc()) : ?>
                <option value="<?php echo $room['room_number']; ?>"><?php echo $room['room_number']; ?></option>
            <?php endwhile; ?>
        </select>
    </div>

    <div class="form-group">
        <label for="month">กรุณาเลือกเดือน:</label>
        <select name="month" required>
            <option value="มกราคม">มกราคม</option>
            <option value="กุมภาพันธ์">กุมภาพันธ์</option>
            <option value="มีนาคม">มีนาคม</option>
            <option value="เมษายน">เมษายน</option>
            <option value="พฤษภาคม">พฤษภาคม</option>
            <option value="มิถุนายน">มิถุนายน</option>
            <option value="กรกฎาคม">กรกฎาคม</option>
            <option value="สิงหาคม">สิงหาคม</option>
            <option value="กันยายน">กันยายน</option>
            <option value="ตุลาคม">ตุลาคม</option>
            <option value="พฤศจิกายน">พฤศจิกายน</option>
            <option value="ธันวาคม">ธันวาคม</option>
        </select>
    </div>

    <div class="form-group">
        <label for="year">กรุณากรอกปี:</label>
        <input type="number" name="year" required>
    </div>
</div>
    <h2>กรุณาเลือกอัตราค่าบริการให้ตรงกับอัตราค่าบริการที่บันทึกไว้</h2>
    <fieldset>
        <legend>เลือกวิธีคิดค่าไฟฟ้า</legend>
        <label>
            <input type="radio" name="electricity_type" value="flat" checked onclick="toggleElectricityFields()"> เหมารายเดือน (บาท/เดือน)
        </label>
        <label>
            <input type="radio" name="electricity_type" value="meter" onclick="toggleElectricityFields()"> คิดตามจริง (บาท/ยูนิต)
        </label>
        
        <div id="electricity_meter_fields" style="display: none;">
            <label for="electricity_meter1">เลขมิเตอร์ครั้งนี้</label>
            <input type="number" name="electricity_meter1" placeholder="เลขมิเตอร์ครั้งนี้">
            <label for="electricity_meter2">เลขมิเตอร์ครั้งก่อน</label>
            <input type="number" name="electricity_meter2" placeholder="เลขมิเตอร์ครั้งก่อน">
        </div>
    </fieldset>
    
    <fieldset>
        <legend>ค่าน้ำ:</legend>
        <label>
            <input type="radio" name="water_type" value="flat" checked onclick="toggleWaterFields()"> เหมารายเดือน (บาท/เดือน)
        </label>
        <label>
            <input type="radio" name="water_type" value="meter" onclick="toggleWaterFields()"> คิดตามจริง (บาท/ยูนิต)
        </label>
        
        <div id="water_meter_fields" style="display: none;">
            <label for="water_meter1">เลขมิเตอร์ครั้งนี้</label>
            <input type="number" name="water_meter1" placeholder="เลขมิเตอร์ครั้งนี้">
            <label for="water_meter2">เลขมิเตอร์ครั้งก่อน</label>
            <input type="number" name="water_meter2" placeholder="เลขมิเตอร์ครั้งก่อน">
        </div>
    </fieldset>
    
    <fieldset>
        <legend>ค่าอินเทอร์เน็ต</legend>
        <label>
            <input type="radio" name="internet_type" value="flat" checked> เหมารายเดือน (บาท/เดือน)
        </label>
        <label>
            <input type="radio" name="internet_type" value="flat" checked> ฟรี
        </label>
    </fieldset>

    <button type="submit">บันทึกข้อมูล</button>
</form>

<script>
function toggleElectricityFields() {
    const electricityType = document.querySelector('input[name="electricity_type"]:checked').value;
    const electricityFields = document.getElementById('electricity_meter_fields');
    electricityFields.style.display = electricityType === 'meter' ? 'block' : 'none';
}

function toggleWaterFields() {
    const waterType = document.querySelector('input[name="water_type"]:checked').value;
    const waterFields = document.getElementById('water_meter_fields');
    waterFields.style.display = waterType === 'meter' ? 'block' : 'none';
}
</script>
</body>
</html>