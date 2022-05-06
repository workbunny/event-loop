<?php
declare(strict_types=1);

namespace WorkBunny\EventLoop\Drivers;

use WorkBunny\EventLoop\Exception\LoopException;
use WorkBunny\EventLoop\Protocols\AbstractLoop;
use WorkBunny\EventLoop\Utils\Counter;
use EvLoop as BaseEvLoop;
use EvSignal;
use EvTimer;
use EvIo;
use Ev;

class EvLoop extends AbstractLoop
{
    /** @var BaseEvLoop loop */
    protected BaseEvLoop $_loop;

    /** @var Counter 计数器 */
    protected Counter $_timers;

    /**
     * Ev constructor.
     * @throws LoopException
     */
    public function __construct()
    {
        if(!extension_loaded('ev')){
            throw new LoopException('ext-ev not support');
        }
        $this->_timers = new Counter();
        $this->_loop = new BaseEvLoop();
        parent::__construct();
    }

    /** @inheritDoc */
    public function addReadStream($stream, callable $handler): void
    {
        if(is_resource($stream)){
            $event = new EvIo($stream,Ev::READ, $handler);
            $this->_reads[(int)$stream] = $event;
        }
    }

    /** @inheritDoc */
    public function delReadStream($stream): void
    {
        if(is_resource($stream) and isset($this->_reads[(int)$stream])){
            /** @var EvIo $event */
            $event = $this->_reads[(int)$stream];
            $event->stop();
            unset($this->_reads[(int)$stream]);
        }
    }

    /** @inheritDoc */
    public function addWriteStream($stream, callable $handler): void
    {
        if(is_resource($stream)){
            $event = new EvIo($stream, Ev::WRITE, $handler);
            $this->_writes[(int)$stream] = $event;
        }
    }

    /** @inheritDoc */
    public function delWriteStream($stream): void
    {
        if(is_resource($stream) and isset($this->_writes[(int)$stream])){
            /** @var EvIo $event */
            $event = $this->_writes[(int)$stream];
            $event->stop();
            unset($this->_writes[(int)$stream]);
        }
    }

    /** @inheritDoc */
    public function addSignal(int $signal, callable $handler): void
    {
        $event = new EvSignal($signal, $handler);
        $this->_signals[$signal] = $event;
    }

    /** @inheritDoc */
    public function delSignal(int $signal, callable $handler): void
    {
        if(isset($this->_signals[$signal])){
            /** @var EvSignal $event */
            $event = $this->_signals[$signal];
            $event->stop();
            unset($this->_signals[$signal]);
        }
    }

    /** @inheritDoc */
    public function addTimer(float $interval, callable $callback): int
    {
        return $this->_timers->add(
            $event = new EvTimer($interval, 0.0, $callback)
        );
    }

    /** @inheritDoc */
    public function addPeriodicTimer(float $interval, callable $callback): int
    {
        return $this->_timers->add(
            $event = new EvTimer($interval, $interval, $callback)
        );
    }

    /** @inheritDoc */
    public function delTimer(int $timerId): void
    {
        /** @var EvTimer $event */
        if($event = $this->_timers->get($timerId)){
            $event->stop();
            $this->_timers->del($timerId);
        }
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

            $this->_future->tick();

            $flags = Ev::RUN_ONCE;
            if (!$this->_future->isEmpty()) {
                $flags |= Ev::RUN_NOWAIT;
            } elseif (
                !$this->_reads and !$this->_writes and !$this->_signals and $this->_timers->isEmpty()) {
                break;
            }
            $this->_loop->run($flags);
            if($this->_switching){
                usleep(0);
            }
        }
    }

    /** @inheritDoc */
    public function destroy(): void
    {
        $this->_stopped = true;
        $this->_loop->stop();
    }
}