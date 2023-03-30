<?php declare(strict_types=1);
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
namespace WorkBunny\EventLoop\Drivers;

use Closure;
use WorkBunny\EventLoop\Exception\InvalidArgumentException;

interface LoopInterface
{
    /**
     * 如无需拓展则返回null
     *
     * @return string|null
     */
    public function getExtName(): null|string;

    /**
     * 是否存在拓展
     *
     * @return bool
     */
    public function hasExt(): bool;

    /**
     * 添加信号处理器
     *
     * @param int $signal
     * @param Closure $handler
     * @throws InvalidArgumentException 当signal参数为非正整数时将抛出该异常
     */
    public function addSignal(int $signal, Closure $handler): void;

    /**
     * 移除信号处理器
     *
     * @param int $signal
     * @throws InvalidArgumentException 当signal参数为非正整数时将抛出该异常
     */
    public function delSignal(int $signal): void;

    /**
     * 添加读流处理器
     *
     * @param resource $stream
     * @param Closure $handler
     */
    public function addReadStream($stream, Closure $handler): void;

    /**
     * 移除读流处理器
     *
     * @param resource $stream
     */
    public function delReadStream($stream): void;

    /**
     * 创建写流处理器
     *
     * @param resource $stream
     * @param Closure $handler
     */
    public function addWriteStream($stream, Closure $handler): void;

    /**
     * 移除写流处理器
     *
     * @param resource $stream
     */
    public function delWriteStream($stream): void;

    /**
     * @Future [delay=0.0, repeat=false]
     *  在下一个周期执行，执行一次即销毁
     * @ReFuture [delay=0.0, repeat=0.0]
     *  在每一个周期执行，不会自动销毁
     * @DelayReFuture [delay>0.0, repeat=0.0]
     *  延迟delay秒后每一个周期执行，不会自动销毁
     * @Delayer [delay>0.0, repeat=false]
     *  延迟delay秒后执行，执行一次即销毁
     * @Timer [delay=0.0, repeat>0.0]
     *  在下一个周期开始每间隔repeat秒执行，不会自动销毁
     * @DelayTimer [delay>0.0, repeat>0.0]
     *  延迟delay秒后每间隔repeat秒执行，不会自动销毁
     *
     * @param float $delay
     * @param float|false $repeat
     * @param Closure $handler
     * @return string
     * @throws InvalidArgumentException delay或repeat为负数时抛出该异常
     */
    public function addTimer(float $delay, float|false $repeat, Closure $handler): string;

    /**
     * 移除定时处理器
     *
     * @param string $timerId
     */
    public function delTimer(string $timerId): void;

    /**
     * 运行loop
     *
     * @return void
     */
    public function run(): void;

    /**
     * 暂停loop
     *
     * @return void
     */
    public function stop(): void;

    /**
     * 与stop()不同的是，该方法会暂停loop并清除所有处理器
     *
     * @return void
     */
    public function destroy(): void;
}
