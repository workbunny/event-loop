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

namespace WorkBunny\EventLoop\Drivers;

use Closure;
use WorkBunny\EventLoop\Exception\LoopException;

interface LoopInterface
{
    /**
     * 创建信号处理
     * @param int $signal
     * @param Closure $handler
     * @throws LoopException
     */
    public function addSignal(int $signal, Closure $handler): void;

    /**
     * 移除信号处理
     * @param int $signal
     */
    public function delSignal(int $signal): void;

    /**
     * 创建读流
     * @param resource $stream
     * @param Closure $handler
     * @throws LoopException
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
     * @throws LoopException
     */
    public function addWriteStream($stream, Closure $handler): void;

    /**
     * 移除写流
     * @param resource $stream
     */
    public function delWriteStream($stream): void;

    /**
     * 创建定时器
     * @param float $delay
     * @param float $repeat
     * @param Closure $callback
     * @return string
     */
    public function addTimer(float $delay, float $repeat, Closure $callback): string;

    /**
     * 移除定时触发器
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
