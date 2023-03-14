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

namespace WorkBunny\EventLoop\Drivers;

use Closure;
use WorkBunny\EventLoop\Exception\InvalidArgumentException;

interface LoopInterface
{
    /**
     * @return string
     */
    public function getExtName(): string;

    /**
     * @return bool
     */
    public function hasExt(): bool;

    /**
     * 创建信号处理
     * @param int $signal
     * @param Closure $handler
     * @throws InvalidArgumentException 当signal参数为非正整数时将抛出该异常
     */
    public function addSignal(int $signal, Closure $handler): void;

    /**
     * 移除信号处理
     * @param int $signal
     * @throws InvalidArgumentException 当signal参数为非正整数时将抛出该异常
     */
    public function delSignal(int $signal): void;

    /**
     * 创建读流
     * @param resource $stream
     * @param Closure $handler
     */
    public function addReadStream($stream, Closure $handler): void;

    /**
     * 移除读流
     * @param resource $stream
     */
    public function delReadStream($stream): void;

    /**
     * 创建写流
     * @param resource $stream
     * @param Closure $handler
     */
    public function addWriteStream($stream, Closure $handler): void;

    /**
     * 移除写流
     * @param resource $stream
     */
    public function delWriteStream($stream): void;

    /**
     * delay=0.0 && repeat=false : 在下一个loop周期内执行一次callback()；
     * delay=0.0 && repeat=0.0 : 在每一个loop周期内都将执行一次callback()；
     * delay=0.0 && repeat>0.0 : 立即开始间隔为repeat周期的定时任务执行callback()；
     * delay>0.0 && repeat=0.0 : 延迟delay执行一次callback()；
     * delay>0.0 && repeat>0.0 : 延迟delay执行一次callback()后开始间隔为repeat周期的定时任务执行callback()；
     *
     * @param float $delay
     * @param float|false $repeat
     * @param Closure $callback
     * @return string
     * @throws InvalidArgumentException delay或repeat为负数时抛出该异常
     */
    public function addTimer(float $delay, float|false $repeat, Closure $callback): string;

    /**
     * 移除定时触发器
     *
     * @param string $timerId
     */
    public function delTimer(string $timerId): void;

    /**
     * main loop.
     */
    public function loop(): void;

    /**
     * destroy loop.
     */
    public function destroy(): void;
}
