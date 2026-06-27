@echo off
title Laravel Web Server
set "PATH=C:\Users\Dell\node-v24\node-v24.18.0-win-x64;C:\Users\Dell\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe;C:\Users\Dell\AppData\Local\Microsoft\WinGet\Packages\Git.MinGit_Microsoft.Winget.Source_8wekyb3d8bbwe\cmd;%PATH%"
echo ===================================================
echo Memulai Laravel Web Server pada http://127.0.0.1:8000
echo ===================================================
php artisan serve
pause
