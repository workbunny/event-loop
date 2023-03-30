<?php declare(strict_types=1);

namespace WorkBunny\Tests\Events;

trait SignalsTest
{

    /**
     * 移除一个未注册的信号
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testDelSignalNotRegisteredIsNoOp(): void
    {
        $this->getLoop()->delSignal(2);
        $this->assertTrue(true);
    }

    /**
     * 添加相同的信号
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testAddSameSignal(): void
    {
        if (!extension_loaded('posix')) {
            $this->markTestSkipped('Signal test skipped because ext-posix are missing.');
        }
        $count1 = $count2 = 0;

        $this->getLoop()->addSignal(10, function ($signal) use (&$count1) {
            $count1 ++;
            $this->getLoop()->delSignal($signal);
        });
        $this->getLoop()->addSignal(10, function ($signal) use (&$count2) {
            $count2 ++;
            $this->getLoop()->delSignal($signal);
        });

        $this->getLoop()->addTimer(0.1,false, function () {
            \posix_kill(\getmypid(), 10);
        });
        $this->getLoop()->addTimer(0.5,false, function () {
            $this->getLoop()->stop();
        });

        $this->getLoop()->run();

        $this->assertEquals(1, $count1);
        $this->assertEquals(0, $count2);
    }

    /**
     * 信号响应
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testSignalResponse(): void
    {
        if (!extension_loaded('posix')) {
            $this->markTestSkipped('Signal test skipped because ext-posix are missing.');
        }
        $count1 = $count2 = 0;

        $this->getLoop()->addSignal(10, function ($signal) use (&$count1) {
            $count1 ++;
            $this->getLoop()->delSignal($signal);
        });
        $this->getLoop()->addSignal(12, function ($signal) use (&$count2) {
            $count2 ++;
            $this->getLoop()->delSignal($signal);
        });

        $this->getLoop()->addTimer(0.1,false, function () {
            \posix_kill(\getmypid(), 12);
        });

        $this->getLoop()->addTimer(0.5,false, function () {
            $this->getLoop()->stop();
        });

        $this->getLoop()->run();

        $this->assertEquals(0, $count1);
        $this->assertEquals(1, $count2);
    }

}