<?php declare(strict_types=1);

namespace WorkBunny\Tests\UnitTests\Units;

use PHPUnit\Framework\Attributes\Medium;
use WorkBunny\EventLoop\Drivers\LoopInterface;

#[Medium]
trait TimerUnit
{
    /**
     * 测试@Future 的创建及自动销毁
     * More Tag @see LoopInterface::addTimer()
     *
     * @return void
     */
    public function testFuture(): void
    {
        // before create @Future
        $this->assertEquals(0, $this->getCountNum());
        $this->assertEquals(0, $this->getLoop()->getStorage()->count());
        // create @Future
        $this->getLoop()->addTimer(0.0, false, function () {
            $this->setCountNum($this->getCountNum() + 1);
        });
        // before loop start
        $this->assertEquals(0, $this->getCountNum());
        $this->assertEquals(1, $this->getLoop()->getStorage()->count());
        // loop start
        $this->tickLoop($this->tickTimeout);

        $this->assertNotEquals(0.0, $startTime = $this->getStartTime());
        $this->assertNotEquals(0.0, $endTime = $this->getEndTime());
        // less or equal 1ms
        $this->assertLessThan($this->tickTimeout, $endTime - $startTime);
        $this->assertEquals(1, $this->getCountNum());
        $this->assertEquals(0, $this->getLoop()->getStorage()->count());
    }

    /**
     * 测试@ReFuture 的创建及销毁
     * More Tag @see LoopInterface::addTimer()
     *
     * @return void
     */
    public function testReFuture(): void
    {
        // before create @ReFuture
        $this->assertEquals(0, $this->getCountNum());
        $this->assertEquals(0, $this->getLoop()->getStorage()->count());
        // create @ReFuture
        $timerId = $this->getLoop()->addTimer(0.0, 0.0, function () use (&$timerId) {
            $this->setCountNum($count = ($this->getCountNum() + 1));

            if($count === 1){
                $this->addInfo($count, $this->tickTimeout, $this->getEndTime() - $this->getStartTime());
            }
            if($count === 2){
                $this->addInfo($count, $this->tickTimeout, $this->getEndTime() - $this->getStartTime());
                // del timer
                $this->getLoop()->delTimer($timerId);
            }
        });
        // before loop run
        $this->assertEquals(0, $this->getCountNum());
        $this->assertEquals(1, $this->getLoop()->getStorage()->count());
        // loop tick run
        $this->tickLoop($this->tickTimeout);
        // after loop run
        $this->assertNotEquals(0.0, $startTime = $this->getStartTime());
        $this->assertNotEquals(0.0, $endTime = $this->getEndTime());
        // assert info
        $this->assertInfo();
        // less or equal 1000ms
        $this->assertLessThan($this->tickTimeout, $endTime - $startTime);
        $this->assertEquals(2, $this->getCountNum());
        $this->assertEquals(0, $this->getLoop()->getStorage()->count());
    }

    /**
     * 测试@DelayReFuture 的创建及销毁
     * More Tag @see LoopInterface::addTimer()
     *
     * @return void
     */
    public function testDelayReFuture(): void
    {
        $this->assertEquals(0, $this->getCountNum());
        $this->assertEquals(0, $this->getLoop()->getStorage()->count());
        // create @DelayReFuture
        $timerId = $this->getLoop()->addTimer(1.0, 0.0, function () use (&$timerId){
            $this->setCountNum($count = ($this->getCountNum() + 1));
            if($count === 1 or $count === 2 or $count === 3){
                $this->addInfo($count, 1.0 + $this->tickTimeout, $this->getEndTime() - $this->getStartTime());
            }
            // del timer
            if($count >= 3){
                $this->getLoop()->delTimer($timerId);
            }
        });
        // before loop run
        $this->assertEquals(0, $this->getCountNum());
        $this->assertEquals(1, $this->getLoop()->getStorage()->count());
        // loop tick run
        $this->tickLoop(1.0 + $this->tickTimeout);
        // after loop run
        $this->assertNotNull($startTime = $this->getStartTime());
        $this->assertNotNull($endTime = $this->getEndTime());
        // assert info
        $this->assertInfo();
        // less or equal 2000ms
        $this->assertLessThan(1.0 + $this->tickTimeout, $endTime - $startTime);
        $this->assertEquals(3, $this->getCountNum());
        $this->assertEquals(0, $this->getLoop()->getStorage()->count());
    }

