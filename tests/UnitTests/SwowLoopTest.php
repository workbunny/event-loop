<?php
declare(strict_types=1);

namespace WorkBunny\Tests\UnitTests;

use WorkBunny\EventLoop\Drivers\SwowLoop;

class SwowLoopTest extends AbstractTestCase
{
    /** @inheritDoc */
    public function setLoop(): SwowLoop
    {
        if (!extension_loaded('swow')) {
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
