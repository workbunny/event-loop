<?php
declare(strict_types=1);

namespace WorkBunny\Tests\UnitTests;

use WorkBunny\EventLoop\Drivers\NativeLoop;

class NativeLoopTest extends AbstractTestCase
{
    /** 创建循环 */
    public function setLoop(): NativeLoop
    {
        return new NativeLoop();
    }

    /** 设置模拟loop的间隔 */
    public function setTickTimeout(): float
    {
        /** 1ms */
        return 0.02;
    }
}
