@echo off

SET TOOL_AWK=c:\Tools\awk.exe
SET TOOL_CPK=C:\Tools\cri\tools\crifilesystem\cpkmakec.exe
SET TOOL_FIND=c:\Tools\Unix\usr\local\wbin\find_.exe
SET TOOL_TEE=C:\Tools\Unix\usr\local\wbin\tee.exe

SET PARAM_AWKSCR=%~dp0genfilelist.awk

SET FILELIST_LST=%~dp0filelist.lst
SET FILELIST_CSV=%~dp0filelist.csv


:main

IF "%1" == "" (
  echo "Usage: packcpk <directory>"
  goto :end
)

SET DATADIR=%~dp0%1
SET DATACPK=%DATADIR%.out.cpk

del %FILELIST_LST% 2>&1 >NUL
del %FILELIST_CSV% 2>&1 >NUL

pushd %DATADIR%
FOR /D %%f IN (*) DO (
  echo + Processing %%f
  %TOOL_FIND% %%f -type f | %TOOL_TEE% -a %FILELIST_LST% | %TOOL_AWK% -f %PARAM_AWKSCR% >> %FILELIST_CSV%
)

echo + Packing "%FILELIST_CSV%" to "%DATACPK%"
%TOOL_CPK% "%FILELIST_CSV%" "%DATACPK%" -align=2048 -mode=FILENAME

popd
echo Done.

:end
pause
