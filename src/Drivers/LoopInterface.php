<?php
declare(strict_types=1);

namespace EventLoop\Drivers;

use EventLoop\Exception\LoopException;

interface LoopInterface
{
    /**
     * 创建信号处理
     * @param int $signal
     * @param callable $handler
     * @throws LoopException
     */
    public function addSignal(int $signal, callable $handler): void;

    /**
     * 移除信号处理
     * @param int $signal
     * @param callable $handler
     */
    public function delSignal(int $signal, callable $handler): void;

    /**
     * 创建读流
     * @param resource $stream
     * @param callable $handler
     * @throws LoopException
     */
    public function addReadStream($stream, callable $handler): void;

    /**
     * 移除读流
     * @param resource $stream
     */
    public function delReadStream($stream): void;

    /**
     * 创建写流
     * @param resource $stream
     * @param callable $handler
     * @throws LoopException
     */
    public function addWriteStream($stream, callable $handler): void;

    /**
     * 移除写流
     * @param resource $stream
     */
    public function delWriteStream($stream): void;

    /**
     * 创建定时器
     * @param float $delay
     * @param float $repeat
     * @param callable $callback
     * @return int
     */
    public function addTimer(float $delay, float $repeat, callable $callback): int;

    /**
     * 移除定时触发器
     * @param int $timerId
     */
    public function delTimer(int $timerId): void;

    /**
     * main loop.
     */
    public function loop(): void;

    /**
     * destroy loop.
     */
    public function destroy(): void;
}
