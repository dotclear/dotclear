<?php
if (!empty($_SERVER['argv'][1]) && in_array($_SERVER['argv'][1], array('mysql','sqlite','pgsql'))
   && is_readable(__DIR__.'/conf/config_'.$_SERVER['argv'][1].'.php')) {

    $_SERVER['DC_RC_PATH'] = __DIR__.'/conf/config_'.$_SERVER['argv'][1].'.php';
} else {
    $_SERVER['DC_RC_PATH'] = __DIR__.'/../inc/config.php';
}

if (empty($_SERVER['DC_RC_PATH'])) {
    die('You must define DC_RC_PATH');
}
$_SERVER['REQUEST_URI'] = 'http://localhost:1080';
$_SERVER['HTTP_HOST'] = 'http';
$_SERVER['SERVER_PORT'] = '1080';

$tmp_pwd = md5(uniqid());  // don't care, user will be deleted
$_POST = array(
    'u_login' => 'admin',
    'u_pwd' =>  $tmp_pwd,
    'u_pwd2' => $tmp_pwd
);

ob_start();
require_once(__DIR__.'/../admin/install/index.php');
ob_end_clean();
