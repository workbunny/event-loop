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

use Ev;
use EvIo;
use EvSignal;
use EvTimer;
use WorkBunny\EventLoop\Exception\LoopException;
use EvLoop as BaseEvLoop;
use Closure;

class EvLoop extends AbstractLoop
{
    /** @var BaseEvLoop loop */
    protected BaseEvLoop $_loop;

    /** @inheritDoc */
    public function __construct()
    {
        if(!extension_loaded('ev')){
            throw new LoopException('ext-ev not support');
        }

        parent::__construct();

        $this->_loop = new BaseEvLoop();
    }

    /** @inheritDoc */
    public function addReadStream($stream, Closure $handler): void
    {
        if(is_resource($stream) and !isset($this->_reads[$key = (int)$stream])){
            $event = $this->_loop->io($stream, Ev::READ, $handler);
            $this->_reads[$key] = $event;
            $this->_readFds[spl_object_hash($event)] = $stream;
        }
    }

    /**
     * @param resource|EvIo $stream
     * @return void
     */
    public function delReadStream($stream): void
    {
        if(is_resource($stream) and isset($this->_reads[$key = (int)$stream])){
            /** @var EvIo $event */
            $event = $this->_reads[$key];
            $event->stop();
            unset(
                $this->_reads[$key],
                $this->_readFds[spl_object_hash($event)]
            );
        }

        if($stream instanceof EvIo and isset($this->_readFds[spl_object_hash($stream)])){
            $stream->stop();
            $key = (int)($this->_readFds[spl_object_hash($stream)]);
            unset(
                $this->_reads[$key],
                $this->_readFds[spl_object_hash($stream)]
            );
        }
    }

    /** @inheritDoc */
    public function addWriteStream($stream, Closure $handler): void
    {
        if(is_resource($stream) and !isset($this->_writes[$key = (int)$stream])){
            $event = $this->_loop->io($stream, Ev::WRITE, $handler);
            $this->_writes[$key] = $event;
            $this->_writeFds[spl_object_hash($event)] = $stream;
        }
    }

    /**
     * @param EvIo|resource $stream
     * @return void
     */
    public function delWriteStream($stream): void
    {
        if(is_resource($stream) and isset($this->_writes[$key = (int)$stream])){
            /** @var EvIo $event */
            $event = $this->_writes[$key];
            $event->stop();
            unset(
                $this->_writes[$key],
                $this->_writeFds[spl_object_hash($event)]
            );
        }

        if($stream instanceof EvIo and isset($this->_writeFds[spl_object_hash($stream)])){
            $stream->stop();
            $key = (int)($this->_writeFds[spl_object_hash($stream)]);
            unset(
                $this->_writes[$key],
                $this->_writeFds[spl_object_hash($stream)]
            );
        }
    }

    /** @inheritDoc */
    public function addSignal(int $signal, Closure $handler): void
    {
        if(!isset($this->_signals[$signal])){
            $event = $this->_loop->signal($signal, $handler);
            $this->_signals[$signal] = $event;
        }
    }

    /** @inheritDoc */
    public function delSignal(int $signal): void
    {
        if(isset($this->_signals[$signal])){
            /** @var EvSignal $event */
            $event = $this->_signals[$signal];
            $event->stop();
            unset($this->_signals[$signal]);
        }
    }

    /** @inheritDoc */
    public function addTimer(float $delay, float $repeat, Closure $callback): string
    {
        $event = $this->_loop->timer($delay, $repeat, function () use (&$event, $repeat, $callback){
            $callback();
            if($repeat === 0.0){
                $this->_storage->del(spl_object_hash($event));
            }
        });
        return $this->_storage->add(spl_object_hash($event), $event);
    }

    /** @inheritDoc */
    public function delTimer(string $timerId): void
    {
        /** @var EvTimer $event */
        if($event = $this->_storage->get($timerId)){
            $event->stop();
            $this->_storage->del($timerId);
        }
    }

    /** @inheritDoc */
    public function loop(): void
    {
        if($this->_storage->isEmpty() and !$this->_reads and !$this->_writes and !$this->_signals){
            return;
        }
        $this->_loop->run();
    }

    /** @inheritDoc */
    public function destroy(): void
    {
        $this->_loop->stop();
    }
}