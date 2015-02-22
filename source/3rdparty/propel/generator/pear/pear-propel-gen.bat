@ECHO OFF

:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: The propel-gen build script for Windows based systems
:: $Id: pear-propel-gen.bat 1443 2013-10-08 16:33:03Z mkovachev $
:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

::----------------------------------------------------------------------------------
:: Please set following to the "phing" script.  By default this is expected to be
:: on your path.  (You don't need to modify this file if that is the case.)

SET phingScript=phing

::---------------------------------------------------------------------------------
::---------------------------------------------------------------------------------
:: Do not modify below this line!! (Unless you know what your doing :)
::---------------------------------------------------------------------------------
::---------------------------------------------------------------------------------

set nbArgs=0
for %%x in (%*) do Set /A nbArgs+=1
if %nbArgs% leq 1 (
  "%phingScript%" -f "@DATA-DIR@\propel_generator\pear-build.xml" -Dproject.dir="%CD%" %*
) else (
  "%phingScript%" -f "@DATA-DIR@\propel_generator\pear-build.xml" -Dproject.dir=%*
)
GOTO :EOF

:PAUSE_END
PAUSE