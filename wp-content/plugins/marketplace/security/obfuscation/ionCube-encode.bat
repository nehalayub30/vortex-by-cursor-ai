@echo off
REM IonCube Encoder Batch Script for VORTEX Marketplace
REM This script runs the IonCube encoder on prepared files

echo VORTEX Marketplace Plugin - IonCube Encoding Script
echo =================================================
echo.

REM Configuration - Modify these paths to match your environment
set IONCUBE_PATH=C:\ioncube\ioncube_encoder.exe
set PLUGIN_ROOT=..\..\
set BUILD_DIR=%PLUGIN_ROOT%build\
set SOURCE_DIR=%BUILD_DIR%to-obfuscate\
set TARGET_DIR=%BUILD_DIR%obfuscated\

REM Check if IonCube encoder exists
if not exist "%IONCUBE_PATH%" (
    echo ERROR: IonCube encoder not found at %IONCUBE_PATH%
    echo Please install IonCube encoder or update the path in this script.
    exit /b 1
)

echo Checking directories...
if not exist "%SOURCE_DIR%" (
    echo ERROR: Source directory not found: %SOURCE_DIR%
    echo Please run the preparation script first.
    exit /b 1
)

REM Create target directory if it doesn't exist
if not exist "%TARGET_DIR%" (
    echo Creating target directory...
    mkdir "%TARGET_DIR%"
)

echo.
echo Starting encoding process...
echo.

REM Read manifest file
set MANIFEST_FILE=%BUILD_DIR%obfuscation-manifest.json
if not exist "%MANIFEST_FILE%" (
    echo ERROR: Manifest file not found: %MANIFEST_FILE%
    echo Please run the preparation script first.
    exit /b 1
)

REM Load license data from a separate file (keep this secure!)
set LICENSE_FILE=%PLUGIN_ROOT%security\obfuscation\license-data.txt
if not exist "%LICENSE_FILE%" (
    echo ERROR: License data file not found: %LICENSE_FILE%
    echo Please create a license data file with your IonCube license information.
    exit /b 1
)

REM Encoding options
set ENCODING_OPTIONS=--callback-file callback.php --with-license %LICENSE_FILE% --optimize max --expire-in 365 --notify-email support@vortexdao.com

REM Process each file from the source directory
for /r "%SOURCE_DIR%" %%f in (*.php) do (
    echo Processing: %%f
    
    REM Determine the relative path
    set "FILE_PATH=%%f"
    set "FILE_PATH=!FILE_PATH:%SOURCE_DIR%=!"
    
    REM Create the directory structure in the target directory
    set "TARGET_FILE_DIR=%TARGET_DIR%!FILE_PATH!"
    set "TARGET_FILE_DIR=!TARGET_FILE_DIR:~0,-4!"
    if not exist "!TARGET_FILE_DIR!" mkdir "!TARGET_FILE_DIR!"
    
    REM Encode the file
    "%IONCUBE_PATH%" %ENCODING_OPTIONS% "%%f" -o "%TARGET_DIR%!FILE_PATH!"
    
    echo Encoded: !FILE_PATH!
    echo.
)

echo.
echo Encoding complete. Encoded files are in %TARGET_DIR%
echo.
echo Post-processing...

REM Copy non-PHP files that might be required
echo Copying non-PHP files...
robocopy "%PLUGIN_ROOT%" "%TARGET_DIR%" /E /XF *.php *.txt *.log *.git *.md /XD .git build security\obfuscation\

echo.
echo Process completed successfully.
echo =================================================
echo.

pause 