<?php
declare(strict_types=1);

namespace WorkBunny\Test\Events;

trait SignalsTest
{

    /**
     * 移除一个未注册的信号
     * @runInSeparateProcess
     * @return void
     */
    public function testDelSignalNotRegisteredIsNoOp()
    {
        $this->getLoop()->delSignal(2);
        $this->assertTrue(true);
    }

    /**
     * 添加相同的信号
     * @runInSeparateProcess
     * @return void
     */
    public function testAddSameSignal()
    {
        if (
            !function_exists('posix_kill') or
            !function_exists('posix_getpid')
        ) {
            $this->markTestSkipped('Signal test skipped because functions "posix_kill" and "posix_getpid" are missing.');
        }
        $count1 = $count2 = 0;

        $this->getLoop()->addSignal(10, function () use (&$count1) {
            $count1 ++;
        });
        $this->getLoop()->addSignal(10, function () use (&$count2) {
            $count2 ++;
        });

        $this->getLoop()->addTimer(0.0,0.0, function () {
            posix_kill(posix_getpid(), 10);
        });

        $this->getLoop()->addTimer(0.1,0.0, function (){
            $this->getLoop()->delSignal(10);
            $this->getLoop()->destroy();
        });

        $this->getLoop()->loop();

        $this->assertEquals(1, $count1);
        $this->assertEquals(0, $count2);
    }

    /**
     * 信号响应
     * @runInSeparateProcess
     * @return void
     */
    public function testSignalResponse()
    {
        if (
            !function_exists('posix_kill') or
            !function_exists('posix_getpid')
        ) {
            $this->markTestSkipped('Signal test skipped because functions "posix_kill" and "posix_getpid" are missing.');
        }
        $count1 = $count2 = 0;

        $this->getLoop()->addSignal(10, function () use (&$count1) {
            $count1 ++;
        });
        $this->getLoop()->addSignal(12, function () use (&$count2) {
            $count2 ++;
        });

        $this->getLoop()->addTimer(0.0,0.0, function () {
            posix_kill(posix_getpid(), 12);
        });

        $this->getLoop()->addTimer(0.1,0.0, function (){
            $this->getLoop()->delSignal(10);
            $this->getLoop()->delSignal(12);
            $this->getLoop()->destroy();
        });

        $this->getLoop()->loop();

        $this->assertEquals(0, $count1);
        $this->assertEquals(1, $count2);
    }

}