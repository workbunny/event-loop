<?php
/**
 * This file is part of workbunny.
 *
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    chaz6chez<250220719@qq.com>
 * @copyright chaz6chez<250220719@qq.com>
 * @link      https://github.com/workbunny/event-loop
 * @license   https://github.com/workbunny/event-loop/blob/main/LICENSE
 */
declare(strict_types=1);

namespace WorkBunny\EventLoop;

use WorkBunny\EventLoop\Drivers\EvLoop;
use WorkBunny\EventLoop\Drivers\LoopInterface;
use WorkBunny\EventLoop\Drivers\NativeLoop;
use WorkBunny\EventLoop\Drivers\EventLoop;
use WorkBunny\EventLoop\Drivers\OpenSwooleLoop;
use WorkBunny\EventLoop\Exception\LoopException;

final class Loop
{
    /** @var LoopInterface|null */
    protected static ?LoopInterface $_loop = null;

    /** @var array|string[]  */
    protected static array $_drivers = [
        EventLoop::class,
        EvLoop::class,
        NativeLoop::class,
        OpenSwooleLoop::class
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