# PowerShell script to download Bootstrap and Bootstrap Icons

# Create directories
New-Item -ItemType Directory -Force -Path "public\assets\css"
New-Item -ItemType Directory -Force -Path "public\assets\js"
New-Item -ItemType Directory -Force -Path "public\assets\fonts"

Write-Host "Downloading Bootstrap 5.3.3..." -ForegroundColor Green
Invoke-WebRequest -Uri "https://github.com/twbs/bootstrap/releases/download/v5.3.3/bootstrap-5.3.3-dist.zip" -OutFile "bootstrap.zip"
Expand-Archive -Path "bootstrap.zip" -DestinationPath "temp" -Force

Copy-Item "temp\bootstrap-5.3.3-dist\css\bootstrap.min.css" "public\assets\css\"
Copy-Item "temp\bootstrap-5.3.3-dist\js\bootstrap.bundle.min.js" "public\assets\js\"

Write-Host "Downloading Bootstrap Icons..." -ForegroundColor Green
Invoke-WebRequest -Uri "https://github.com/twbs/icons/releases/download/v1.11.3/bootstrap-icons-1.11.3.zip" -OutFile "bootstrap-icons.zip"
Expand-Archive -Path "bootstrap-icons.zip" -DestinationPath "temp-icons" -Force

Copy-Item "temp-icons\bootstrap-icons-1.11.3\font\bootstrap-icons.css" "public\assets\css\"
Copy-Item "temp-icons\bootstrap-icons-1.11.3\font\fonts\*" "public\assets\fonts\"

# Cleanup
Remove-Item "bootstrap.zip"
Remove-Item "bootstrap-icons.zip"
Remove-Item -Recurse "temp"
Remove-Item -Recurse "temp-icons"

Write-Host "Download complete!" -ForegroundColor Green
Write-Host "Files saved to:" -ForegroundColor Yellow
Write-Host "  - public\assets\css\bootstrap.min.css" -ForegroundColor Cyan
Write-Host "  - public\assets\css\bootstrap-icons.css" -ForegroundColor Cyan
Write-Host "  - public\assets\js\bootstrap.bundle.min.js" -ForegroundColor Cyan
Write-Host "  - public\assets\fonts\" -ForegroundColor Cyan
