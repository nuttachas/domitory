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

// ตรวจสอบว่ามี apartment_id ใน session หรือไม่
if (!isset($_SESSION['apartment_id'])) {
    echo "<p style='color:red;'>กรุณาเลือกหอพักก่อนจัดการข้อมูล</p>";
    exit();
}

$apartment_id = $_SESSION['apartment_id'];

// เตรียมการค้นหา
$search_room_number = isset($_POST['room_number']) ? mysqli_real_escape_string($con, $_POST['room_number']) : '';
$search_month = isset($_POST['month']) ? mysqli_real_escape_string($con, $_POST['month']) : '';
$search_year = isset($_POST['year']) ? mysqli_real_escape_string($con, $_POST['year']) : '';
$search_status = isset($_POST['status']) ? mysqli_real_escape_string($con, $_POST['status']) : '';

// ตรวจสอบใบเสร็จในตาราง receipts ที่เกี่ยวข้องกับผู้ใช้
$receipts_query = "
    SELECT r.*, p.slip_image AS payment_slip_path 
    FROM receipts r
    LEFT JOIN payment_slips p ON r.id = p.receipt_id
    WHERE r.user_id = ? AND r.apartment_id = ?
";

// เพิ่มเงื่อนไขการค้นหาหมายเลขห้อง เดือน ปี และสถานะการชำระเงิน
if ($search_room_number !== '') {
    $receipts_query .= " AND r.room_number LIKE '%$search_room_number%'";
}
if ($search_month !== '') {
    // ตรวจสอบชื่อเดือนในฐานข้อมูล
    $receipts_query .= " AND r.month = '$search_month'";
}

if ($search_year !== '') {
    $receipts_query .= " AND r.year = '$search_year'";
}
if ($search_status !== '') {
    $receipts_query .= " AND r.status = '$search_status'";
}

$receipts_query .= " ORDER BY r.room_number ASC";  // เรียงตามหมายเลขห้อง

$stmt = $con->prepare($receipts_query);
$stmt->bind_param('ii', $user_id, $apartment_id);
$stmt->execute();
$receipts_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ตรวจสอบสลิปการโอนเงิน</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.css">
    <script src="https://kit.fontawesome.com/433b24fc29.js" crossorigin="anonymous"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@100;200;300;400;500;600;700;800;900&family=Prompt:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/home.css">
    <link rel="stylesheet" href="assets/css/dropdown.css">
    <link rel="stylesheet" href="assets/css/check_payment_slip.css">
    <script src="https://code.jquery.com/jquery-2.1.3.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert-dev.js"></script>
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
    <h1>ตรวจสอบสลิปการโอนเงิน</h1>

<!-- ฟอร์มค้นหา -->
<form method="post" action="">
    <input type="text" name="room_number" placeholder="หมายเลขห้อง" value="<?php echo htmlspecialchars($search_room_number); ?>">
    <input type="text" name="month" placeholder="กรุณากรอกชื่อเดือน" value="<?php echo htmlspecialchars($search_month); ?>">
    <input type="text" name="year" placeholder="กรุณากรอกปีตามพ.ศ." value="<?php echo htmlspecialchars($search_year); ?>">
    <select name="status">
        <option value="" disabled selected>กรุณาเลือกสถานะ</option>
        <option value="paid" <?php if ($search_status === 'paid') echo 'selected'; ?>>ชำระเงินแล้ว</option>
        <option value="unpaid" <?php if ($search_status === 'unpaid') echo 'selected'; ?>>ยังไม่มีการชำระเงิน</option>
    </select>
    <div class="button-container">
        <button type="submit" class="search-button">ค้นหา</button>
        <a href="check_payment_slip.php"><button type="button" class="view-all-button">กลับ</button></a>
    </div>
</form>

<?php if ($receipts_result->num_rows > 0): ?>
    <table>
        <tr>
            <th>หมายเลขห้อง</th>
            <th>เดือน</th>
            <th>ปี</th>
            <th>สถานะ</th>
            <th>จัดการ</th>
            <th>สลิปการชำระเงิน</th>
        </tr>
        <?php while ($receipt = $receipts_result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($receipt['room_number']); ?></td>
                <td><?php echo htmlspecialchars($receipt['month']); ?></td>
                <td><?php echo htmlspecialchars($receipt['year']); ?></td>
                <td>
                    <?php
                    // เปลี่ยนสถานะจาก 'paid' เป็น 'ชำระเงินแล้ว' และ 'unpaid' เป็น 'ยังไม่มีการชำระเงิน'
                    echo htmlspecialchars($receipt['status'] === 'paid' ? 'ชำระเงินแล้ว' : 'ยังไม่มีการชำระเงิน');
                    ?>
                </td>
                <td>
                    <?php if ($receipt['status'] === 'unpaid'): ?>
                        <form action="upload_payment_slip.php" method="post" enctype="multipart/form-data">
                            <input type="hidden" name="receipt_id" value="<?php echo $receipt['id']; ?>">
                            <input type="file" name="payment_slip" required>
                        </form>
                    <?php else: ?>
                        <span>ชำระเงินแล้ว</span>
                    <?php endif; ?>
                </td>
                <td>
                <?php if ($receipt['payment_slip_path']): ?>
                    <img src="<?php echo htmlspecialchars($receipt['payment_slip_path']); ?>" alt="Slip Image" width="100">
                <?php else: ?>
                    <span>ยังไม่มีการชำระเงิน</span>
                <?php endif; ?>
</td>
            </tr>
        <?php endwhile; ?>
    </table>

        <!-- Modal สำหรับแสดงรูปใหญ่ -->
    <div id="myModal" class="modal">
    <span class="close">&times;</span>
    <img class="modal-content" id="img01">
    <div id="caption"></div>
    </div>

<?php else: ?>
    <p>ไม่พบข้อมูล</p>
<?php endif; ?>

<script>
// Get the modal
var modal = document.getElementById("myModal");

// Get the image and insert it inside the modal - use its "alt" text as a caption
var modalImg = document.getElementById("img01");
var captionText = document.getElementById("caption");

document.querySelectorAll('img').forEach(img => {
  img.onclick = function(){
    modal.style.display = "block";
    modalImg.src = this.src;
    captionText.innerHTML = this.alt;
  }
});

// Get the <span> element that closes the modal
var span = document.getElementsByClassName("close")[0];

// When the user clicks on <span> (x), close the modal
span.onclick = function() {
  modal.style.display = "none";
}
</script>
</div>
</body>
</html>