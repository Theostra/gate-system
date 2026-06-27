@echo off
title Laravel Queue Listener (AI Worker)
set "PATH=C:\Users\Dell\node-v24\node-v24.18.0-win-x64;C:\Users\Dell\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe;C:\Users\Dell\AppData\Local\Microsoft\WinGet\Packages\Git.MinGit_Microsoft.Winget.Source_8wekyb3d8bbwe\cmd;%PATH%"
echo =======================================================
echo Memulai Laravel Queue Listener untuk memproses tugas AI...
echo =======================================================
php artisan queue:listen --tries=1 --timeout=0
pause
