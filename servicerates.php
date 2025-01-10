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

// ตรวจสอบการส่งฟอร์มเพิ่มข้อมูล
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ตรวจสอบข้อมูลจากฟอร์ม
    $electrical_unit = mysqli_real_escape_string($con, $_POST['electrical_unit']);
    $electrical_unit_type = mysqli_real_escape_string($con, $_POST['electrical_unit_type']);
    $water_unit_value = mysqli_real_escape_string($con, $_POST['water_unit_value']);
    $water_unit_type = mysqli_real_escape_string($con, $_POST['water_unit_type']);

    // ตรวจสอบว่าฟอร์มมีค่าอินเทอร์เน็ตหรือไม่
    $internet_unit_type = mysqli_real_escape_string($con, $_POST['internet_unit_type']);
    $internet_unit = mysqli_real_escape_string($con, $_POST['internet_unit']);

    // ตรวจสอบว่าค่าบริการอินเทอร์เน็ตเป็น 'free' หรือไม่
    if ($internet_unit_type == 'free') {
        echo "0 บาท/เดือน";  // แสดงเป็น "0 บาท" เมื่อประเภทเป็น "free"
        $internet_unit = 0;   // กำหนดค่าอินเทอร์เน็ตเป็น 0 สำหรับกรณี 'free'
    } else {
        echo number_format($internet_unit, 2) . " บาท/เดือน";  // แสดงตามค่า
    }

    $room_unit = mysqli_real_escape_string($con, $_POST['room_unit']);
    
    // เพิ่มข้อมูลลงในฐานข้อมูล
    $insert_query = "INSERT INTO service_rates (apartment_id, electrical_unit, electrical_unit_type, water_unit_value, water_unit_type, internet_unit, room_unit) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $con->prepare($insert_query);
    $stmt->bind_param('issssss', $apartment_id, $electrical_unit, $electrical_unit_type, $water_unit_value, $water_unit_type, $internet_unit, $room_unit);
    
    if ($stmt->execute()) {
        // Redirect หลังจากเพิ่มข้อมูลสำเร็จ
        header('location: ' . $_SERVER['PHP_SELF']);
        exit();
    } else {
        die('Error inserting data: ' . mysqli_error($con));
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>อัตราค่าบริการ</title>
    <script src="https://kit.fontawesome.com/433b24fc29.js" crossorigin="anonymous"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/home.css">
    <link rel="stylesheet" href="assets/css/servicerates.css">
    <link rel="stylesheet" href="assets/css/dropdown.css">
    <script src="assets/js/menu.js"></script>
    <script src="dropdown.js"></script>
    <script>
    function toggleWaterInput() {
        const waterUnitType = document.getElementById('water_unit_type').value;
        const waterUnitValueInput = document.getElementById('water_unit_value');

        // ตั้งค่า placeholder ตามประเภทหน่วยน้ำที่เลือก
        if (waterUnitType === 'monthly') {
            waterUnitValueInput.placeholder = 'เหมารายเดือน (บาท/เดือน)';
        } else if (waterUnitType === 'per_unit') {
            waterUnitValueInput.placeholder = 'คิดตามจริง (บาท/ยูนิต)';
        } else {
            waterUnitValueInput.placeholder = ''; // ล้าง placeholder ถ้าไม่มีการเลือก
        }

        // จัดการกับประเภทค่าไฟฟ้า
        const electricalUnitType = document.getElementById('electrical_unit_type').value;
        const electricalUnitInput = document.getElementById('electrical_unit');

        // ตั้งค่า placeholder ตามประเภทค่าไฟฟ้าที่เลือก
        if (electricalUnitType === 'monthly') {
            electricalUnitInput.placeholder = 'เหมารายเดือน (บาท/เดือน)';
        } else if (electricalUnitType === 'per_unit') {
            electricalUnitInput.placeholder = 'คิดตามจริง (บาท/ยูนิต)';
        } else {
            electricalUnitInput.placeholder = ''; // ล้าง placeholder ถ้าไม่มีการเลือก
        }

        // จัดการกับประเภทค่าอินเทอร์เน็ต
    const internetUnitType = document.getElementById('internet_unit_type').value;
    const internetUnitInput = document.getElementById('internet_unit');

        // ตั้งค่า placeholder ตามประเภทค่าอินเทอร์เน็ตที่เลือก
        if (internetUnitType === 'monthly') {
            internetUnitInput.placeholder = 'เหมารายเดือน (บาท/เดือน)';
        } else if (internetUnitType === 'free') {
            internetUnitInput.placeholder = 'ฟรี';
            internetUnitInput.value = 0; // ตั้งค่าเป็น 0 เมื่อเลือกฟรี
        } else {
            internetUnitInput.placeholder = ''; // ล้าง placeholder ถ้าไม่มีการเลือก
        }
    }

    // เรียกใช้ฟังก์ชันเมื่อโหลดหน้าเว็บเพื่อกำหนด placeholder เริ่มต้น
    document.addEventListener('DOMContentLoaded', function() {
        toggleWaterInput();
        // เพิ่มการติดตามการเปลี่ยนแปลงประเภทหน่วยน้ำและค่าไฟฟ้า
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

        <h1>หน่วยบริการ</h1>
        <div class="table-container">
    <table>
        <tr>
            <th>รายการ</th>
            <th>ราคา</th>
        </tr>
        <?php
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                // แสดงประเภทค่าไฟฟ้า
                if ($row['electrical_unit_type'] == 'monthly') {
                    echo "<tr><td>ค่าไฟฟ้า</td><td>{$row['electrical_unit']} บาท/เดือน</td></tr>";
                } else {
                    echo "<tr><td>ค่าไฟฟ้า</td><td>{$row['electrical_unit']} บาท/ยูนิต</td></tr>";
                }
        
                // แสดงประเภทค่าน้ำ
                if ($row['water_unit_type'] == 'monthly') {
                    echo "<tr><td>ค่าน้ำ</td><td>{$row['water_unit_value']} บาท/เดือน</td></tr>";
                } else {
                    echo "<tr><td>ค่าน้ำ</td><td>{$row['water_unit_value']} บาท/ยูนิต</td></tr>";
                }
                
                // แสดงประเภทค่าอินเทอร์เน็ต
                if ($row['internet_unit_type'] == 'monthly') {
                    echo "<tr><td>ค่าอินเทอร์เน็ต</td><td>" . number_format($row['internet_unit'], 2) . " บาท/เดือน</td></tr>";
                } elseif ($row['internet_unit_type'] == 'free') {
                    echo "<tr><td>ค่าอินเทอร์เน็ต</td><td>" . number_format(0, 2) . " บาท</td></tr>"; // แสดง 0.00 บาท
                } else {
                    echo "<tr><td>ค่าอินเทอร์เน็ต</td><td>" . number_format($row['internet_unit'], 2) . " บาท</td></tr>";
                }

                // แสดงค่าเช่าห้อง
                echo "<tr><td>ค่าเช่าห้อง</td><td>{$row['room_unit']} บาท/เดือน</td></tr>";
            }
        } else {
            echo "<tr><td colspan='2'>ไม่มีข้อมูล</td></tr>";
        }
        ?>
    </table>
</div>
        
        <?php if (mysqli_num_rows($result) == 0): ?>
            <form action="" method="post">
            <input type="hidden" name="apartment_id" value="<?= $apartment_id; ?>">

            <label for="electrical_unit_type">เลือกวิธีคิดค่าไฟฟ้า</label>
            <select name="electrical_unit_type" id="electrical_unit_type" onchange="toggleWaterInput()" required>
                <option value="monthly">เหมารายเดือน (บาท/เดือน)</option>
                <option value="per_unit">คิดตามจริง (บาท/ยูนิต)</option>
            </select>

            <label for="electrical_unit">หน่วยไฟฟ้า</label>
            <input type="number" name="electrical_unit" id="electrical_unit" required>

            <label for="water_unit_type">เลือกวิธีคิดค่าน้ำ</label>
            <select name="water_unit_type" id="water_unit_type" onchange="toggleWaterInput()" required>
                <option value="monthly">เหมารายเดือน (บาท/เดือน)</option>
                <option value="per_unit">คิดตามจริง (บาท/ยูนิต)</option>
            </select>

            <label for="water_unit_value">หน่วยน้ำ</label>
            <input type="number" name="water_unit_value" id="water_unit_value" required>
            
            <label for="internet_unit">เลือกวิธีคิดค่าอินเทอร์เน็ต</label>
            <select name="internet_unit_type" id="internet_unit_type" onchange="toggleWaterInput()" required>
                <option value="monthly">เหมารายเดือน (บาท/เดือน)</option>
                <option value="free">ฟรี</option>
            </select>

            <label for="internet_unit">ราคาอินเทอร์เน็ต (บาท/เดือน)</label>
            <input type="number" name="internet_unit" id="internet_unit" required>

            <label for="room_unit">ราคาห้องพัก (บาท/เดือน)</label>
            <input type="number" name="room_unit" id="room_unit" required>

            <button type="submit">บันทึก</button>
        </form>
        <?php else: ?>
            <div class="edit-button-container"> <!-- ใช้ div ครอบปุ่ม -->
                <a href="edit-service.php">
                    <button type="button">แก้ไขอัตราค่าบริการ</button>
                </a>
            </div>
        <?php endif; ?>

</body>
</html>