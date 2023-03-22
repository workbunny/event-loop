<?php declare(strict_types=1);

namespace WorkBunny\Tests\Benchmarks\Future;

class WhileFuture extends AbstractFuture
{
    public static function run(): void
    {
        while (true){
            self::setCount(self::getCount() + 1);
            if(self::getInitialTime() + 1 <= microtime(true)) {
                dump(
                    'PHP-While 1s loop-counts: ' . self::getCount(),
                    'Memory Usage: ' . self::getUsedMemory() . ' B'
                );
                break;
            }
        }
    }
}