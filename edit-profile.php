<?php
session_start();
include('server.php'); // ตรวจสอบว่า server.php มีการเชื่อมต่อฐานข้อมูล

// ตรวจสอบว่าผู้ใช้ล็อกอินแล้วหรือยัง
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
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

$username = $_SESSION['username'];

// ดึงข้อมูลผู้ใช้จากฐานข้อมูล
$sql = "SELECT * FROM users WHERE username = ?";
$stmt = mysqli_prepare($con, $sql);
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// อัปเดตข้อมูลผู้ใช้
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    $sql_update = "UPDATE users SET username = ?, firstname = ?, lastname = ?, email = ?, phone = ? WHERE username = ?";
    $stmt_update = mysqli_prepare($con, $sql_update);
    mysqli_stmt_bind_param($stmt_update, "sssssss", $username, $firstname, $lastname, $email, $phone, $target_file, $username);
    mysqli_stmt_execute($stmt_update);
    mysqli_stmt_close($stmt_update);

    header("Location: profile.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/edit-profile.css">
    <link rel="stylesheet" href="assets/css/dropdown.css">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&family=Prompt:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="dropdown.js"></script>
    <title>แก้ไขข้อมูลส่วนตัว</title>
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
    
    <form action="edit-profile.php" method="post" enctype="multipart/form-data">
    <h1>แก้ไขข้อมูลส่วนตัว</h1>
        <div class="input-group">
            <label for="username">ชื่อผู้ใช้</label>
            <input type="text" name="username" placeholder="ชื่อผู้ใช้"
            <?php echo htmlspecialchars($user['username']); ?> required>
        </div>

        <div class="input-group2">
            <div class="input">
            <label for="firstname">ชื่อจริง</label>
            <input type="text" name="firstname" placeholder="ชื่อจริง"
            <?php echo htmlspecialchars($user['firstname']); ?> required>
        </div>

        <div class="input">
            <label for="lastname">นามสกุล</label>
            <input type="text" name="lastname" placeholder="นามสกุล"
            <?php echo htmlspecialchars($user['lastname']); ?> required>
        </div>
    </div>

        <div class="input-group">
            <label for="email">อีเมล</label>
            <input type="email" name="email" placeholder="อีเมล"
            <?php echo htmlspecialchars($user['email']); ?> required>
        </div>

        <div class="input-group">
            <label for="phone">เบอร์โทรศัพท์</label>
            <input type="tel" name="phone" placeholder="เบอร์โทรศัพท์"
            <?php echo htmlspecialchars($user['phone']); ?> required>
        </div>

            <button type="submit">แก้ไขข้อมูล</button>
            <a href="selectrole.php">ยกเลิก</a>
        </div>

    </form>
</body>
</html>