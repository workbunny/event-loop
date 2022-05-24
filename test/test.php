<?php
require_once '../vendor/autoload.php';

$loop = \EventLoop\Factory::create(\EventLoop\Drivers\EventLoop::class);
$id = $loop->addTimer(5.0,1.0,function () use (&$id, $loop){
    dump(microtime(true));
    $loop->delTimer($id);
    $loop->destroy();
});
$loop->loop();