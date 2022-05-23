<?php
require_once '../vendor/autoload.php';

$loop = \EventLoop\Factory::create(\EventLoop\Drivers\NativeLoop::class);
$loop->addTimer(5.0,1.0,function (){
    dump(microtime(true));
});
$loop->loop();