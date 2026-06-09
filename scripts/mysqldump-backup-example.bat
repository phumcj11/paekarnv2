@echo off
REM Example: nightly MySQL backup on Windows (XAMPP)
REM 1) Copy this file and edit MYSQL_USER, MYSQL_PASSWORD, DATABASE_NAME
REM 2) Run manually or schedule via Task Scheduler

set MYSQL_HOME=C:\xampp\mysql\bin
set MYSQL_USER=root
set MYSQL_PASSWORD=
set DATABASE_NAME=paekan

set OUTDIR=%~dp0..\storage\backups
if not exist "%OUTDIR%" mkdir "%OUTDIR%"

set FNAME=%OUTDIR%\db_%DATABASE_NAME%_%date:~-4,4%%date:~-10,2%%date:~7,2%_%time:~0,2%%time:~3,2%%time:~6,2%.sql
set FNAME=%FNAME: =0%

"%MYSQL_HOME%\mysqldump.exe" -u%MYSQL_USER% -p%MYSQL_PASSWORD% --single-transaction --routines %DATABASE_NAME% > "%FNAME%"
echo Backup written to %FNAME%