    /**
     * 测试@Delayer 的创建及自动销毁
     * More Tage @see LoopInterface::addTimer()
     *
     * @return void
     */
    public function testDelayer(): void
    {
        $this->assertEquals(0, $this->getCountNum());
        $this->assertEquals(0, $this->getLoop()->getStorage()->count());
        // create @Delayer
        $this->getLoop()->addTimer(1.0, false, function () {
            $this->setCountNum($this->getCountNum() + 1);
        });
        // before loop run
        $this->assertEquals(0, $this->getCountNum(), 'Before Loop. ');
        $this->assertEquals(1, $this->getLoop()->getStorage()->count());
        // tick loop
        $this->tickLoop(1.0 + $this->tickTimeout);
        // after loop run
        $this->assertNotNull($startTime = $this->getStartTime());
        $this->assertNotNull($endTime = $this->getEndTime());
        // less or equal 2000ms
        $this->assertLessThan(1.0 + $this->tickTimeout, $endTime - $startTime);
        $this->assertEquals(1, $this->getCountNum());
        $this->assertEquals(0, $this->getLoop()->getStorage()->count());
    }

    /**
     * 测试@Timer 的创建及销毁
     * More Tag @see LoopInterface::addTimer()
     *
     * @return void
     */
    public function testTimer(): void
    {
        $this->assertEquals(0, $this->getCountNum());
        $this->assertEquals(0, $this->getLoop()->getStorage()->count());
        // create @Timer
        $timerId = $this->getLoop()->addTimer(0.0, 0.2, function () use (&$timerId){
            $this->setCountNum($count = ($this->getCountNum() + 1));

            if($count === 1){
                $this->addInfo($count, $this->tickTimeout, $this->getEndTime() - $this->getStartTime());
            }
            if($count === 2){
                $this->addInfo($count, 0.2 + $this->tickTimeout, $this->getEndTime() - $this->getStartTime());
            }
            if($count === 3){
                $this->addInfo($count, 0.2 * 2 + $this->tickTimeout, $this->getEndTime() - $this->getStartTime());
            }
            if($count >= 3){
                $this->getLoop()->delTimer($timerId);
            }
        });
        // before loop run
        $this->assertEquals(0, $this->getCountNum());
        $this->assertEquals(1, $this->getLoop()->getStorage()->count());
        // loop run
        $this->tickLoop(0.2 * 2 + $this->tickTimeout);
        // after loop run
        $this->assertNotNull($startTime = $this->getStartTime());
        $this->assertNotNull($endTime = $this->getEndTime());
        // assert info
        $this->assertInfo();
        $this->assertLessThan(0.2 * 2 + $this->tickTimeout, $endTime - $startTime);
        $this->assertEquals(3, $this->getCountNum());
        $this->assertEquals(0, $this->getLoop()->getStorage()->count());
    }

    /**
     * 测试@DelayTimer 的创建及销毁
     * More Tag @see LoopInterface::addTimer()
     *
     * @return void
     */
    public function testDelayTimer(): void
    {
        $this->assertEquals(0, $this->getCountNum());
        $this->assertEquals(0, $this->getLoop()->getStorage()->count());
        // create @DelayTimer
        $timerId = $this->getLoop()->addTimer(0.2, 0.1, function () use (&$timerId){
            $this->setCountNum($count = ($this->getCountNum() + 1));
            if($count === 1){
                $this->addInfo($count, 0.2 + $this->tickTimeout, $this->getEndTime() - $this->getStartTime());
            }
            if($count === 2){
                $this->addInfo($count, 0.2 + 0.1 + $this->tickTimeout, $this->getEndTime() - $this->getStartTime());
            }
            if($count === 3){
                $this->addInfo($count, 0.2 + 0.1 + 0.1 + $this->tickTimeout, $this->getEndTime() - $this->getStartTime());
                $this->getLoop()->delTimer($timerId);
            }
        });
        // before loop run
        $this->assertEquals(0, $this->getCountNum());
        $this->assertEquals(1, $this->getLoop()->getStorage()->count());
        // tick run
        $this->tickLoop( 0.2 + 0.1 + 0.1 + $this->tickTimeout);
        // after loop run
        $this->assertNotNull($startTime = $this->getStartTime());
        $this->assertNotNull($endTime = $this->getEndTime());
        // assert info
        $this->assertInfo();
        $this->assertLessThan(0.2 + 0.1 + 0.1 + $this->tickTimeout, $endTime - $startTime);
        $this->assertEquals(3, $this->getCountNum());
        $this->assertEquals(0, $this->getLoop()->getStorage()->count());
    }

