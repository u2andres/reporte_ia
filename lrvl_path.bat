@ ECHO OFF

REM "letra" del disco actual( C:, D:,...)
REM -------------------------------------
SET DISK=%CD:~0,2%

SET PATH=C:\WINDOWS\system32;C:\WINDOWS;C:\WINDOWS\System32\Wbem;C:\WINDOWS\System32\WindowsPowerShell\v1.0\;C:\WINDOWS\System32\OpenSSH\;C:\ProgramData\chocolatey\bin;%DISK%\eid\Xampp_82\htdocs\desarrollo;%DISK%\eid\Xampp_82\php;%DISK%\Users\azotelo\AppData\Local\ComposerSetup\bin;%DISK%\Program Files\nodejs;%DISK%\eid\Git\cmd;%DISK%\Users\azotelo\Dropbox\SrcBox

REM accion adicional a ejecutar...
REM ------------------------------
IF _%1 == _SERVER (
  php artisan serve
)

