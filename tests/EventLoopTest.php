<?php
declare(strict_types=1);

namespace WorkBunny\Tests;

use WorkBunny\EventLoop\Drivers\EventLoop;

class EventLoopTest extends AbstractLoopTest
{

    /** @inheritDoc */
    public function setLoop(): EventLoop
    {
        if (!extension_loaded('event')) {
            $this->markTestSkipped('ext-event tests skipped because ext-event is not installed.');
        }
        return new EventLoop();
    }

    /** @inheritDoc */
    public function setTickTimeout(): float
    {
        return 0.02; //20ms
    }
}
