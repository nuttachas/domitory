<?php
session_start();

// ดึง ID ของหอพักจากเซสชัน
$apartmentId = isset($_SESSION['apartment_id']) ? $_SESSION['apartment_id'] : null;

// ลบ ID ของหอพักจากเซสชันหลังจากใช้งาน
unset($_SESSION['apartment_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/owner-create.css">
    <link rel="stylesheet" href="assets/css/dropdown.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&family=Noto+Sans+Thai:wght@300&family=Prompt:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">
    <script src="dropdown.js"></script>
    <title>Apartment Creation Success</title>
</head>
<body>

<header id="header">
    <nav>
        <a href="selectrole.php" class="logo">ระบบบริหารจัดการหอพัก</a>
        <ul>
            <li class="dropdown">
                <div class="dropbtn">
                    <i class="fa-solid fa-circle-user fa-2x"></i>
                </div>
                <div class="dropdown-content">
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
    <h1>สร้างหอพักสำเร็จ</h1>
    <?php if ($apartmentId): ?>
        <p>ID หอพักของคุณคือ: <strong><?php echo htmlspecialchars($apartmentId); ?></strong></p>
        <p>กรุณาแจ้ง ID นี้ให้กับผู้เช่าเพื่อใช้ในการลงทะเบียน</p>
        <button type="button" class="btn" onclick="window.location.href='selectrole.php';">กลับไปหน้าแรก</button>
    <?php else: ?>
        <p>เกิดข้อผิดพลาดในการสร้างหอพัก</p>
        <button type="button" class="btn" onclick="window.location.href='selectrole.php';">กลับไปหน้าแรก</button>
<?php endif; ?>
</main>


</body>
</html>