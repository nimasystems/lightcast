@echo off

rem This script will do the following:
rem - check for PHP_COMMAND env, if found, use it.
rem   - if not found detect php, if found use it, otherwise err and terminate

if "%OS%"=="Windows_NT" @setlocal

rem %~dp0 is expanded pathname of the current script under NT
set SCRIPT_DIR=%~dp0

goto init

:init

if "%PHP_COMMAND%" == "" goto no_phpcommand

IF EXIST ".\cmd" (
  %PHP_COMMAND% ".\cmd" %*
) ELSE (
  %PHP_COMMAND% "%SCRIPT_DIR%\cmd" %*
)
goto cleanup

:no_phpcommand
echo ------------------------------------------------------------------------
echo WARNING: Set environment var PHP_COMMAND to the location of your php.exe
echo          executable (e.g. C:\PHP\php.exe).  (assuming php.exe on PATH)
echo ------------------------------------------------------------------------
set PHP_COMMAND=php.exe
goto init

:cleanup
if "%OS%"=="Windows_NT" @endlocal
rem pause
