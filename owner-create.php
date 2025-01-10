<?php
session_start();
include('server.php');

$sql_provinces = "SELECT * FROM provinces";
$query = mysqli_query($con, $sql_provinces);

// ดึงข้อมูลของผู้ใช้ที่ล็อกอิน
$username = $_SESSION['username'];
$user_query = "SELECT username FROM users WHERE username = '$username' LIMIT 1";
$user_result = mysqli_query($con, $user_query);
$user = mysqli_fetch_assoc($user_result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/owner-create.css">
    <link rel="stylesheet" href="assets/css/home.css">
    <link rel="stylesheet" href="assets/css/dropdown.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Noto+Sans+Thai:wght@300&family=Prompt:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <script src="dropdown.js"></script>
    <script src="assets/js/generateRoomInputs.js"></script>
    <title>Owner creat apartment</title>
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

<form action="save_apartment.php" method="post">
    <h1>ข้อมูลหอพัก</h1>
        <div class="input-group">
            <label for="apartmentname">ชื่อหอพัก</label>
            <input type="text" name="apartmentname" placeholder="ชื่อหอพัก" required>
        </div>

        <div class="input-group">
            <label for="apartmentaddress">ที่อยู่หอพัก</label>
            <input type="text" name="apartmentaddress" placeholder="ที่อยู่หอพัก" required>
        </div>

        <div class="input-group2">
    <div>
        <label for="sel1">จังหวัด</label>
        <select class="form-control" name="Ref_prov_id" id="provinces" required>
            <option value="" selected disabled>กรุณาเลือกจังหวัด</option>
            <?php foreach ($query as $value) { ?>
            <option value="<?=$value['id']?>"><?=$value['name_th']?></option>
            <?php } ?>
        </select>
    </div>

    <div>
        <label for="sel1">อำเภอ</label>
        <select class="form-control" name="Ref_dist_id" id="amphures" required>
        </select>
    </div>

    <div>
        <label for="sel1">ตำบล</label>
        <select class="form-control" name="Ref_subdist_id" id="districts" required>
        </select>
    </div>

    <div>
        <label for="sel1">รหัสไปรษณีย์</label>
        <input type="text" name="zip_code" id="zip_code" class="form-control" required>
    </div>
    </div>

        <div class="input-group">
            <label for="ownertel">เบอร์โทรติดต่อหอพัก</label>
            <input type="tel" name="ownertel" placeholder="xxx-xxx-xxxx" required>
        </div>

        <div class="input-group">
            <label for="owneremail">อีเมลติดต่อหอพัก</label>
            <input type="email" name="owneremail" placeholder="อีเมลติดต่อหอพัก" required>
        </div>

        <div class="input-group">
            <label for="layer">จำนวนชั้น</label>
            <input type="number" id="layer" name="layer" placeholder="จำนวนชั้น" required oninput="generateRoomInputs()">
        </div>

        <!-- Container for dynamically generated room inputs -->
        <div id="room-container"></div>

        <div class="input-group2">
        <div>
    <label for="bill-day">หอพักของคุณทำบิลทุกวันที่เท่าไหร่</label>
    <div class="input-group">
        <label for="bill-day-select"></label>
        <select name="bill_day" id="bill-day-select" required>
            <option value="" selected disabled>กรุณาเลือกวัน</option>
            <?php
                for ($day = 1; $day <= 31; $day++) {
                    echo "<option value='$day'>วันที่ $day ของทุกเดือน</option>";
                }
            ?>
        </select>
    </div>
</div>

    <div>
        <label for="payment-end-day">หอพักของคุณกำหนดวันที่สิ้นสุดการชำระเงินทุกวันที่เท่าไหร่</label>
        <div class="input-group">
            <label for="payment-end-day-select"></label>
            <select name="payment_end_day" id="payment-end-day-select" required>
                <option value="" selected disabled>กรุณาเลือกวัน</option>
                <?php
                for ($day = 1; $day <= 31; $day++) {
                    echo "<option value='$day'>วันที่ $day ของทุกเดือน</option>";
                }
            ?>
            </select>
        </div>
    </div>
</div>

    <button type="submit" class="btn">สร้างหอพัก</button>
    </form>
</body>
</html>
<?php include('script.php');?>