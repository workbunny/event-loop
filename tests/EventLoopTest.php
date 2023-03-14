<?php
declare(strict_types=1);

namespace WorkBunny\Tests;

use WorkBunny\EventLoop\Drivers\EventLoop;
use WorkBunny\Tests\Events\TimerTest;

class EventLoopTest extends AbstractLoopTest
{

    /** 创建循环 */
    public function createLoop(): EventLoop
    {
        if (!extension_loaded('event')) {
            $this->markTestSkipped('ext-event tests skipped because ext-event is not installed.');
        }
        return new EventLoop();
    }

    /**
     * 延迟定时器的优先级
     * @see TimerTest::testDelayRepeatTimerPriority()
     * @return void
     */
    public function testDelayRepeatTimerPriority()
    {
        $string = '';
        $this->getLoop()->addTimer(0.1, 0.1, function () use(&$string){
            $string .= 'timer1' . PHP_EOL;
        });
        $this->getLoop()->addTimer(0.1, 0.1, function () use(&$string){
            $string .= 'timer2' . PHP_EOL;
        });

        # 区别于其他循环，这里需要多等一个模拟周期，否则timer2不能正常输出
        $this->tickLoop(0.1 + $this->tickTimeout);

        $this->assertEquals('timer1' . PHP_EOL . 'timer2' . PHP_EOL, $string);
    }

}
