<?php
declare(strict_types=1);

namespace WorkBunny\Tests;

use PHPUnit\Framework\TestCase;
use WorkBunny\EventLoop\Drivers\AbstractLoop;

abstract class AbstractTest extends TestCase
{
    /** @var float 模拟loop间隔1ms */
    public float $tickTimeout = 0.001;
    /** @var ?string */
    protected ?string $received = null;
    /** @var AbstractLoop|null  */
    protected ?AbstractLoop $loop = null;
    /**
     * @var array = [
     *      1 => [$expected, $actual],
     *      2 => [$expected, $actual]
     *  ]
     */
    protected array $info = [];
    protected int $count = 0;
    protected float $startTime = 0.0;
    protected float $endTime = 0.0;

    /**
     * 初始化
     * @return void
     */
    public function setUp(): void
    {
        $this->loop = $this->setLoop();
        $this->tickTimeout = $this->setTickTimeout();

        $this->startTime = $this->endTime = 0.0;
        $this->string = '';
        $this->count = 0;
        $this->info = [];
    }

    /** 创建循环 */
    abstract public function setLoop() : AbstractLoop;

    /** 设置tickTimeout */
    abstract public function setTickTimeout(): float;

    /** 获取循环 */
    public function getLoop(): ? AbstractLoop
    {
        return $this->loop;
    }

    /** 创建socket */
    public function createSocketPair(): bool|array
    {
        return stream_socket_pair(
            (DIRECTORY_SEPARATOR === '\\') ? STREAM_PF_INET : STREAM_PF_UNIX,
            STREAM_SOCK_STREAM,
            STREAM_IPPROTO_IP
        );
    }

    /**
     * 设置开始时间
     * @param float $startTime
     * @return void
     */
    public function setStartTime(float $startTime): void
    {
        $this->startTime = $startTime;
    }

    /**
     * 设置结束时间
     * @param float $endTime
     * @return void
     */
    public function setEndTime(float $endTime): void
    {
        $this->endTime = $endTime;
    }

    /**
     * @return float
     */
    public function getStartTime(): float
    {
        return $this->startTime;
    }

    /**
     * @return float
     */
    public function getEndTime(): float
    {
        return $this->startTime;
    }

    /**
     * 设置计数
     * @param int $count
     * @return void
     */
    public function setCountNum(int $count): void
    {
        $this->count = $count;
    }

    /**
     * 获取计数
     * @return int
     */
    public function getCountNum(): int
    {
        return $this->count;
    }

    /**
     * @param int $count
     * @param mixed $expected
     * @param mixed $actual
     * @return void
     */
    public function addInfo(int $count, mixed $expected, mixed $actual): void
    {
        $this->info[$count] = [$expected, $actual];
    }

    /**
     * @return array
     */
    public function getInfo(): array
    {
        return $this->info;
    }

    /**
     * 运行loop
     * @return void
     */
    public function runLoop(): void
    {
        $this->setStartTime(microtime(true));
        $this->getLoop()->run();
        $this->setEndTime(microtime(true));
    }

    /**
     * 利用@Future模拟单次loop
     * @param float $delay
     * @return void
     */
    public function tickLoop(float $delay = 0.0): void
    {
        $this->getLoop()->addTimer($delay, false, function () {
             $this->getLoop()->stop();
        });
        $this->runLoop();
    }

    /**
     * 断言检查info
     * @return void
     */
    public function assertInfo(): void
    {
        foreach ($this->getInfo() as $count => list($expected, $actual)) {
            $this->assertLessThan($expected, $actual, "Error Count: $count");
        }
    }

    /**
     * 比较时间
     * @param float $maxInterval
     * @return void
     */
    public function assertRunFasterThan(float $maxInterval): void
    {
        $this->runLoop();
        $this->assertGreaterThan($this->getEndTime() - $this->getStartTime(), $maxInterval);
    }
}
