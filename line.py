import requests

token = 'PvZvwIwBP1HbV3qXvVzrvSyKV0cT5KSSoUoxXkXgsFw'
headers = {
    'Content-Type': 'application/x-www-form-urlencoded',
    'Authorization': f'Bearer {token}'

}
url = 'https://notify-api.line.me/api/notify'
message =  'ห้อง 101 แจ้งค่าห้องทั้งหมด 5000 บาท สามารถชำระเงินได้ตามลิงค์นี้ https://www.sci.nu.ac.th/coop/index.php'
print(headers)
r = requests.post(url=url, headers=headers, data={'message': message})
