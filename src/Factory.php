<?php
declare(strict_types=1);

namespace EventLoop;

use EventLoop\Drivers\EvLoop;
use EventLoop\Drivers\LoopInterface;
use EventLoop\Drivers\NativeLoop;
use EventLoop\Drivers\EventLoop;
use EventLoop\Exception\LoopException;

final class Factory
{
    /** @var LoopInterface|null */
    protected static ?LoopInterface $_loop = null;

    /** @var array|string[]  */
    protected static array $_drivers = [
        EventLoop::class,
        EvLoop::class,
        NativeLoop::class
    ];

    /**
     * @param string $loop
     * @return LoopInterface
     */
    public static function create(string $loop = NativeLoop::class): LoopInterface
    {
        if(!self::$_loop){
            if(!in_array($loop, self::$_drivers)){
                throw new LoopException('not found :' . $loop);
            }
            self::$_loop = new $loop();
        }
        return self::$_loop;
    }

    /**
     * @param string $loop
     * @return void
     */
    public static function register(string $loop): void
    {
        if((new $loop()) instanceof LoopInterface){
            self::$_drivers[] = $loop;
        }
    }
}