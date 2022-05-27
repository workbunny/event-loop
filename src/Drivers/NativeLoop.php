<?php
declare(strict_types=1);

namespace WorkBunny\EventLoop\Drivers;

use WorkBunny\EventLoop\Exception\LoopException;
use WorkBunny\EventLoop\Timer;
use SplPriorityQueue;
use Closure;

class NativeLoop extends AbstractLoop
{
    /** @var SplPriorityQueue 优先队列 */
    protected SplPriorityQueue $_queue;

    /** @var bool  */
    protected bool $_stopped = false;

    /** @inheritDoc */
    public function __construct()
    {
        if(!extension_loaded('pcntl')){
            throw new LoopException('not support: ext-pcntl');
        }
        parent::__construct();

        $this->_queue = new SplPriorityQueue();
        $this->_queue->setExtractFlags(SplPriorityQueue::EXTR_BOTH);
        $this->_readFds = [];
        $this->_writeFds = [];
    }

    /** @inheritDoc */
    public function addReadStream($stream, Closure $handler): void
    {
        if(is_resource($stream) and !isset($this->_readFds[$key = (int)$stream])){
            $this->_readFds[$key] = $stream;
            $this->_reads[$key] = $handler;
        }
    }

    /** @inheritDoc */
    public function delReadStream($stream): void
    {
        if(is_resource($stream) and isset($this->_readFds[$key = (int)$stream])){
            unset(
                $this->_reads[$key],
                $this->_readFds[$key]
            );
        }
    }

    /** @inheritDoc */
    public function addWriteStream($stream, Closure $handler): void
    {
        if(is_resource($stream) and !isset($this->_writeFds[$key = (int) $stream])){
            $this->_writeFds[$key] = $stream;
            $this->_writes[$key] = $handler;
        }
    }

    /** @inheritDoc */
    public function delWriteStream($stream): void
    {
        if(is_resource($stream) and isset($this->_writeFds[$key = (int)$stream])){
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
            $this->_signals[$signal] = $handler;
            \pcntl_signal($signal, function($signal){
                $this->_signals[$signal]($signal);
            });
        }
    }

    /** @inheritDoc */
    public function delSignal(int $signal): void
    {
        if(isset($this->_signals[$signal])){
            unset($this->_signals[$signal]);
            \pcntl_signal($signal, \SIG_IGN);
        }
    }

    /** @inheritDoc */
    public function addTimer(float $delay, float $repeat, Closure $callback): string
    {
        $timer = new Timer($delay, $repeat, $callback);
        $runTime = \hrtime(true) * 1e-9 + $delay;
        $this->_queue->insert($id = spl_object_hash($timer), -$runTime);
        return $this->_storage->add($id, $timer);
    }

    /** @inheritDoc */
    public function delTimer(string $timerId): void
    {
        $this->_storage->del($timerId);
    }

    /** @inheritDoc */
    public function loop(): void
    {
        $this->_stopped = false;

        while (!$this->_stopped) {
            if(!$this->_readFds and !$this->_writeFds and !$this->_signals and $this->_storage->isEmpty()){
                break;
            }

            \pcntl_signal_dispatch();

            $writes = $this->_writeFds;
            $reads = $this->_readFds;
            $excepts = [];
            foreach ($writes as $key => $socket) {
                if (!isset($reads[$key]) && @\ftell($socket) === 0) {
                    $excepts[$key] = $socket;
                }
            }

            if($writes or $reads or $excepts){
                try {
                    @stream_select($reads, $writes, $excepts, 0,0);
                } catch (\Throwable $e) {}

                foreach ($reads as $stream) {
                    $key = (int)$stream;
                    if (isset($this->_reads[$key])) {
                        ($this->_reads[$key])($stream);
                    }
                }

                foreach ($writes as $stream) {
                    $key = (int)$stream;
                    if (isset($this->_writes[$key])) {
                        ($this->_writes[$key])($stream);
                    }
                }
            }

            $this->_tick();

            usleep(0);
        }
    }

    /** @inheritDoc */
    public function destroy(): void
    {
        $this->_stopped = true;
    }

    /** 执行 */
    protected function _tick(): void
    {
        $count = $this->_queue->count();
        while ($count--){
            $data = $this->_queue->top();
            $runTime = -$data['priority'];
            $timerId = $data['data'];
            /** @var Timer $data */
            if($data = $this->_storage->get($timerId)){
                $repeat = $data->getRepeat();
                $callback = $data->getHandler();
                $timeNow = \hrtime(true) * 1e-9;
                if (($runTime - $timeNow) <= 0) {
                    $this->_queue->extract();
                    \call_user_func($callback);
                    if($repeat !== 0.0){
                        $nextTime = $timeNow + $repeat;
                        $this->_queue->insert($timerId, -$nextTime);
                    }else{
                        $this->delTimer($timerId);
                    }
                }
            }
        }
    }
}
