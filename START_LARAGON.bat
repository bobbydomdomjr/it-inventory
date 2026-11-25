@echo off
setlocal
cd /d "%~dp0"
set "PORT=8080"
if not "%~1"=="" set "PORT=%~1"

echo ==========================================
echo IT INVENTORY MANAGEMENT SYSTEM
echo Laravel local browser launcher
echo ==========================================
echo.

where php >nul 2>nul
if errorlevel 1 (
  echo ERROR: PHP was not found in PATH.
  echo Open Laragon Terminal or add Laragon PHP to PATH.
  pause
  exit /b 1
)

php -v
if errorlevel 1 (
  echo ERROR: PHP could not run.
  pause
  exit /b 1
)

if not exist ".env" (
  echo ERROR: .env is missing.
  echo Run: copy .env.example .env
  echo Then configure DB_DATABASE, DB_USERNAME, DB_PASSWORD and APP_URL.
  pause
  exit /b 1
)

if not exist "vendor\autoload.php" (
  echo ERROR: Composer dependencies are missing.
  echo Run: composer install --no-interaction --prefer-dist
  pause
  exit /b 1
)

echo.
echo Checking required PHP extensions...
php -r "foreach (['pdo_mysql','mbstring','dom','xml','xmlwriter','zip','curl','gd'] as $e) { if (!extension_loaded($e)) { fwrite(STDERR, 'MISSING: '.$e.PHP_EOL); } }"
echo.
echo Confirm MySQL is running and the database configured in .env exists.
echo Browser URL: http://127.0.0.1:%PORT%
echo Press Ctrl+C in this window to stop the server.
echo.

start "" "http://127.0.0.1:%PORT%/login"
php artisan serve --host=127.0.0.1 --port=%PORT%
pause
