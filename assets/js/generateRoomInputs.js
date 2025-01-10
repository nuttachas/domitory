// generateRoomInputs.js
function generateRoomInputs() {
    var layerCount = document.getElementById('layer').value;
    var roomContainer = document.getElementById('room-container');
    roomContainer.innerHTML = ''; // ล้างข้อมูลเก่าก่อน

    for (var i = 1; i <= layerCount; i++) {
        var floorDiv = document.createElement('div');
        floorDiv.classList.add('input-group');
        floorDiv.innerHTML = '<label for="floor_' + i + '">จำนวนห้องในชั้นที่ ' + i + '</label>' +
                            '<input type="number" name="floor_' + i + '_rooms" placeholder="จำนวนห้องในชั้นที่ ' + i + '" required>';
        roomContainer.appendChild(floorDiv);
    }
}

// เพิ่ม event listener ถ้ามีการเปลี่ยนแปลงใน input 'layer'
document.getElementById('layer').addEventListener('input', generateRoomInputs);