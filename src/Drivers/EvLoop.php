<?php
declare(strict_types=1);

namespace EventLoop\Drivers;

use EventLoop\Exception\LoopException;
use EventLoop\Storage;
use EvLoop as BaseEvLoop;
use Closure;

class EvLoop implements LoopInterface
{
    /** @var array All listeners for read event. */
    protected array $_reads = [];

    /** @var array All listeners for write event. */
    protected array $_writes = [];

    /** @var array Event listeners of signal. */
    protected array $_signals = [];

    /** @var BaseEvLoop loop */
    protected BaseEvLoop $_loop;

    /** @var Storage 计数器 */
    protected Storage $_storage;

    /**
     * Ev constructor.
     * @throws LoopException
     */
    public function __construct()
    {
        if(!extension_loaded('ev')){
            throw new LoopException('ext-ev not support');
        }
        $this->_storage = new Storage();
        $this->_loop = new BaseEvLoop();
    }

    /** @inheritDoc */
    public function addReadStream($stream, Closure $handler): void
    {
        if(is_resource($stream)){
            $event = new \EvIo($stream,\Ev::READ, $handler);
            $this->_reads[(int)$stream] = $event;
        }
    }

    /** @inheritDoc */
    public function delReadStream($stream): void
    {
        if(is_resource($stream) and isset($this->_reads[(int)$stream])){
            /** @var \EvIo $event */
            $event = $this->_reads[(int)$stream];
            $event->stop();
            unset($this->_reads[(int)$stream]);
        }
    }

    /** @inheritDoc */
    public function addWriteStream($stream, Closure $handler): void
    {
        if(is_resource($stream)){
            $event = new \EvIo($stream, \Ev::WRITE, $handler);
            $this->_writes[(int)$stream] = $event;
        }
    }

    /** @inheritDoc */
    public function delWriteStream($stream): void
    {
        if(is_resource($stream) and isset($this->_writes[(int)$stream])){
            /** @var \EvIo $event */
            $event = $this->_writes[(int)$stream];
            $event->stop();
            unset($this->_writes[(int)$stream]);
        }
    }

    /** @inheritDoc */
    public function addSignal(int $signal, Closure $handler): void
    {
        $event = new \EvSignal($signal, $handler);
        $this->_signals[$signal] = $event;
    }

    /** @inheritDoc */
    public function delSignal(int $signal, Closure $handler): void
    {
        if(isset($this->_signals[$signal])){
            /** @var \EvSignal $event */
            $event = $this->_signals[$signal];
            $event->stop();
            unset($this->_signals[$signal]);
        }
    }

    /** @inheritDoc */
    public function addTimer(float $delay, float $repeat, Closure $callback): string
    {
        $event = new \EvTimer($delay, $repeat, $callback);
        return $this->_storage->add(spl_object_hash($event), $event);
    }

    /** @inheritDoc */
    public function delTimer(string $timerId): void
    {
        /** @var \EvTimer $event */
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