<?php
declare(strict_types=1);

namespace WorkBunny\EventLoop\Protocols;

use WorkBunny\EventLoop\Exception\LoopException;

interface LoopInterface
{
    /**
     * @param resource $stream
     * @param callable $handler
     * @throws LoopException
     */
    public function addReadStream($stream, callable $handler): void;

    /**
     * @param resource $stream
     */
    public function delReadStream($stream): void;

    /**
     * @param resource $stream
     * @param callable $handler
     * @throws LoopException
     */
    public function addWriteStream($stream, callable $handler): void;

    /**
     * @param resource $stream
     */
    public function delWriteStream($stream): void;

    /**
     * @param float $interval
     * @param callable $callback
     * @throws LoopException
     * @return int
     */
    public function addTimer(float $interval, callable $callback): int;

    /**
     * @param float $interval
     * @param callable $callback
     * @throws LoopException
     * @return int
     */
    public function addPeriodicTimer(float $interval, callable $callback): int;

    /**
     * @param int $timerId
     */
    public function delTimer(int $timerId): void;

    /**
     * @param callable $handler
     * @throws LoopException
     * @return int
     */
    public function addFuture(callable $handler): int;

    /**
     * @param int $futureId
     */
    public function delFuture(int $futureId): void;

    /**
     * @param int $signal
     * @param callable $handler
     * @throws LoopException
     */
    public function addSignal(int $signal, callable $handler): void;

    /**
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
