@echo off & setlocal ENABLEEXTENSIONS

goto :main

:do_one
SET ZPATH=%~dp1
SET ZPATH=%ZPATH:work=extr%
quickbms -o sega-nextech-shining_wind-rax-010.bms %1 %ZPATH%
goto :eof

:main
for /R %%i in (*.rax) DO call :do_one %%i



:end
pause