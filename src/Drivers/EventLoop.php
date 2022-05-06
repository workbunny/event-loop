<?php
declare(strict_types=1);

namespace WorkBunny\EventLoop\Drivers;

use WorkBunny\EventLoop\Exception\LoopException;
use WorkBunny\EventLoop\Protocols\AbstractLoop;
use WorkBunny\EventLoop\Utils\Counter;
use EventConfig;
use EventBase;
use Event;

class EventLoop extends AbstractLoop
{
    /** @var EventBase  */
    protected EventBase $_eventBase;

    /** @var Counter 计数器 */
    protected Counter $_timers;

    /**
     * Ev constructor.
     * @throws LoopException
     */
    public function __construct()
    {
        if(!extension_loaded('event')){
            throw new LoopException('ext-event not support');
        }
        $config = new EventConfig();
        if (\DIRECTORY_SEPARATOR !== '\\') {
            $config->requireFeatures(\EventConfig::FEATURE_FDS);
        }
        $this->_eventBase = new EventBase($config);
        $this->_timers = new Counter();
        parent::__construct();
    }

    /** @inheritDoc */
    public function addReadStream($stream, callable $handler): void
    {
        if(is_resource($stream)){
            $event = new Event($this->_eventBase, $stream, \Event::READ | \Event::PERSIST, $handler);
            if ($event and $event->add()) {
                $this->_reads[(int)$stream] = $event;
            }
        }
    }

    /** @inheritDoc */
    public function delReadStream($stream): void
    {
        if(is_resource($stream) and isset($this->_reads[(int)$stream])){
            /** @var Event $event */
            $event = $this->_reads[(int)$stream];
            $event->free();
            unset($this->_reads[(int)$stream]);
        }
    }

    /** @inheritDoc */
    public function addWriteStream($stream, callable $handler): void
    {
        if(is_resource($stream)){
            $event = new Event($this->_eventBase, $stream, Event::WRITE | Event::PERSIST, $handler);
            if ($event and $event->add()) {
                $this->_writes[(int)$stream] = $event;
            }
        }
    }

    /** @inheritDoc */
    public function delWriteStream($stream): void
    {
        if(is_resource($stream) and isset($this->_writes[(int)$stream])){
            /** @var Event $event */
            $event = $this->_writes[(int)$stream];
            $event->free();
            unset($this->_writes[(int)$stream]);
        }
    }

    /** @inheritDoc */
    public function addSignal(int $signal, callable $handler): void
    {
        $event = Event::signal($this->_eventBase, $signal, $handler);
        if ($event or $event->add()) {
            $this->_signals[$signal] = $event;
        }

    }

    /** @inheritDoc */
    public function delSignal(int $signal, callable $handler): void
    {
        if(isset($this->_signals[$signal])){
            /** @var Event $event */
            $event = $this->_signals[$signal];
            $event->free();
            unset($this->_signals[$signal]);
        }
    }

    /** @inheritDoc */
    public function addTimer(float $interval, callable $callback): int
    {
        $event = Event::timer($this->_eventBase, $callback);
        $event->add($interval);
        return 0;
    }

    /** @inheritDoc */
    public function addPeriodicTimer(float $interval, callable $callback): int
    {
        $event = new Event($this->_eventBase, -1, Event::TIMEOUT | Event::PERSIST, $callback);
        $event->add($interval);
        return $this->_timers->add($event);
    }

    /** @inheritDoc */
    public function delTimer(int $timerId): void
    {
        /** @var Event $event */
        if($event = $this->_timers->get($timerId)){
            $event->free();
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

            $flags = EventBase::LOOP_ONCE;
            if (!$this->_future->isEmpty()) {
                $flags |= EventBase::LOOP_NONBLOCK;
            } elseif (
                !$this->_reads and !$this->_writes and !$this->_signals and $this->_timers->isEmpty()) {
                break;
            }
            $this->_eventBase->loop($flags);
            usleep(0);
        }
    }

    /** @inheritDoc */
    public function destroy(): void
    {
        $this->_stopped = true;
        $this->_eventBase->stop();
    }
}

