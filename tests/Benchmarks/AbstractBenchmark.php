<?php declare(strict_types=1);

namespace WorkBunny\Tests\Benchmarks;

abstract class AbstractBenchmark
{
    /** @var int 初始内存占用量 */
    protected static int $_initialMemoryUsage = 0;

    /** @var float 初始时间 */
    protected static float $_initialTime = 0.0;

    /** @var int 占用的内存 */
    protected static int $_usedMemory = 0;

    /** @var float 占用的时间 */
    protected static float $_duration = 0.0;

    /** @var int 初始计数器 */
    protected static int $_count = 0;

    /**
     * @return void
     */
    public static function construct(): void
    {
        static::$_initialTime        = microtime(true);
        static::$_initialMemoryUsage = memory_get_usage();
        static::run();
        static::$_duration   = microtime(true) - static::$_initialTime;
        static::$_usedMemory = static::$_initialMemoryUsage - memory_get_usage();
    }

    /**
     * @return void
     */
    public static function destruct(): void
    {
        static::$_initialTime        = 0.0;
        static::$_initialMemoryUsage = 0;
        static::$_duration           = 0.0;
        static::$_usedMemory         = 0;
        static::$_count              = 0;
    }

    /**
     * @return int
     */
    public static function getCount(): int
    {
        return self::$_count;
    }

    /**
     * @param int $count
     */
    public static function setCount(int $count): void
    {
        self::$_count = $count;
    }

    /**
     * @return int
     */
    public static function getUsedMemory(): int
    {
        return self::$_usedMemory;
    }

    /**
     * @return float
     */
    public static function getDuration(): float
    {
        return self::$_duration;
    }

    /**
     * @return int
     */
    public static function getInitialMemoryUsage(): int
    {
        return self::$_initialMemoryUsage;
    }

    /**
     * @return float
     */
    public static function getInitialTime(): float
    {
        return self::$_initialTime;
    }



    /**
     * 运行逻辑
     *
     * @return void
     */
    abstract public static function run(): void;
}