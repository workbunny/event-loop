<?php
declare(strict_types=1);

namespace WorkBunny\EventLoop\Drivers;

use Swoole\Event;
use Swoole\Process;
use Swoole\Timer;
use WorkBunny\EventLoop\Exception\LoopException;
use WorkBunny\EventLoop\Timer as TimerObj;

class OpenSwooleLoop extends AbstractLoop
{

    /** @inheritDoc */
    public function __construct()
    {
        if(!extension_loaded('openswoole')){
            throw new LoopException('not support: ext-openswoole');
        }
        if(!extension_loaded('pcntl')){
            throw new LoopException('not support: ext-pcntl');
        }
        parent::__construct();
    }

    /** @inheritDoc */
    public function addReadStream($stream, \Closure $handler): void
    {
        if(is_resource($stream) and !isset($this->_readFds[$key = (int)$stream])){
            Event::add($stream,$handler,null,SWOOLE_EVENT_READ);
            $this->_readFds[$key] = $stream;
        }
    }

    /** @inheritDoc */
    public function delReadStream($stream): void
    {
        if(
            is_resource($stream) and
            isset($this->_readFds[$key = (int)$stream]) and
            Event::isset($stream,SWOOLE_EVENT_READ)
        ){
            Event::del($stream);
            unset($this->_readFds[$key]);
        }
    }

    /** @inheritDoc */
    public function addWriteStream($stream, \Closure $handler): void
    {
        if(is_resource($stream) and !isset($this->_writeFds[$key = (int)$stream])){
            Event::add($stream,null,$handler,SWOOLE_EVENT_WRITE);
            $this->_writeFds[$key] = $stream;
        }
    }

    /** @inheritDoc */
    public function delWriteStream($stream): void
    {
        if(
            is_resource($stream) and
            isset($this->_writeFds[$key = (int)$stream]) and
            Event::isset($stream,SWOOLE_EVENT_WRITE)
        ){
            Event::del($stream);
            unset($this->_writeFds[$key]);
        }
    }

    /** @inheritDoc */
    public function addSignal(int $signal, \Closure $handler): void
    {
        if(!isset($this->_signals[$signal])){
            $this->_signals[$signal] = $handler;
            Process::signal($signal, $handler);
        }
    }

    /** @inheritDoc */
    public function delSignal(int $signal): void
    {
        if(isset($this->_signals[$signal])){
            unset($this->_signals[$signal]);
            Process::signal($signal, function (){});# 模拟 SIG_IGN
        }
    }

    /** @inheritDoc */
    public function addTimer(float $delay, float $repeat, \Closure $callback): string
    {
        $timer = new TimerObj($delay, $repeat, $callback);
        $timerId = spl_object_hash($timer);
        $delay = $this->_floatToInt($delay);
        $repeat = $this->_floatToInt($repeat);
        $equals = ($delay === $repeat);
        $id = 0;

        if($repeat === 0){
            if($equals){
                Event::defer(function () use($timerId, $callback){
                    $callback();
                    $this->_storage->del($timerId);
                });
            }else{
                $id = Timer::after($delay, function () use($timerId, $callback){
                    $callback();
                    $this->_storage->del($timerId);
                });
            }
        }else{
            if($equals){
                $id = Timer::tick($repeat, $callback);
            }else{
                Event::defer(function() use($timerId, &$id, $repeat, $callback){
                    if($id = Timer::tick($repeat, $callback)){
                        $this->_storage->set($timerId, $id);
                    }
                    $callback();
                });
            }
        }
        return $this->_storage->add($timerId, (int)$id);
    }

    /** @inheritDoc */
    public function delTimer(string $timerId): void
    {
        $id = $this->_storage->get($timerId);
        if($id !== 0){
            Timer::clear($id);
        }
        $this->_storage->del($timerId);
    }

    /** @inheritDoc */
    public function loop(): void
    {
        Event::wait();
    }

    /** @inheritDoc */
    public function destroy(): void
    {
        Event::exit();
    }

    /** 获取小数点位数 */
    protected function _floatToInt(float $float): int
    {
        $float = $float * 1000;
        if($float < 0.0){
            throw new \InvalidArgumentException('Minimum support 0.001');
        }
        return (int)($float);
    }
}

