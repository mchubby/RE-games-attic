@echo off
FOR %%f IN (*.pac) DO (
  process_pac.exe %%f
)