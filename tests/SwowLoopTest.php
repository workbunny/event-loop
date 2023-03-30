<?php
declare(strict_types=1);

namespace WorkBunny\Tests;

use Swoole\Process;
use WorkBunny\EventLoop\Drivers\OpenSwooleLoop;
use WorkBunny\EventLoop\Drivers\SwowLoop;
use WorkBunny\Tests\Events\StreamsTest;
use WorkBunny\Tests\Events\TimerTest;

class SwowLoopTest extends AbstractLoopTest
{
    /** @inheritDoc */
    public function setLoop(): SwowLoop
    {
        if (!extension_loaded('swoow')) {
            $this->markTestSkipped('SwowLoop tests skipped because ext-swow extension is not installed.');
        }
        return new SwowLoop();
    }

    /** @inheritDoc */
    public function setTickTimeout(): float
    {
        return 0.001;
    }
}
