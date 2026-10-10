@echo off
chcp 65001 >nul
title Kaniva - Local Server
cd /d "%~dp0"

echo.
echo  ==========================================
echo    Kaniva Bangladesh - localhost
echo  ==========================================
echo.

if not exist "artisan" (
    echo  [!] Age zip file-ti Extract korun, tarpor extract kora folder theke ei file chalan.
    echo.
    pause
    exit /b 1
)

rem ---- Find a PHP 8.2+ (C:\php, Laragon, PATH, XAMPP)
set "PHP="
set "PHPARGS="
for %%P in ("C:\php\php.exe" "C:\php83\php.exe" "C:\php84\php.exe" "C:\php82\php.exe") do (
    if not defined PHP if exist %%P call :try "%%~P" portable
)
if not defined PHP for /d %%D in ("C:\laragon\bin\php\php-8*") do if not defined PHP call :try "%%D\php.exe"
if not defined PHP for /f "delims=" %%W in ('where php 2^>nul') do if not defined PHP call :try "%%W"
if not defined PHP if exist "C:\xampp\php\php.exe" call :try "C:\xampp\php\php.exe"

if not defined PHP (
    echo  [!] PHP 8.2 ba tar beshi paoa jayni. ^(XAMPP-er PHP 8.0 Laravel 12-er jonno purono^)
    echo.
    echo  Shohoj upay:
    echo   1. https://windows.php.net/download/ theke "PHP 8.3 ... VS16 x64 Thread Safe" Zip namun
    echo   2. Zip-ti C:\php folder-e extract korun  ^(jeno C:\php\php.exe thake^)
    echo   3. Abar ei file-e double-click korun
    echo.
    start "" "https://windows.php.net/download/"
    pause
    exit /b 1
)
echo  PHP: %PHP%
"%PHP%" %PHPARGS% -r "echo '  Version: '.PHP_VERSION.PHP_EOL;"

rem ---- First run: .env, database, admin
if not exist ".env" (
    echo  Prothombar setup hocche...
    copy /y ".env.example" ".env" >nul
    powershell -NoProfile -Command "(Get-Content .env) -replace '^APP_ENV=.*','APP_ENV=local' -replace '^APP_DEBUG=.*','APP_DEBUG=true' -replace '^APP_URL=.*','APP_URL=http://127.0.0.1:8000' -replace '^DB_CONNECTION=.*','DB_CONNECTION=sqlite' -replace '^DB_(HOST|PORT|DATABASE|USERNAME|PASSWORD)=.*','' -replace '^SESSION_SECURE_COOKIE=.*','SESSION_SECURE_COOKIE=false' -replace '^LOG_CHANNEL=.*','LOG_CHANNEL=single' | Set-Content .env -Encoding UTF8"
    "%PHP%" %PHPARGS% artisan key:generate --force || goto :failed
)
if not exist "database\database.sqlite" (
    type nul > "database\database.sqlite"
    "%PHP%" %PHPARGS% artisan migrate --seed --force || (del "database\database.sqlite" & goto :failed)
    "%PHP%" %PHPARGS% artisan kaniva:admin admin@kaniva.local --password=admin12345
)

echo.
echo  Site:   http://127.0.0.1:8000
echo  Admin:  http://127.0.0.1:8000/auth   (admin@kaniva.local / admin12345)
echo  Ei window khola rakhun. Bondho korle site-o bondho hobe.
echo.
rem open the browser a few seconds later, after the server is up
start "" /min powershell -NoProfile -WindowStyle Hidden -Command "Start-Sleep 3; Start-Process 'http://127.0.0.1:8000'"
pushd public
"%PHP%" %PHPARGS% -S 127.0.0.1:8000 ..\vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php
popd
pause
exit /b 0

:try
rem %1 = php.exe, %2 = "portable" when it has no php.ini of its own
set "ARGS="
if "%~2"=="portable" if not exist "%~dp1php.ini" set ARGS=-n -d extension_dir="%~dp1ext" -d extension=mbstring -d extension=openssl -d extension=fileinfo -d extension=pdo_sqlite -d extension=sqlite3 -d extension=curl -d memory_limit=512M
"%~1" %ARGS% -r "exit(version_compare(PHP_VERSION,'8.2.0','>=') && extension_loaded('pdo_sqlite') && extension_loaded('mbstring') && extension_loaded('openssl') ? 0 : 1);" >nul 2>nul
if not errorlevel 1 (
    set "PHP=%~1"
    set "PHPARGS=%ARGS%"
)
exit /b 0

:failed
echo.
echo  [!] Setup-e somossa hoyeche. Upore lal/sada lekhar screenshot Claude-ke pathan.
pause
exit /b 1
