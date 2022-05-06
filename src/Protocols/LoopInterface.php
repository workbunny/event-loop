<?php
declare(strict_types=1);

namespace WorkBunny\EventLoop\Protocols;

use WorkBunny\EventLoop\Exception\LoopException;

/**
 * Interface LoopInterface
 * @package WorkBunny\EventLoop\Protocols
 * @author chaz6chez
 */
interface LoopInterface
{
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
     * 临时性定时触发器
     * @param float $interval
     * @param callable $callback
     * @throws LoopException
     * @return int
     */
    public function addTimer(float $interval, callable $callback): int;

    /**
     * 持久性定时触发器
     * @param float $interval
     * @param callable $callback
     * @throws LoopException
     * @return int
     */
    public function addPeriodicTimer(float $interval, callable $callback): int;

    /**
     * 移除定时触发器
     * @param int $timerId
     */
    public function delTimer(int $timerId): void;

    /**
     * 即时触发器
     * @param callable $handler
     * @throws LoopException
     * @return int
     */
    public function addFuture(callable $handler): int;

    /**
     * 移除即时触发器
     * @param int $futureId
     */
    public function delFuture(int $futureId): void;

    /**
     * 装载信号
     * @param int $signal
     * @param callable $handler
     * @throws LoopException
     */
    public function addSignal(int $signal, callable $handler): void;

    /**
     * 移除信号
     * @param int $signal
     * @param callable $handler
     */
    public function delSignal(int $signal, callable $handler): void;

    /**
     * main loop.
     */
    public function loop(): void;

    /**
     * destroy loop.
     */
    public function destroy(): void;
}
