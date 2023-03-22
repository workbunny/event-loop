<?php declare(strict_types=1);

namespace WorkBunny\Tests\Benchmarks\Future;

use WorkBunny\EventLoop\Drivers\EventLoop;
use WorkBunny\EventLoop\Loop;

class EventLoopFuture extends AbstractFuture
{
    public static function run(): void
    {
        $loop = Loop::create(EventLoop::class);
        $loop->addTimer(0.0, 0.0, function () use ($loop){
            self::setCount(self::getCount() + 1);
            if(self::getInitialTime() + 1 >= microtime(true)){
                return;
            }
            dump(
                get_class($loop) . ' 1s loop-counts: ' . self::getCount(),
                'Memory Usage: ' . self::getUsedMemory() . ' B'
            );
            $loop->stop();
        });
        $loop->run();
    }
}