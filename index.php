<?php
define('ROOT_DIR',realpath(dirname(__FILE__)));

require_once ROOT_DIR.'/vendor/autoload.php';

require(ROOT_DIR.'/app/base/kernel.php');
kernel::boot();