    /**
     * 移除一个不存在的定时器
     *
     * @return void
     */
    public function testRemoveNonExistingTimer(): void
    {
        $this->getLoop()->delTimer('test');
        $this->tickLoop();
        $this->assertLessThan($this->tickTimeout, $this->getEndTime() - $this->getStartTime());
    }

    /**
     * 测试@Future的优先级
     * More Tag @see LoopInterface::addTimer()
     *
     * @return void
     */
    public function testFuturePriority(): void
    {
        $this->expectOutputString('@Future-1' . PHP_EOL . '@Future-2' . PHP_EOL);

        $this->getLoop()->addTimer(0.0, false, function () {
            echo '@Future-1' . PHP_EOL;
        });
        $this->getLoop()->addTimer(0.0, false, function () {
            echo '@Future-2' . PHP_EOL;
        });

        $this->tickLoop($this->tickTimeout);
    }

    /**
     * 测试@ReFuture的优先级
     * More Tag @see LoopInterface::addTimer()
     *
     * @return void
     */
    public function testReFuturePriority(): void
    {
        $this->expectOutputString(
            '@ReFuture-1' . PHP_EOL .
            '@ReFuture-2' . PHP_EOL .
            '@ReFuture-1' . PHP_EOL .
            '@ReFuture-2' . PHP_EOL
        );
        $reFuture1Count = 0;
        $reFuture2Count = 0;
        $reFuture1 = $this->getLoop()->addTimer(0.0, 0.0, function () use(&$reFuture1, &$reFuture1Count) {
            $reFuture1Count ++;
            echo '@ReFuture-1' . PHP_EOL;
            if($reFuture1Count === 2) {
                $this->getLoop()->delTimer($reFuture1);
            }
        });
        $reFuture2 = $this->getLoop()->addTimer(0.0, 0.0, function () use(&$reFuture2, &$reFuture2Count) {
            $reFuture2Count ++;
            echo '@ReFuture-2' . PHP_EOL;
            if($reFuture2Count === 2) {
                $this->getLoop()->delTimer($reFuture2);
            }
        });
        $this->tickLoop($this->tickTimeout);
    }

    /**
     * 测试@DelayReFuture的优先级
     * More Tag @see LoopInterface::addTimer()
     *
     * @return void
     */
    public function testDelayReFuturePriority(): void
    {
        $this->expectOutputString(
            '@DelayReFuture-1' . PHP_EOL .
            '@DelayReFuture-2' . PHP_EOL .
            '@DelayReFuture-1' . PHP_EOL .
            '@DelayReFuture-2' . PHP_EOL
        );
        $DelayReFuture1Count = 0;
        $DelayReFuture2Count = 0;
        $DelayReFuture1 = $this->getLoop()->addTimer(1.0, 0.0, function () use(&$DelayReFuture1, &$DelayReFuture1Count) {
            $DelayReFuture1Count ++;
            echo '@DelayReFuture-1' . PHP_EOL;
            if($DelayReFuture1Count === 2) {
                $this->getLoop()->delTimer($DelayReFuture1);
            }
        });
        $DelayReFuture2 = $this->getLoop()->addTimer(1.0, 0.0, function () use(&$DelayReFuture2, &$DelayReFuture2Count) {
            $DelayReFuture2Count ++;
            echo '@DelayReFuture-2' . PHP_EOL;
            if($DelayReFuture2Count === 2) {
                $this->getLoop()->delTimer($DelayReFuture2);
            }
        });
        $this->tickLoop(1.0 + $this->tickTimeout);
    }

    /**
     * 测试@Delayer的优先级
     * More Tag @see LoopInterface::addTimer()
     *
     * @return void
     */
    public function testDelayerPriority(): void
    {
        $this->expectOutputString(
            '@Delayer-1' . PHP_EOL . '@Delayer-2' . PHP_EOL
        );
        $this->getLoop()->addTimer(1.0, false, function () {
            echo '@Delayer-1' . PHP_EOL;
        });
        $this->getLoop()->addTimer(1.0, false, function () {
            echo '@Delayer-2' . PHP_EOL;
        });
        $this->tickLoop(1.0 + $this->tickTimeout);
    }

