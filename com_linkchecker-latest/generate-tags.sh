#!/bin/sh
#ctags -f tags --languages=PHP -R ../../../dev.joomla.test/joomla3/libraries/f0f/ .
#ctags -f tags --languages=PHP -R --fields=+l ~/www/dev.joomla.test/joomla3/ .
ctags-php -f tags --languages=PHP -R --fields=+l ~/www/dev.joomla.test/joomla3/ .
