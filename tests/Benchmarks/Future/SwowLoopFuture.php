<?php declare(strict_types=1);

namespace WorkBunny\Tests\Benchmarks\Future;

use WorkBunny\EventLoop\Drivers\SwowLoop;
use WorkBunny\EventLoop\Loop;

class SwowLoopFuture extends AbstractFuture
{
    public static function run(): void
    {
        @dl('swow');
        $loop = Loop::create(SwowLoop::class);
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