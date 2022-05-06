<?php
declare(strict_types=1);

namespace WorkBunny\EventLoop;

use WorkBunny\EventLoop\Drivers\EventLoop;
use WorkBunny\EventLoop\Drivers\EvLoop;
use WorkBunny\EventLoop\Drivers\NativeLoop;
use WorkBunny\EventLoop\Exception\LoopException;
use WorkBunny\EventLoop\Protocols\AbstractLoop;

final class Loop
{
    const EVENT  = 'event';
    const EV     = 'ev';
    const NATIVE = 'native';

    /** @var array|string[]  */
    protected static array $_loops = [
        self::EV     => EvLoop::class,
        self::EVENT  => EventLoop::class,
        self::NATIVE => NativeLoop::class
    ];

    /** @var AbstractLoop */
    protected static AbstractLoop $_loop;

    /**
     * @param string $loop
     * @return AbstractLoop
     */
    public static function factory(string $loop = self::NATIVE): AbstractLoop
    {
        if(isset(self::$_loops[$loop])){
            self::$_loop = new self::$_loops[$loop];
            return self::$_loop;
        }
        throw new LoopException('not found :' . $loop);
    }
}