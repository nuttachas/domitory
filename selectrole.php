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

// ดึงข้อมูลหอพักที่เจ้าของหอพักสร้าง
$apartment_query = "SELECT id, name FROM apartments WHERE created_by = ?";
$stmt = mysqli_prepare($con, $apartment_query);

if (!$stmt) {
    die('Error preparing statement: ' . mysqli_error($con));
}

mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$apartment_result = mysqli_stmt_get_result($stmt);

if (!$apartment_result) {
    die('Error executing statement: ' . mysqli_error($con));
}

// ดึงข้อมูลหอพักที่ผู้เช่าเชื่อมต่ออยู่
$renter_query = "
    SELECT r.apartment_id, a.name AS apartment_name, r.phone, r.email
    FROM renter r
    JOIN apartments a ON r.apartment_id = a.id
    WHERE r.user_id = ?";
$stmt = mysqli_prepare($con, $renter_query);

if (!$stmt) {
    die('Error preparing statement: ' . mysqli_error($con));
}

mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$renter_result = mysqli_stmt_get_result($stmt);

if (!$renter_result) {
    die('Error executing statement: ' . mysqli_error($con));
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>หน้าแรก</title>
    <link rel="stylesheet" href="assets/css/selectrole.css">
    <link rel="stylesheet" href="assets/css/dropdown.css">
    <link rel="stylesheet" href="assets/css/home.css">
    <script src="https://kit.fontawesome.com/433b24fc29.js" crossorigin="anonymous"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="dropdown.js"></script>
    <script src="assets/js/menu.js"></script>
</head>
<body>
    <header class="header">
        <nav>
            <a href="selectrole.php" class="logo">ระบบบริหารจัดการหอพัก</a>
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
        <!-- แสดงข้อผิดพลาด -->
        <?php if (isset($_SESSION['error'])) : ?>
            <div class="error">
                <h3><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></h3>
            </div>
        <?php endif ?>

        <div class="role-boxes">
            <!-- กล่องสำหรับเจ้าของหอพัก -->
            <div class="role-box">
                <h2>เจ้าของหอพัก</h2>
                <div class="image-box">
                    <img src="assets/images/owner.png" alt="Owner" />
                </div>
                
                <form action="owner-create.php" method="POST">
                    <input type="hidden" name="role" value="owner">
                    <button type="submit" class="btn">เพิ่มตึกใหม่</button>
                </form>
                
                <div class="apartment-list">
                    <h3>หอพักที่ลงทะเบียนแล้ว</h3>
                    <?php if (mysqli_num_rows($apartment_result) > 0) : ?>
                        <ul>
                            <?php while ($apartment = mysqli_fetch_assoc($apartment_result)) : ?>
                                <li class="apartment-item">
                                    <div class="apartment-info">
                                        <?php echo htmlspecialchars($apartment['id']) . ' - ' . htmlspecialchars($apartment['name']); ?>
                                    </div>
                                    <div class="apartment-buttons">
                                        <a href="roomlayout.php?apartment_id=<?php echo $apartment['id']; ?>" class="btn-manage">จัดการข้อมูล</a>
                                        <form action="delete-apartment.php" method="POST" onsubmit="return confirm('คุณต้องการลบหอพักนี้หรือไม่?');">
                                            <input type="hidden" name="apartment_id" value="<?php echo $apartment['id']; ?>">
                                            <button type="submit" class="btn-delete">ลบ</button>
                                        </form>
                                    </div>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else : ?>
                        <p>ยังไม่มีหอพักที่ลงทะเบียนไว้</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- กล่องสำหรับผู้เช่า -->
            <div class="role-box">
                <h2>ผู้เช่า</h2>
                <div class="image-box">
                    <img src="assets/images/renter.png" alt="Renter" />
                </div>

                <form action="renter-add.php" method="POST">
                    <input type="hidden" name="role" value="renter">
                    <button type="submit" class="btn">เชื่อมต่อหอพัก</button>
                </form>

                <div class="renter-list">
                    <h3>หอพักที่ผู้เช่าเชื่อมต่ออยู่</h3>
                    <?php if (mysqli_num_rows($renter_result) > 0) : ?>
                    <ul>
                        <?php while ($renter = mysqli_fetch_assoc($renter_result)) : ?>
                            <li class="renter-item">
                                <div class="renter-info">
                                    <?php echo htmlspecialchars($renter['apartment_id']) . ' - ' . htmlspecialchars($renter['apartment_name']); ?>
                                </div>
                                <div class="renter-buttons">
                                    <a href="renter_check_bill.php?apartment_id=<?php echo htmlspecialchars($renter['apartment_id']); ?>" class="btn-manage">เข้าสู่หน้าหอพัก</a>
                                    <form action="renter-delete.php" method="POST" onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบการเชื่อมต่อหอพักนี้?');">
                                        <input type="hidden" name="apartment_id" value="<?php echo htmlspecialchars($renter['apartment_id']); ?>">
                                        <button type="submit" class="btn-delete">ลบ</button>
                                    </form>
                                </div>
                            </li>
                        <?php endwhile; ?>
                    </ul>
        <?php else : ?>
            <p>ยังไม่มีการเชื่อมต่อหอพัก</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>