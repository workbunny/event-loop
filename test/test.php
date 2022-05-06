<?php
require_once '../vendor/autoload.php';
$loop = \WorkBunny\EventLoop\Loop::factory(\WorkBunny\EventLoop\Loop::EVENT);

$pid = \pcntl_fork();

// For master process.
if ($pid > 0) {
    $loop->addPeriodicTimer(4, function (){
        dump('master:' . microtime(true));
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