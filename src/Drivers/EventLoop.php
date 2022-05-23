<?php
declare(strict_types=1);

namespace EventLoop\Drivers;

use EventConfig;
use EventBase;
use Event;
use EventLoop\Storage;

class EventLoop implements LoopInterface
{
    /** @var array All listeners for read event. */
    protected array $_reads = [];

    /** @var array All listeners for write event. */
    protected array $_writes = [];

    /** @var array Event listeners of signal. */
    protected array $_signals = [];

    /** @var EventBase  */
    protected EventBase $_eventBase;

    /** @var Storage 定时器容器 */
    protected Storage $_storage;

    public function __construct()
    {
        $config = new EventConfig();
        if (\DIRECTORY_SEPARATOR !== '\\') {
            $config->requireFeatures(\EventConfig::FEATURE_FDS);
        }
        $this->_storage = new Storage();
        $this->_eventBase = new EventBase($config);
    }

    /** @inheritDoc */
    public function addReadStream($stream, callable $handler): void
    {
        if(is_resource($stream)){
            $event = new Event($this->_eventBase, $stream, \Event::READ | \Event::PERSIST, $handler);
            if ($event->add()) {
                $this->_reads[(int)$stream] = $event;
            }
        }
    }

    /** @inheritDoc */
    public function delReadStream($stream): void
    {
        if(is_resource($stream) and !empty($this->_reads[(int)$stream])){
            /** @var Event $event */
            $event = $this->_reads[(int)$stream];
            $event->del();
            unset($this->_reads[(int)$stream]);
        }
    }

    /** @inheritDoc */
    public function addWriteStream($stream, callable $handler): void
    {
        if(is_resource($stream)){
            $event = new Event($this->_eventBase, $stream, Event::WRITE | Event::PERSIST, $handler);
            if ($event->add()) {
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
            $event->del();
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
            $event->del();
            unset($this->_signals[$signal]);
        }
    }

    /** @inheritDoc */
    public function addTimer(float $delay, float $repeat, callable $callback): int
    {
        $id = $this->_storage->id();

        $event = new Event($this->_eventBase, -1, \Event::TIMEOUT, function () use($repeat, $id, $callback){
            $callback();

            if($repeat === 0.0){
                $this->_storage->del($id);
            }else{
                $event = new Event($this->_eventBase, -1, \Event::TIMEOUT | \Event::PERSIST, $callback);
                $event->add($repeat);
                $this->_storage->set($id, $event);
            }

        });
        $event->add($delay);

        return $this->_storage->add($event);
    }

    /** @inheritDoc */
    public function delTimer(int $timerId): void
    {
        /** @var Event $events */
        if($event = $this->_storage->get($timerId)){
            $event->del();
            $this->_storage->del($timerId);
        }
    }

    /** @inheritDoc */
    public function loop(): void
    {
        if($this->_storage->isEmpty() and !$this->_reads and !$this->_writes and !$this->_signals){
            return;
        }
        if (\DIRECTORY_SEPARATOR !== '\\') {
            $this->_eventBase->loop(EventBase::STARTUP_IOCP);
        }else{
            $this->_eventBase->loop();
        }
    }

    /** @inheritDoc */
    public function destroy(): void
    {
        $this->_eventBase->stop();
    }
}

