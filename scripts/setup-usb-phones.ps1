# Run after plugging in phones. Sets adb reverse so each phone can use http://127.0.0.1:8080
$devices = adb devices | Select-String "device$" | ForEach-Object { ($_ -split "\s+")[0] } | Where-Object { $_ -ne "List" }

if (-not $devices) {
    Write-Host "No phones detected. Enable USB debugging and connect via USB."
    exit 1
}

foreach ($id in $devices) {
    adb -s $id reverse --remove-all 2>$null
    adb -s $id reverse tcp:8080 tcp:80
    $name = adb -s $id shell getprop ro.product.model 2>$null
    Write-Host "OK $name ($id) -> http://127.0.0.1:8080"
}

Write-Host ""
Write-Host "Server URL on EVERY phone: http://127.0.0.1:8080"
Write-Host "Staff phone:  staff1 / password"
Write-Host "Student phone: maria.santos / password"
Write-Host "Keep XAMPP Apache running on this PC."
