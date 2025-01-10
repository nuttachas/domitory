<?php 
session_start();
include('server.php');

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบหรือไม่
if (!isset($_SESSION['username'])) {
    header('location: index.php');
    exit();
}

// ตรวจสอบว่ามี apartment_id ใน session หรือไม่
if (!isset($_SESSION['apartment_id'])) {
    echo "<p style='color:red;'>กรุณาเลือกหอพักก่อนจัดการข้อมูล</p>";
    exit();
}

$apartment_id = $_SESSION['apartment_id'];

// ตรวจสอบว่าหอพักที่เลือกมีอยู่ในระบบหรือไม่
$apartment_query = "SELECT id FROM apartments WHERE id = ?";
$stmt = $con->prepare($apartment_query);
$stmt->bind_param('i', $apartment_id);
$stmt->execute();
$apartment_result = $stmt->get_result();

if ($apartment_result->num_rows === 0) {
    echo "<p style='color:red;'>หอพักที่เลือกไม่ถูกต้อง กรุณาเลือกหอพักใหม่</p>";
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

// ตรวจสอบใบเสร็จที่มีอยู่ โดยเลือกใบเสร็จล่าสุดของแต่ละห้อง
$receipts_query = "
    SELECT b.*, MONTHNAME(STR_TO_DATE(b.month, '%m')) AS month_name
    FROM billing_statements b
    INNER JOIN (
        SELECT room_number, MAX(created_at) as latest_date
        FROM billing_statements
        WHERE user_id = ? AND apartment_id = ?
        GROUP BY room_number
    ) latest_receipts ON b.room_number = latest_receipts.room_number AND b.created_at = latest_receipts.latest_date
    ORDER BY b.created_at DESC
";

$stmt = $con->prepare($receipts_query);
$stmt->bind_param('ii', $user_id, $apartment_id);
$stmt->execute();
$receipts_result = $stmt->get_result();

// ตรวจสอบการส่งข้อมูลจากฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['receipt_ids']) && !empty($_POST['receipt_ids'])) {
        $receipt_ids = $_POST['receipt_ids'];
        $room_numbers = []; // เพื่อเก็บหมายเลขห้องสำหรับการแจ้งเตือน

        // ดึงข้อมูลใบเสร็จที่เลือกเพื่อนำไปบันทึก
        foreach ($receipt_ids as $receipt_id) {
            // ตรวจสอบว่ามีใบเสร็จที่เลือกอยู่ในตาราง receipts หรือไม่
            $check_receipt_query = "
                SELECT * FROM receipts 
                WHERE room_number = (SELECT room_number FROM billing_statements WHERE id = ?)
                AND month = (SELECT month FROM billing_statements WHERE id = ?)
                AND year = (SELECT year FROM billing_statements WHERE id = ?)
                AND apartment_id = ?
            ";
            $stmt = $con->prepare($check_receipt_query);
            $stmt->bind_param('iiii', $receipt_id, $receipt_id, $receipt_id, $apartment_id);
            $stmt->execute();
            $check_receipt_result = $stmt->get_result();

            // หากไม่มีใบเสร็จในฐานข้อมูล จึงบันทึกใหม่
            if ($check_receipt_result->num_rows == 0) {
                // ดึงข้อมูลใบเสร็จจาก billing_statements
                $query = "SELECT * FROM billing_statements WHERE id = ? AND apartment_id = ?";
                $stmt = $con->prepare($query);
                $stmt->bind_param('ii', $receipt_id, $apartment_id); // ตรวจสอบว่าหมายเลขใบเสร็จตรงกับ apartment_id
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $receipt = $result->fetch_assoc();
                    
                    // บันทึกลงตาราง receipts
                    $insert_query = "
                        INSERT INTO receipts (
                            user_id, apartment_id, room_number, month, year, electricity_cost, water_cost, internet_cost, room_cost, total_cost, 
                            electricity_meter, water_meter, electricity_meter_previous, water_meter_previous
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ";
                    $stmt = $con->prepare($insert_query);
                    $stmt->bind_param(
                        'iissiiiiiiiiii',
                        $receipt['user_id'],
                        $apartment_id,
                        $receipt['room_number'],
                        $receipt['month'],
                        $receipt['year'],
                        $receipt['electricity_cost'],
                        $receipt['water_cost'],
                        $receipt['internet_cost'],
                        $receipt['room_cost'],
                        $receipt['total_cost'],
                        $receipt['electricity_meter'],          // เพิ่มข้อมูลมิเตอร์ไฟฟ้า
                        $receipt['water_meter'],                 // เพิ่มข้อมูลมิเตอร์น้ำ
                        $receipt['electricity_meter_previous'],  // เพิ่มข้อมูลมิเตอร์ไฟฟ้าก่อนหน้า
                        $receipt['water_meter_previous']         // เพิ่มข้อมูลมิเตอร์น้าก่อนหน้า
                    );
                    $stmt->execute();
                    $room_numbers[] = $receipt['room_number']; // เก็บหมายเลขห้อง
                }
            }
        }

        // ส่งการแจ้งเตือนผ่านไลน์
        if (!empty($room_numbers)) {
            $token = 'PvZvwIwBP1HbV3qXvVzrvSyKV0cT5KSSoUoxXkXgsFw';
            $line_api_url = 'https://notify-api.line.me/api/notify';
            $message = "ห้อง " . implode(", ", $room_numbers) . " แจ้งค่าห้องทั้งหมด " . htmlspecialchars(number_format($receipt['total_cost'], 2)) . " บาท สามารถชำระเงินได้ตามลิงค์นี้ https://www.sci.nu.ac.th/coop/index.php";

            // ส่งข้อความ
            $headers = [
                'Content-Type: application/x-www-form-urlencoded',
                'Authorization: Bearer ' . $token
            ];
            $data = [
                'message' => $message
            ];

            // เรียกใช้ cURL เพื่อส่งข้อความ
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $line_api_url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);
            curl_close($ch);
        }

        // เพิ่ม SweetAlert สำหรับแจ้งเตือน
        echo '
            <script src="https://code.jquery.com/jquery-2.1.3.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert-dev.js"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.css">
        <link rel="stylesheet" href="assets/css/SweetAlert.css">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&family=Prompt:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">';
        echo '<script>
            setTimeout(function() {
                swal({
                    title: "ส่งใบเสร็จที่เลือกสำเร็จแล้ว",
                    type: "success"
                }, function() {
                   window.location = "check_receipt.php"; // หน้าที่ต้องการให้กระโดดไป
                });
            }, 1000);
        </script>';
    } else {
        // เพิ่ม SweetAlert สำหรับแจ้งเตือนเมื่อไม่เลือกใบเสร็จ
        echo '
            <script src="https://code.jquery.com/jquery-2.1.3.min.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert-dev.js"></script>
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.css">
        ';
        echo '<script>
            setTimeout(function() {
                swal({
                    title: "กรุณาเลือกใบเสร็จอย่างน้อยหนึ่งรายการ",
                    type: "error"
                }, function() {
                   window.location = "check_receipt.php"; // หน้าที่ต้องการให้กระโดดไป
                });
            }, 1000);
        </script>';
    }
}
?>
