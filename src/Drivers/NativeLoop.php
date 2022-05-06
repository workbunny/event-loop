<?php
declare(strict_types=1);

namespace WorkBunny\EventLoop\Drivers;

use WorkBunny\EventLoop\Exception\LoopException;
use WorkBunny\EventLoop\Protocols\AbstractLoop;
use WorkBunny\EventLoop\Utils\Timer;

class NativeLoop extends AbstractLoop
{
    /** @var resource[] */
    protected array $_readFds;

    /** @var resource[] */
    protected array $_writeFds;

    /** @var Timer timers */
    protected Timer $_timers;

    /**
     * Ev constructor.
     * @throws LoopException
     */
    public function __construct()
    {
        $this->_timers = new Timer();
        $this->_readFds = [];
        $this->_writeFds = [];
        if(!extension_loaded('pcntl')){
            throw new LoopException('not support: ext-pcntl');
        }
        parent::__construct();
    }

    /** @inheritDoc */
    public function addReadStream($stream, callable $handler): void
    {
        if(is_resource($stream)){
            $key = (int) $stream;
            if (!isset($this->_readFds[$key])) {
                $this->_readFds[$key] = $stream;
                $this->_reads[$key] = $handler;
            }
        }
    }

    /** @inheritDoc */
    public function delReadStream($stream): void
    {
        if(is_resource($stream)){
            $key = (int)$stream;
            unset(
                $this->_reads[$key],
                $this->_readFds[$key]
            );
        }
    }

    /** @inheritDoc */
    public function addWriteStream($stream, callable $handler): void
    {
        if(is_resource($stream)){
            $key = (int) $stream;
            if (!isset($this->_writeFds[$key])) {
                $this->_writeFds[$key] = $stream;
                $this->_writes[$key] = $handler;
            }
        }
    }

    /** @inheritDoc */
    public function delWriteStream($stream): void
    {
        if(is_resource($stream)){
            $key = (int)$stream;
            unset(
                $this->_writes[$key],
                $this->_writeFds[$key]
            );
        }
    }

    /** @inheritDoc */
    public function addSignal(int $signal, callable $handler): void
    {
        $this->_signals[$signal] = $handler;
        \pcntl_signal($signal, function($signal){
            $this->_signals[$signal]($signal);
        });
    }

    /** @inheritDoc */
    public function delSignal(int $signal, callable $handler): void
    {
        unset($this->_signals[$signal]);
        \pcntl_signal($signal, \SIG_IGN);
    }

    /** @inheritDoc */
    public function addTimer(float $interval, callable $callback): int
    {
        return $this->_timers->add($interval, $callback, false);
    }

    /** @inheritDoc */
    public function addPeriodicTimer(float $interval, callable $callback): int
    {
        return $this->_timers->add($interval, $callback, true);
    }

    /** @inheritDoc */
    public function delTimer(int $timerId): void
    {
        $this->_timers->del($timerId);
    }

    /** @inheritDoc */
    public function addFuture(callable $handler): int
    {
        return $this->_future->add($handler);
    }

    /** @inheritDoc */
    public function delFuture(int $futureId): void
    {
        $this->_future->del($futureId);
    }

    /** @inheritDoc */
    public function loop(): void
    {
        $this->_stopped = false;
        while (!$this->_stopped) {
            if($this->_stopped){
                break;
            }

            \pcntl_signal_dispatch();

            $this->_future->tick();
            $this->_timers->tick();

            if(
                !$this->_readFds and
                !$this->_writeFds and
                $this->_future->isEmpty() and
                $this->_timers->isEmpty()
            ){
                break;
            }

            $writes = $this->_writeFds;
            $reads = $this->_readFds;
            $excepts = [];
            foreach ($writes as $key => $socket) {
                if (!isset($reads[$key]) && @\ftell($socket) === 0) {
                    $excepts[$key] = $socket;
                }
            }

            try {
                @stream_select($reads, $writes, $excepts, 1);
            } catch (\Throwable $e) {}

            foreach ($reads as $stream) {
                $key = (int)$stream;
                if (isset($this->_reads[$key])) {
                    $this->_reads[$key]($stream);
                }
            }

            foreach ($writes as $stream) {
                $key = (int)$stream;
                if (isset($this->_writes[$key])) {
                    $this->_writes[$key]($stream);
                }
            }
            usleep(0);
        }
    }

    /** @inheritDoc */
    public function destroy(): void
    {
        $this->_stopped = true;
    }
}
