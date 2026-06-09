@echo off
chcp 65001 >nul
echo.
echo === Paekarn Deploy ===
echo.

:: รับ commit message (ถ้าไม่ใส่ ใช้วันที่+เวลา)
set "MSG=%~1"
if "%MSG%"=="" (
    for /f "tokens=1-6 delims=/: " %%a in ("%date% %time%") do (
        set "MSG=update %%c-%%b-%%a %%d:%%e"
    )
)

git add -A
git status --short
echo.
echo Committing: %MSG%
git commit -m "%MSG%"
git push origin main
echo.
echo Done! Check: https://github.com/phumcj11/paekarnv2/actions
echo.
