<?php
declare(strict_types=1);

namespace WorkBunny\Test;

use WorkBunny\EventLoop\Drivers\NativeLoop;

class NativeLoopTest extends AbstractLoopTest
{
    /** 创建循环 */
    public function createLoop(): NativeLoop
    {
        return new NativeLoop();
    }
}
