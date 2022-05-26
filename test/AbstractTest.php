<?php
declare(strict_types=1);

namespace WorkBunny\Test;

use PHPUnit\Framework\TestCase;
use WorkBunny\EventLoop\Drivers\LoopInterface;

abstract class AbstractTest extends TestCase
{
    /**
     * @var LoopInterface|null
     */
    protected ?LoopInterface $loop = null;

    /** 创建循环 */
    abstract public function createLoop();

    /** 获取循环 */
    protected function getLoop(): ? LoopInterface
    {
        return $this->loop;
    }

    /** 单次loop */
    protected function tickLoop(float $delay = 0.0)
    {
        $this->getLoop()->addTimer($delay, 0.0, function () {
            $this->getLoop()->destroy();
        });

        $this->getLoop()->loop();
    }

    /** 比较时间 */
    protected function assertRunSlowerThan(LoopInterface $loop, float $minInterval)
    {
        $start = microtime(true);

        $this->getLoop()->loop();

        $end = microtime(true);
        $interval = $end - $start;

        $this->assertLessThan($interval, $minInterval);
    }

    /** 比较时间 */
    protected function assertRunFasterThan(float $maxInterval)
    {
        $start = microtime(true);

        $this->getLoop()->loop();

        $end = microtime(true);
        $interval = $end - $start;

        $this->assertLessThan($maxInterval, $interval);
    }
}
