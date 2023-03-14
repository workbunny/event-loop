<?php
/**
 * This file is part of workbunny.
 *
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    chaz6chez<chaz6chez1993@outlook.com>
 * @copyright chaz6chez<chaz6chez1993@outlook.com>
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
use WorkBunny\EventLoop\Drivers\SwowLoop;
use WorkBunny\EventLoop\Exception\DriverExtNotFoundException;
use WorkBunny\EventLoop\Exception\DriverNotFoundException;

final class Loop
{
    /** @var LoopInterface[] */
    protected static array $_loops = [];

    /** @var array|string[]  */
    protected static array $_drivers = [
        EventLoop::class,
        EvLoop::class,
        NativeLoop::class,
        SwowLoop::class,
        OpenSwooleLoop::class
    ];

    /**
     * 创建事件循环
     *
     * @param string $loop
     * @return LoopInterface
     * @throws DriverNotFoundException 驱动未找到
     * @throws DriverExtNotFoundException 驱动未安装php-ext
     */
    public static function create(string $loop = NativeLoop::class): LoopInterface
    {
        if(!isset(self::$_loops[$loop])){
            if(!in_array($loop, self::$_drivers)){
                throw new DriverNotFoundException('not found :' . $loop);
            }
            /**
             * @throws DriverExtNotFoundException
             */
            self::$_loops[$loop] = new $loop();
        }
        return self::$_loops[$loop];
    }

    /**
     * 移除事件循环
     *
     * @param string|null $loop
     * @return void
     */
    public static function remove(?string $loop = null): void
    {
        if($loop === null){
            self::$_loops = [];
            return;
        }
        if(isset(self::$_loops[$loop])){
            unset(self::$_loops[$loop]);
        }
    }

    /**
     * 注册驱动
     *
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