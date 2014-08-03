@echo off

mkdir outpng 2>NUL

goto :main

:proc1
GimConv.exe %1 -o outpng/%~n1.png
goto :eof

:main
for %%f in (*.tm2) do CALL :proc1 %%f
pause