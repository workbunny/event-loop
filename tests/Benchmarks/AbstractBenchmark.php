<?php declare(strict_types=1);

namespace WorkBunny\Tests\Benchmarks;

abstract class AbstractBenchmark
{
    /** @var int 初始内存占用量 */
    protected int $_initialMemoryUsage = 0;

    /** @var float 初始时间 */
    protected float $_initialTime = 0.0;

    /** @var int 占用的内存 */
    protected int $_usedMemory = 0;

    /** @var float 占用的时间 */
    protected float $_duration = 0.0;

    /** @var int 初始计数器 */
    protected int $_count = 0;

    public function __construct()
    {
        $this->setInitialTime(microtime(true));
        $this->setInitialMemoryUsage(memory_get_usage());
        $this->handler();
        $this->getDuration(true);
        $this->getUsedMemory(true);
    }

    /**
     * @return void
     */
    public function clear(): void
    {
        $this->_initialTime        = 0.0;
        $this->_initialMemoryUsage = 0;
        $this->_duration           = 0.0;
        $this->_usedMemory         = 0;
        $this->_count              = 0;
    }

    /**
     * @return int
     */
    public function getInitialMemoryUsage(): int
    {
        return $this->_initialMemoryUsage;
    }

    /**
     * @param int $initialMemoryUsage
     */
    public function setInitialMemoryUsage(int $initialMemoryUsage): void
    {
        $this->_initialMemoryUsage = $initialMemoryUsage;
    }

    /**
     * @return float
     */
    public function getInitialTime(): float
    {
        return $this->_initialTime;
    }

    /**
     * @param float $initialTime
     */
    public function setInitialTime(float $initialTime): void
    {
        $this->_initialTime = $initialTime;
    }

    /**
     * @param bool $set
     * @return int
     */
    public function getUsedMemory(bool $set = true): int
    {
        if($set){
            $this->setUsedMemory(memory_get_usage() - $this->getInitialMemoryUsage());
        }
        return $this->_usedMemory;
    }

    /**
     * @param int $usedMemory
     */
    public function setUsedMemory(int $usedMemory): void
    {
        $this->_usedMemory = $usedMemory;
    }

    /**
     * @param bool $set
     * @return float
     */
    public function getDuration(bool $set = true): float
    {
        if($set){
            $this->setDuration(microtime(true) - $this->getInitialTime());
        }
        return $this->_duration;
    }

    /**
     * @param float $duration
     */
    public function setDuration(float $duration): void
    {
        $this->_duration = $duration;
    }

    /**
     * @return int
     */
    public function getCount(): int
    {
        return $this->_count;
    }

    /**
     * @param int $count
     */
    public function setCount(int $count): void
    {
        $this->_count = $count;
    }

    /**
     * 运行逻辑
     *
     * @return void
     */
    abstract public function handler(): void;
}