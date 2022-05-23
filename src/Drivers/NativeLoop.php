<?php
declare(strict_types=1);

namespace EventLoop\Drivers;

use EventLoop\Exception\LoopException;
use EventLoop\Storage;

class NativeLoop implements LoopInterface
{
    /** @var resource[] */
    protected array $_readFds;

    /** @var resource[] */
    protected array $_writeFds;

    /** @var array All listeners for read event. */
    protected array $_reads = [];

    /** @var array All listeners for write event. */
    protected array $_writes = [];

    /** @var array Event listeners of signal. */
    protected array $_signals = [];

    /** @var Storage 定时器容器 */
    protected Storage $_storage;

    /** @var \SplPriorityQueue 优先队列 */
    protected \SplPriorityQueue $_queue;

    protected bool $_stopped = false;

    /**
     * Ev constructor.
     * @throws LoopException
     */
    public function __construct()
    {
        if(!extension_loaded('pcntl')){
            throw new LoopException('not support: ext-pcntl');
        }
        $this->_storage = new Storage();
        $this->_queue = new \SplPriorityQueue();
        $this->_queue->setExtractFlags(\SplPriorityQueue::EXTR_BOTH);
        $this->_readFds = [];
        $this->_writeFds = [];
    }

    /** @inheritDoc */
    public function addReadStream($stream, callable $handler): void
    {
        if(is_resource($stream)){
            $key = (int) $stream;
            if (!isset($this->_readFds[$key])) {
                $this->_readFds[$key] = $stream;
                $this->_reads[$key] = $handler;
            }
        }
    }

    /** @inheritDoc */
    public function delReadStream($stream): void
    {
        if(is_resource($stream)){
            $key = (int)$stream;
            unset(
                $this->_reads[$key],
                $this->_readFds[$key]
            );
        }
    }

    /** @inheritDoc */
    public function addWriteStream($stream, callable $handler): void
    {
        if(is_resource($stream)){
            $key = (int) $stream;
            if (!isset($this->_writeFds[$key])) {
                $this->_writeFds[$key] = $stream;
                $this->_writes[$key] = $handler;
            }
        }
    }

    /** @inheritDoc */
    public function delWriteStream($stream): void
    {
        if(is_resource($stream)){
            $key = (int)$stream;
            unset(
                $this->_writes[$key],
                $this->_writeFds[$key]
            );
        }
    }

    /** @inheritDoc */
    public function addSignal(int $signal, callable $handler): void
    {
        $this->_signals[$signal] = $handler;
        \pcntl_signal($signal, function($signal){
            $this->_signals[$signal]($signal);
        });
    }

    /** @inheritDoc */
    public function delSignal(int $signal, callable $handler): void
    {
        unset($this->_signals[$signal]);
        \pcntl_signal($signal, \SIG_IGN);
    }

    /** @inheritDoc */
    public function addTimer(float $delay, float $repeat, callable $callback): int
    {
        $runTime = \hrtime(true) * 1e-9 + $delay;
        $this->_queue->insert($this->_storage->id(), -$runTime);
        return $this->_storage->add([
            'delay'    => $delay,
            'repeat'   => $repeat,
            'callback' => $callback
        ]);
    }

    /** @inheritDoc */
    public function delTimer(int $timerId): void
    {
        $this->_storage->del($timerId);
    }

    /** @inheritDoc */
    public function loop(): void
    {

        while (!$this->_stopped) {
            if(!$this->_readFds and !$this->_writeFds and !$this->_signals and $this->_storage->isEmpty()){
                break;
            }

            \pcntl_signal_dispatch();

            $this->_tick();

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
                    @stream_select($reads, $writes, $excepts, 0,1000);
                } catch (\Throwable $e) {}

                foreach ($reads as $stream) {
                    $key = (int)$stream;
                    if (isset($this->_reads[$key])) {
                        $this->_reads[$key]($stream);
                    }
                }

                foreach ($writes as $stream) {
                    $key = (int)$stream;
                    if (isset($this->_writes[$key])) {
                        $this->_writes[$key]($stream);
                    }
                }
            }

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
            if($data = $this->_storage->get($timerId)){
                $repeat = $data['repeat'];
                $callback = $data['callback'];
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
