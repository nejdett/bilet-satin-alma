# WSL 2 Kurulum Script
# PowerShell'i ADMIN olarak çalıştır ve bu scripti çalıştır

Write-Host "WSL 2 kuruluyor..." -ForegroundColor Green

# WSL'i etkinleştir
dism.exe /online /enable-feature /featurename:Microsoft-Windows-Subsystem-Linux /all /norestart

# Virtual Machine Platform'u etkinleştir
dism.exe /online /enable-feature /featurename:VirtualMachinePlatform /all /norestart

Write-Host "" -ForegroundColor Yellow
Write-Host "ÖNEMLI: Bilgisayarını yeniden başlat!" -ForegroundColor Red
Write-Host "Sonra Docker Desktop'ı kur." -ForegroundColor Yellow

# WSL 2'yi varsayılan yap (restart sonrası)
wsl --set-default-version 2

Read-Host "Devam etmek için Enter'a bas"


