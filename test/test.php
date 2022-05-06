<?php
require_once '../vendor/autoload.php';

$loop = \WorkBunny\EventLoop\Loop::factory(\WorkBunny\EventLoop\Loop::NATIVE);
$loop->addPeriodicTimer(1, function (){
   dump(microtime(true));
});
$loop->loop();