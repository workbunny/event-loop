<?php declare(strict_types=1);
/**
 * This file is part of workbunny.
 *
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    chaz6chez<chaz6chez1993@outlook.com>
 * @copyright chaz6chez<chaz6chez1993@outlook.com>
 * @link      https://github.com/workbunny/event-loop
 * @license   https://github.com/workbunny/event-loop/blob/main/LICENSE
 */
namespace WorkBunny\EventLoop\Drivers;

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
        parent::__construct();

        $this->_queue = new SplPriorityQueue();
        $this->_queue->setExtractFlags(SplPriorityQueue::EXTR_BOTH);
        $this->_readFds = [];
        $this->_writeFds = [];
    }

    /** @inheritDoc */
    public function getExtName(): string
    {
        return 'pcntl';
    }

    /** @inheritDoc */
    public function hasExt(): bool
    {
        return extension_loaded($this->getExtName());
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
    public function addTimer(float $delay, float|false $repeat, Closure $handler): string
    {
        $timer = new Timer($delay, $repeat, $handler);
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
    public function run(): void
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
                    @\stream_select($reads, $writes, $excepts, 0,0);
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
        }
    }

    /** @inheritDoc */
    public function stop(): void
    {
        $this->_stopped = true;
    }

    /** @inheritDoc */
    public function destroy(): void
    {
        $this->stop();
        $this->clear();
    }

    /** @inheritDoc */
    public function clear(): void
    {
        parent::clear();
        $this->_queue = new SplPriorityQueue();
        $this->_queue->setExtractFlags(SplPriorityQueue::EXTR_BOTH);
    }

    /** 执行 */
    protected function _tick(): void
    {
        $count = $this->_queue->count();
        while ($count --){
            $data = $this->_queue->current();
            $runTime = -$data['priority'];
            $timerId = $data['data'];
            /** @var Timer $data */
            if($data = $this->_storage->get($timerId)){
                $repeat = $data->getRepeat();
                $callback = $data->getHandler();
                $timeNow = \hrtime(true) * 1e-9;
                if (($runTime - $timeNow) <= 0) {
                    \call_user_func($callback);
                    if($repeat !== false){
                        $nextTime = $timeNow + $repeat;
                        $this->_queue->insert($timerId, -$nextTime);
                    }else{
                        $this->delTimer($timerId);
                    }
                    $this->_queue->next();
                }
            }
        }
    }
}
