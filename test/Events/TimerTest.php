<?php
declare(strict_types=1);

namespace WorkBunny\Test\Events;

trait TimerTest
{

    /** 移除不存在的定时器 */
    public function testRemoveNonExistingTimer()
    {
        $this->getLoop()->delTimer('test');
        $this->tickLoop();
        $this->assertRunFasterThan($this->tickTimeout);
    }

    /** 触发器正常被销毁 */
    public function testNonRepeatTimerDestroyed()
    {
        $this->getLoop()->addTimer(0.0, 0.0, function (){});
        $this->getLoop()->addTimer(0.1, 0.0, function (){
            $this->getLoop()->destroy();
        });

        $this->getLoop()->loop();

        $this->assertEquals(0, $this->getLoop()->getStorage()->count());
    }

    /** 无延迟触发器的优先级 */
    public function testNonDelayNonRepeatTimerPriority()
    {
        $string = '';
        $this->getLoop()->addTimer(0.0, 0.0, function () use(&$string){
            $string .= 'timer1' . PHP_EOL;
        });
        $this->getLoop()->addTimer(0.0, 0.0, function () use(&$string){
            $string .= 'timer2' . PHP_EOL;
        });

        $this->tickLoop($this->tickTimeout);

        $this->assertEquals('timer1' . PHP_EOL . 'timer2' . PHP_EOL, $string);
    }

    /** 延迟触发器的优先级 */
    public function testDelayNonRepeatTimerPriority()
    {
        $string = '';
        $this->getLoop()->addTimer(0.1, 0.0, function () use(&$string){
            $string .= 'timer1' . PHP_EOL;
        });
        $this->getLoop()->addTimer(0.1, 0.0, function () use(&$string){
            $string .= 'timer2' . PHP_EOL;
        });

        $this->tickLoop(0.1 + $this->tickTimeout);

        $this->assertEquals('timer1' . PHP_EOL . 'timer2' . PHP_EOL, $string);
    }

    /** 延迟定时器的优先级 */
    public function testDelayRepeatTimerPriority()
    {
        $string = '';
        $this->getLoop()->addTimer(0.1, 0.1, function () use(&$string){
            $string .= 'timer1' . PHP_EOL;
        });
        $this->getLoop()->addTimer(0.1, 0.1, function () use(&$string){
            $string .= 'timer2' . PHP_EOL;
        });

        $this->tickLoop(0.1);

        $this->assertEquals('timer1' . PHP_EOL . 'timer2' . PHP_EOL, $string);
    }
    
    /** 定时器最大间隔 */
    public function testTimerIntervalCanBeFarInFuture()
    {
        $interval = PHP_INT_MAX / 100000;
        $timer = $this->getLoop()->addTimer((float)$interval,0.0, function () {});
        $this->getLoop()->addTimer(0.0,0.0,function () use ($timer) {
            $this->getLoop()->delTimer($timer);
            $this->getLoop()->destroy();
        });
        $this->assertRunFasterThan($this->tickTimeout);
    }

    /** 延迟触发器器消耗的时长 */
    public function testDelayNonRepeatTimeElapsedByTimer()
    {
        $this->getLoop()->addTimer(0.1, 0.0, function () {

            $this->getLoop()->clear();
            $this->getLoop()->destroy();
        });

        $this->assertRunFasterThan(0.1 + $this->tickTimeout);
    }

    /** 无延迟触发器器消耗的时长 */
    public function testNonDelayNonRepeatTimeElapsedByTimer()
    {
        $this->getLoop()->addTimer(0.0, 0.0, function (){
            $this->getLoop()->clear();
            $this->getLoop()->destroy();
        });

        $this->assertRunFasterThan($this->tickTimeout);
    }

    /** 无延迟定时器消耗的时长 */
    public function testNonDelayTimeElapsedByTimer()
    {
        $count = 0;

        $this->getLoop()->addTimer(0.0, 0.1, function () use (&$count){
            $count ++;
            if($count === 3){
                $this->getLoop()->clear();
                $this->getLoop()->destroy();
            }
        });

        $this->assertRunFasterThan(0.1 * 2 + $this->tickTimeout);
    }

    /** 延迟定时器消耗的时长 */
    public function testDelayTimeElapsedByTimer()
    {
        $count = 0;

        $this->getLoop()->addTimer(0.3, 0.1, function () use (&$count){
            $count ++;
            if($count === 3){
                $this->getLoop()->clear();
                $this->getLoop()->destroy();
            }
        });

        $this->assertRunFasterThan(0.3 + 0.1 * 2 + $this->tickTimeout);
    }

    /** 定时器嵌套 */
    public function testTimerNesting()
    {
        $string = '';
        $this->getLoop()->addTimer(0.0,0.0,
            function () use(&$string){
                $this->getLoop()->addTimer(0.1,0.0,
                    function () use(&$string){
                        $this->getLoop()->addTimer(0.2,0.1,
                            function () use(&$string){
                                $string = 'timer nesting' . PHP_EOL;
                                $this->getLoop()->clear();
                                $this->getLoop()->destroy();
                            }
                        );
                    }
                );
            }
        );
        $this->getLoop()->loop();

        $this->assertEquals('timer nesting' . PHP_EOL , $string);
    }
}