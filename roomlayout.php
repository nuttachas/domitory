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

// ตรวจสอบการอัปเดตข้อมูลห้อง
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    if (isset($data['action'])) {
        if ($data['action'] === 'add') {
            // การเพิ่มข้อมูลห้อง
            $roomNumber = mysqli_real_escape_string($con, $data['roomNumber']);
            $status = mysqli_real_escape_string($con, $data['status']);
            $floorNumber = (int)$data['floorNumber'];
            $renterCount = (int)$data['renterCount'];
            $apartmentId = $_SESSION['apartment_id'];

            // ตรวจสอบค่าว่าง
            if (empty($roomNumber) || empty($status) || empty($floorNumber) || empty($renterCount)) {
                echo json_encode(["success" => false, "message" => "กรุณากรอกข้อมูลให้ครบถ้วน"]);
                exit();
            }

            // SQL Insert สำหรับการเพิ่มข้อมูลห้อง
            $sql = "INSERT INTO rooms (room_number, status, floor_number, renter_count, apartment_id) 
                    VALUES ('$roomNumber', '$status', $floorNumber, $renterCount, $apartmentId)";

            if (mysqli_query($con, $sql)) {
                echo json_encode(["success" => true, "message" => "ห้องถูกเพิ่มเรียบร้อยแล้ว"]);
            } else {
                echo json_encode(["success" => false, "message" => "เกิดข้อผิดพลาดในการเพิ่มข้อมูลห้อง: " . mysqli_error($con)]);
            }
        } elseif ($data['action'] === 'delete') {
            // การลบข้อมูลห้อง
            if (isset($data['roomId'])) {
                $roomId = (int)$data['roomId'];

                // SQL Delete สำหรับการลบข้อมูลห้อง
                $sql = "DELETE FROM rooms WHERE id = $roomId";

                if (mysqli_query($con, $sql)) {
                    echo json_encode(["success" => true, "message" => "ห้องถูกลบเรียบร้อยแล้ว"]);
                } else {
                    echo json_encode(["success" => false, "message" => "เกิดข้อผิดพลาดในการลบข้อมูลห้อง: " . mysqli_error($con)]);
                }
            } else {
                echo json_encode(["success" => false, "message" => "ไม่พบข้อมูลห้องที่จะลบ"]);
            }
            mysqli_close($con);
            exit;
        } elseif ($data['action'] === 'edit') {
            // (เพิ่มโค้ดการแก้ไขห้องที่นี่)
        }
    }

    mysqli_close($con);
    exit;
}

// ดึงข้อมูลห้องทั้งหมดสำหรับผังห้อง
$apartmentId = $_SESSION['apartment_id'];
$rooms_query = "SELECT * FROM rooms WHERE apartment_id = $apartmentId ORDER BY floor_number, room_number";
$rooms_result = mysqli_query($con, $rooms_query);
$rooms_data = [];

// เก็บข้อมูลห้อง
while ($row = mysqli_fetch_assoc($rooms_result)) {
    $rooms_data[$row['floor_number']][] = $row;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ผังห้อง</title>
    <script src="https://kit.fontawesome.com/433b24fc29.js" crossorigin="anonymous"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&family=Prompt:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/home.css">
    <link rel="stylesheet" href="assets/css/roomlayout.css">
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

        <div class="add-room-form">
        <h1>ผังห้อง</h1>
    <h2>เพิ่มข้อมูลห้อง</h2>
    <input type="text" id="roomNumber" placeholder="หมายเลขห้อง" required>
    <input type="number" id="floorNumber" placeholder="ชั้น" required>
    <input type="number" id="renterCount" placeholder="จำนวนผู้เช่า" required>
    
    <label for="status">สถานะ:</label>
    <select id="status" required>
        <option value="" disabled selected>เลือกสถานะ</option>
        <option value="vacant">ว่าง</option>
        <option value="occupied">ไม่ว่าง</option>
    </select>
    <button onclick="addRoom()">เพิ่มห้อง</button>
    <p id="message"></p>
</div>

    <div class="room-layout">
        <table>
            <thead>
                <tr>
                    <th>หมายเลขห้อง</th>
                    <th>ชั้น</th>
                    <th>จำนวนผู้เช่า</th>
                    <th>สถานะ</th>
                    <th>การจัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rooms_data as $floor => $rooms): ?>
                    <?php foreach ($rooms as $room): ?>
                        <tr>
                            <td><?php echo $room['room_number']; ?></td>
                            <td><?php echo $room['floor_number']; ?></td>
                            <td><?php echo $room['renter_count']; ?></td>
                            <td>
                                <?php 
                                    // แปลงสถานะจากอังกฤษเป็นไทย
                                    echo $room['status'] === 'vacant' ? 'ว่าง' : 'ไม่ว่าง'; 
                                ?>
                            </td>
                            <td>
    <a href="edit_room.php?room_id=<?php echo $room['id']; ?>">แก้ไข</a>
    <button onclick="deleteRoom(<?php echo $room['id']; ?>)">ลบ</button>
</td>

                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
   // เพิ่มห้อง
function addRoom() {
    const roomNumber = document.getElementById('roomNumber').value;
    const status = document.getElementById('status').value;
    const floorNumber = document.getElementById('floorNumber').value;
    const renterCount = document.getElementById('renterCount').value;

        // ตรวจสอบค่าว่าง
        if (!roomNumber || !status || !floorNumber || !renterCount) {
        document.getElementById('message').innerText = "กรุณากรอกข้อมูลให้ครบถ้วน";
        return;
    }

    const data = {
        action: 'add',
        roomNumber: roomNumber,
        status: status,
        floorNumber: floorNumber,
        renterCount: renterCount
    };

    fetch('roomlayout.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        document.getElementById('message').innerText = result.message;
        if (result.success) {
            location.reload(); // รีเฟรชหน้าเพื่อนำข้อมูลห้องล่าสุด
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}
// ลบห้อง
function deleteRoom(roomId) {
        if (confirm("คุณต้องการลบห้องนี้ใช่ไหม?")) {
            const data = {
                action: 'delete',
                roomId: roomId
            };

            fetch('roomlayout.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.success) {
                    location.reload(); // รีเฟรชหน้าเพื่อลบห้องที่ถูกลบออก
                }
            })
            .catch((error) => {
                console.error('Error:', error);
            });
        }
    }
</script>
</body>
</html>