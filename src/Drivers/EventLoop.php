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

use EventConfig;
use EventBase;
use Event;
use WorkBunny\EventLoop\Exception\LoopException;
use Closure;

class EventLoop extends AbstractLoop
{
    /** @var EventBase  */
    protected EventBase $_eventBase;

    /** @inheritDoc */
    public function __construct()
    {
        if(!extension_loaded('event')){
            throw new LoopException('ext-event not support');
        }

        parent::__construct();
        $config = new EventConfig();
        if (\DIRECTORY_SEPARATOR !== '\\') {
            $config->requireFeatures(\EventConfig::FEATURE_FDS);
        }
        $this->_eventBase = new EventBase($config);
    }

    /** @inheritDoc */
    public function addReadStream($stream, Closure $handler): void
    {
        if(is_resource($stream) and !isset($this->_readFds[$key = (int)$stream])){
            $event = new Event($this->_eventBase, $stream, \Event::READ | \Event::PERSIST, $handler);
            if ($event->add()) {
                $this->_reads[$key] = $event;
                $this->_readFds[$key] = $stream;
            }
        }
    }

    /** @inheritDoc */
    public function delReadStream($stream): void
    {
        if(is_resource($stream) and !empty($this->_reads[$key = (int)$stream])){
            /** @var Event $event */
            $event = $this->_reads[$key];
            $event->free();
            unset(
                $this->_reads[$key],
                $this->_readFds[$key]
            );
        }
    }

    /** @inheritDoc */
    public function addWriteStream($stream, Closure $handler): void
    {
        if(is_resource($stream) and !isset($this->_writeFds[$key = (int)$stream])){
            $event = new Event($this->_eventBase, $stream, Event::WRITE | Event::PERSIST, $handler);
            if ($event->add()) {
                $this->_writes[$key] = $event;
                $this->_writeFds[$key] = $stream;
            }
        }
    }

    /** @inheritDoc */
    public function delWriteStream($stream): void
    {
        if(is_resource($stream) and isset($this->_writes[$key = (int)$stream])){
            /** @var Event $event */
            $event = $this->_writes[(int)$stream];
            $event->del();
            unset(
                $this->_writes[$key],
                $this->_writeFds[$key]
            );
        }
    }

    /** @inheritDoc */
    public function addSignal(int $signal, Closure $handler): void
    {
        if(!isset($this->_signals[$signal])){
            $event = new Event($this->_eventBase, $signal, Event::SIGNAL | Event::PERSIST, $handler);
            if ($event->add()) {
                $this->_signals[$signal] = $event;
            }
        }
    }

    /** @inheritDoc */
    public function delSignal(int $signal): void
    {
        if(isset($this->_signals[$signal])){
            /** @var Event $event */
            $event = $this->_signals[$signal];
            $event->del();
            unset($this->_signals[$signal]);
        }
    }

    /** @inheritDoc */
    public function addTimer(float $delay, float $repeat, Closure $callback): string
    {
        $event = new Event($this->_eventBase, -1, \Event::TIMEOUT, function () use(&$event, $repeat, $callback){
            $id = spl_object_hash($event);

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

        return $this->_storage->add(spl_object_hash($event), $event);
    }

    /** @inheritDoc */
    public function delTimer(string $timerId): void
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

