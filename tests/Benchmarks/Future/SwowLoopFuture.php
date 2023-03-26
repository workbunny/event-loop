<?php declare(strict_types=1);

namespace WorkBunny\Tests\Benchmarks\Future;

use WorkBunny\EventLoop\Drivers\SwowLoop;
use WorkBunny\EventLoop\Loop;
use WorkBunny\Tests\Benchmarks\AbstractBenchmark;

class SwowLoopFuture extends AbstractBenchmark
{
    public function handler(): void
    {
        dl('swow');
        $loop = Loop::create(SwowLoop::class);
        $loop->addTimer(0.0, 0.0, function () use ($loop){
            $this->setCount($this->getCount() + 1);
            if($this->getInitialTime() + 1 >= microtime(true)){
                return;
            }
            $loop->stop();
        });
        $loop->run();
    }
}