    /**
     * 测试@Timer的优先级
     * More Tag @see LoopInterface::addTimer()
     *
     * @return void
     */
    public function testTimerPriority(): void
    {
        $this->expectOutputString(
            '@Timer-1' . PHP_EOL .
            '@Timer-2' . PHP_EOL .
            '@Timer-1' . PHP_EOL .
            '@Timer-2' . PHP_EOL
        );
        $timer1Count = 0;
        $timer2Count = 0;
        $timer1 = $this->getLoop()->addTimer(0.0, 1.0, function () use (&$timer1, &$timer1Count){
            $timer1Count ++;
            echo '@Timer-1' . PHP_EOL;
            if($timer1Count === 2){
                $this->getLoop()->delTimer($timer1);
            }
        });
        $timer2 = $this->getLoop()->addTimer(0.0, 1.0, function () use (&$timer2, &$timer2Count){
            $timer2Count ++;
            echo '@Timer-2' . PHP_EOL;
            if($timer2Count === 2){
                $this->getLoop()->delTimer($timer2);
            }
        });
        $this->tickLoop(1.0 + $this->tickTimeout);
    }

    /**
     * 测试@DelayTimer的优先级
     * More Tag @see LoopInterface::addTimer()
     *
     * @return void
     */
    public function testDelayTimerPriority(): void
    {
        $this->expectOutputString(
            '@DelayTimer-1' . PHP_EOL .
            '@DelayTimer-2' . PHP_EOL .
            '@DelayTimer-1' . PHP_EOL .
            '@DelayTimer-2' . PHP_EOL
        );
        $delayTimer1Count = 0;
        $delayTimer2Count = 0;
        $delayTimer1 = $this->getLoop()->addTimer(0.5, 1.0, function () use(&$delayTimer1, &$delayTimer1Count){
            $delayTimer1Count ++;
            echo '@DelayTimer-1' . PHP_EOL;
            if($delayTimer1Count === 2){
                $this->getLoop()->delTimer($delayTimer1);
            }
        });
        $delayTimer2 = $this->getLoop()->addTimer(0.5, 1.0, function () use(&$delayTimer2, &$delayTimer2Count){
            $delayTimer2Count ++;
            echo '@DelayTimer-2' . PHP_EOL;
            if($delayTimer2Count === 2){
                $this->getLoop()->delTimer($delayTimer2);
            }
        });
        $this->tickLoop(0.5 + 1.0 + $this->tickTimeout);
    }

    /**
     * 测试定时器最大间隔的设置是否正常
     *
     * @return void
     */
    public function testTimerIntervalCanBeFarInFuture(): void
    {
        $interval = PHP_INT_MAX / 100000;
        // set delay
        $timer = $this->getLoop()->addTimer((float)$interval,0.0, function () {});
        // assert timer isset
        $this->assertEquals(true, $this->getLoop()->getStorage()->exist($timer));
        // tick run
        $this->tickLoop($this->tickTimeout);
        // assert timer deleted
        $this->assertTrue($this->getLoop()->getStorage()->exist($timer));
        // clear
        $this->getLoop()->clear();
        $this->assertFalse($this->getLoop()->getStorage()->exist($timer));

        // set repeat
        $timer = $this->getLoop()->addTimer(0.0, (float)$interval, function () {});
        // assert timer isset
        $this->assertEquals(true, $this->getLoop()->getStorage()->exist($timer));
        // tick run
        $this->tickLoop($this->tickTimeout);
        // assert timer deleted
        $this->assertTrue($this->getLoop()->getStorage()->exist($timer));
        // clear
        $this->getLoop()->clear();
        $this->assertFalse($this->getLoop()->getStorage()->exist($timer));
    }

    /**
     * 定时器嵌套
     *
     * @return void
     */
    public function testTimerNesting(): void
    {
        $this->expectOutputString('timer nesting' . PHP_EOL);
        $this->getLoop()->addTimer(0.0,false,
            function () use(&$string){
                $this->getLoop()->addTimer(0.1,false,
                    function () use(&$string){
                        $this->getLoop()->addTimer(0.2,0.1,
                            function () use(&$string){
                                echo 'timer nesting' . PHP_EOL;
                                $this->getLoop()->destroy();
                            }
                        );
                    }
                );
            }
        );
        $this->getLoop()->run();
    }
}