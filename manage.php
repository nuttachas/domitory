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

// ดึงข้อมูลของผู้ใช้ที่ล็อกอินและ apartment_id
$username = mysqli_real_escape_string($con, $_SESSION['username']);
if (!isset($_SESSION['apartment_id'])) {
    // หากไม่พบ apartment_id ใน session ให้แสดงข้อความผิดพลาดหรือนำผู้ใช้กลับไปเลือกหอพักอีกครั้ง
    echo "กรุณาเลือกหอพักก่อนจัดการข้อมูล";
    exit();
}

// ดึงค่า apartment_id จาก session
$apartment_id = $_SESSION['apartment_id'];
// Query ดึงข้อมูลผู้เช่าที่อยู่ในหอพักที่เจ้าของเป็นคนสร้าง
$stmt = $con->prepare("SELECT id, room_number, name, username, email, phone FROM renter WHERE apartment_id = ?");
$stmt->bind_param('i', $apartment_id);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($id,$room_number, $name, $username, $email, $phone);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>จัดการผู้ใช้งาน</title>
    <script src="https://kit.fontawesome.com/433b24fc29.js" crossorigin="anonymous"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&family=Prompt:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/home.css">
    <link rel="stylesheet" href="assets/css/dropdown.css">
    <link rel="stylesheet" href="assets/css/manage.css">
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

    <main>
    <h1>จัดการผู้ใช้งาน</h1>
    <div class="add-button-container">
        <a href="add-renter.php" class="add-button">เพิ่มผู้ใช้งาน</a>
    </div>
        <table>
            <thead>
                <tr>
                    <th>เลขห้องพัก</th>
                    <th>ชื่อผู้เช่า</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>เบอร์โทรศัพท์</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($stmt->fetch()): ?>
                <tr>
                    <td><?= $room_number; ?></td>
                    <td><?= $name; ?></td>
                    <td><?= $username; ?></td>
                    <td><?= $email; ?></td>
                    <td><?= htmlspecialchars($phone); ?></td>
                    <td>
                    <button class="btn btn-warning btn-sm" onclick="window.location.href='edit-renter.php?id=<?= $id; ?>'">แก้ไข</button>
                    <button class="btn btn-danger btn-sm" onclick="if(confirm('ยืนยันการลบข้อมูล !!')) { window.location.href='delete-rentermanage.php?id=<?= $id; ?>' }">ลบ</button>
                </td>
                <?php endwhile; ?>
            </tbody>
        </table>
    </main>

    <?php
    $stmt->close(); // ปิด statement
    ?>
</body>
</html>