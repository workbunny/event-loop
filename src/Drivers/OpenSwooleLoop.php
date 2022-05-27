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
//            \pcntl_signal($signal, function($signal){
//                $this->_signals[$signal]($signal);
//            });
            if(Process::signal($signal, $handler)){
                $this->_signals[$signal] = $handler;
            }
        }
    }

    /** @inheritDoc */
    public function delSignal(int $signal): void
    {
        if(isset($this->_signals[$signal])){
            unset($this->_signals[$signal]);
//            \pcntl_signal($signal, \SIG_IGN);
            if(Process::signal($signal, function (){})){
                unset($this->_signals[$signal]);
            }# 模拟 SIG_IGN
        }
    }

    /** @inheritDoc */
    public function addTimer(float $delay, float $repeat, \Closure $callback): string
    {
        $timer = new TimerObj($delay, $repeat, $callback);
        $id = spl_object_hash($timer);
        $delay = $this->_floatToInt($delay);
        $repeat = $this->_floatToInt($repeat);
        $equals = ($delay === $repeat);

        if($repeat === 0){
            if($equals){
                Event::defer($callback);
                $timerId = 0;
            }else{
                $timerId = Timer::after($delay, $callback);
            }
        }else{
            if($equals){
                $timerId = Timer::tick($repeat, $callback);
            }else{
                $timerId = 0;
                Event::defer(function() use($id, &$timerId, $repeat, $callback){
                    if($timerId = Timer::tick($repeat, $callback)){
                        $this->_storage->set($id, $timerId);
                    }
                    $callback();
                });
            }
        }
        return $this->_storage->add($id, (int)$timerId);
    }

    /** @inheritDoc */
    public function delTimer(string $timerId): void
    {
        $id = $this->_storage->get($timerId);
        if($id !== null){
            if($id){
                Timer::clear($id);
            }
            $this->_storage->del($timerId);
        }
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
        $number = ($chr = strrchr((string)$float, '.')) ? strlen(substr($chr, 1)) : 0;
        return (int)($float * pow(10, $number));
    }
}

