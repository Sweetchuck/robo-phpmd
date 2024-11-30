<?php

if (version_compare(\PHP_VERSION, '8.4', '>=')) {
    ini_set('error_reporting', \E_ALL & ~\E_DEPRECATED);
}
