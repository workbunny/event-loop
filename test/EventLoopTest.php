<?php
declare(strict_types=1);

namespace WorkBunny\Test;

use WorkBunny\EventLoop\Drivers\EventLoop;

class EventLoopTest extends AbstractLoopTest
{
    /** @var null|string */
    private ?string $fifoPath = null;

    /** 创建循环 */
    public function createLoop(): EventLoop
    {
        if (!extension_loaded('event')) {
            $this->markTestSkipped('ext-event tests skipped because ext-event is not installed.');
        }
        return new EventLoop();
    }

    /**
     * @after
     */
    public function tearDownFile()
    {
        if ($this->fifoPath !== null && file_exists($this->fifoPath)) {
            unlink($this->fifoPath);
        }
    }
}
