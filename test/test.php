<?php
require_once '../vendor/autoload.php';
\WorkBunny\EventLoop\Loop::$switching = false;
$loop = \WorkBunny\EventLoop\Loop::factory(\WorkBunny\EventLoop\Loop::EVENT);

$pid = \pcntl_fork();

// For master process.
if ($pid > 0) {
    $loop->addPeriodicTimer(4, function (){
        dump('master-a:' . microtime(true));
    });
    $loop->addPeriodicTimer(1.1,function (){
        dump('master-b:' . microtime(true));
    });
    $loop->loop();
}

// For child processes.
if ($pid === 0) {
    $loop->addPeriodicTimer(2, function (){
        dump('slave:' . microtime(true));
    });
    $loop->loop();
}

$loop->destroy();