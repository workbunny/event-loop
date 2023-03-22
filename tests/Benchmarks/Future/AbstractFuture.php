<?php declare(strict_types=1);

namespace WorkBunny\Tests\Benchmarks\Future;

use WorkBunny\EventLoop\Drivers\NativeLoop;
use WorkBunny\Tests\Benchmarks\AbstractBenchmark;

abstract class AbstractFuture extends AbstractBenchmark
{
    private static array $_futureTests = [
        WhileFuture::class,
        SleepWhileFuture::class,
        NativeLoop::class,
        EventLoopFuture::class,
        EvLoopFuture::class,
        SwowLoopFuture::class
    ];

    final public static function start(): void
    {
        /** @var AbstractFuture $futureTest */
        foreach (static::$_futureTests as $futureTest) {
            $futureTest::construct();
        }
    }
}
