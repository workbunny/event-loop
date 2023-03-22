<?php declare(strict_types=1);

namespace WorkBunny\Tests\Benchmarks\Future;

use WorkBunny\EventLoop\Drivers\EvLoop;
use WorkBunny\EventLoop\Loop;

class EvLoopFuture extends AbstractFuture
{
    public static function run(): void
    {
        $loop = Loop::create(EvLoop::class);
        $loop->addTimer(0.0, 0.0, function () use ($loop){
            $this->setCount($this->getCount() + 1);
            if($this->getInitialTime() + 1 >= microtime(true)){
                return;
            }
            dump(
                get_class($loop) . ' 1s loop-counts: ' . $this->getCount(),
                'Memory Usage: ' . $this->getUsedMemory() . ' B'
            );
            $loop->stop();
        });
        $loop->run();
    }
}