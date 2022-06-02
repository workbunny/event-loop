<?php
declare(strict_types=1);

namespace WorkBunny\Test;

use Swoole\Process;
use WorkBunny\EventLoop\Drivers\OpenSwooleLoop;
use WorkBunny\Test\Events\StreamsTest;
use WorkBunny\Test\Events\TimerTest;

/**
 * 请关注事件优先级
 *
 * 通过 Process::signal 设置的信号处理回调函数
 * 通过 Timer::tick 和 Timer::after 设置的定时器回调函数
 * 通过 Event::defer 设置的延迟执行函数
 * 通过 Event::cycle 设置的周期回调函数
 *
 * 1.无延迟定时器通过defer模拟的
 */
class OpenSwooleLoopTest extends AbstractLoopTest
{
    /** 创建循环 */
    public function createLoop(): OpenSwooleLoop
    {
        if (!extension_loaded('openswoole')) {
            $this->markTestSkipped('OpenSwooleLoop tests skipped because ext-openswoole extension is not installed.');
        }
        return new OpenSwooleLoop();
    }

    /**
     * @see StreamsTest::testReadStreamHandlerTriggeredMultiTimes()
     * @dataProvider provider
     * @param bool $bio
     * @return void
     */
    public function testReadStreamHandlerTriggeredMultiTimes(bool $bio)
    {
        $this->markTestSkipped('Openswoole fails to trigger multiple stream responses.');
    }

    /**
     * @see StreamsTest::testWriteStreamHandlerTriggeredMultiTimes()
     * @dataProvider provider
     * @param bool $bio
     * @return void
     */
    public function testWriteStreamHandlerTriggeredMultiTimes(bool $bio)
    {
        $this->markTestSkipped('Openswoole fails to trigger multiple stream responses.');
    }
}
