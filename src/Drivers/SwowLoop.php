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

use Closure;
use Swow\Coroutine;
use Swow\Signal;
use Swow\SignalException;
use WorkBunny\EventLoop\Exception\InvalidArgumentException;
use function Swow\Sync\waitAll;
use function msleep;

class SwowLoop extends AbstractLoop
{

    /** @var bool  */
    protected bool $_stopped = false;

    /** @inheritDoc */
    public function getExtName(): string
    {
        return 'swow';
    }

    /** @inheritDoc */
    public function hasExt(): bool
    {
        return extension_loaded($this->getExtName());
    }

    /** @inheritDoc */
    public function addReadStream($stream, Closure $handler): void
    {
        if(\is_resource($stream) and !isset($this->_readFds[$key = (int)$stream])){
            $this->_reads[$key] = null;
            $this->_readFds[$key] = $stream;
            Coroutine::run(function () use ($handler, $key): void {
                try {
                    $this->_reads[$key] = Coroutine::getCurrent();
                    while (!$this->_stopped) {
                        if (!isset($this->_readFds[$key])) {
                            break;
                        }
                        if ($this->_reads[$key] === null) {
                            continue;
                        }
                        $event = stream_poll_one($stream = $this->_readFds[$key], STREAM_POLLIN | STREAM_POLLHUP);

                        if ($event !== STREAM_POLLNONE) {
                            \call_user_func($handler, $stream);
                        }
                        if ($event !== STREAM_POLLIN) {
                            $this->delReadStream($stream);
                            break;
                        }
                    }
                } catch (\RuntimeException) {
                    $this->delReadStream($stream);
                }
            });
        }
    }

    /** @inheritDoc */
    public function delReadStream($stream): void
    {
        if(
            \is_resource($stream) and
            isset($this->_readFds[$key = (int)$stream]) and
            isset($this->_reads[$key])
        ){
            unset($this->_readFds[$key], $this->_reads[$key]);
        }
    }

    /** @inheritDoc */
    public function addWriteStream($stream, Closure $handler): void
    {
        if(\is_resource($stream) and !isset($this->_writeFds[$key = (int)$stream])) {
            $this->_writes[$key] = null;
            $this->_writeFds[$key] = $stream;
            Coroutine::run(function () use ($handler, $key): void {
                try {
                    $this->_writes[$key] = Coroutine::getCurrent();
                    while (!$this->_stopped) {
                        if (!isset($this->_writeFds[$key])) {
                            break;
                        }
                        if ($this->_writes[$key] === null) {
                            continue;
                        }
                        $event = stream_poll_one($stream = $this->_writeFds[$key], STREAM_POLLOUT | STREAM_POLLHUP);

                        if ($event !== STREAM_POLLNONE) {
                            \call_user_func($handler, $stream);
                        }
                        if ($event !== STREAM_POLLOUT) {
                            $this->delWriteStream($stream);
                            break;
                        }
                    }
                } catch (\RuntimeException) {
                    $this->delWriteStream($stream);
                }
            });
        }
    }

    /** @inheritDoc */
    public function delWriteStream($stream): void
    {
        if(
            \is_resource($stream) and
            isset($this->_writeFds[$key = (int)$stream]) and
            isset($this->_writes[$key])
        ){
            unset($this->_writeFds[$key], $this->_writes[$key]);
        }
    }

    /** @inheritDoc */
    public function addSignal(int $signal, Closure $handler): void
    {
        if(!isset($this->_signals[$signal])){
            // 占位
            $this->_signals[$signal] = null;
            Coroutine::run(function () use ($signal, $handler): void {
                $this->_signals[$signal] = Coroutine::getCurrent();
                while (!$this->_stopped) {
                    try {
                        Signal::wait($signal);
                        if (!isset($this->_signals[$signal])) {
                            break;
                        }
                        if ($this->_signals[$signal] === null) {
                            continue;
                        }
                        \call_user_func($handler, $signal);
                    } catch (SignalException) {}
                }
            });
        }
    }

    /** @inheritDoc */
    public function delSignal(int $signal): void
    {
        if(isset($this->_signals[$signal])){
            unset($this->_signals[$signal]);
        }
    }

    /** @inheritDoc */
    public function addTimer(float $delay, float|false $repeat, Closure $handler): string
    {
        $delay = $this->_floatToInt($delay);
        $repeat = $this->_floatToInt($repeat);
        $coroutine = Coroutine::run(function () use ($delay, $repeat, $handler): void {
            $first = true;
            while (!$this->_stopped) {
                if($repeat === false){
                    $this->_storage->del(spl_object_hash(Coroutine::getCurrent()));
                    break;
                }
                if($first){
                    msleep($delay);
                }else{
                    msleep($repeat);
                }
                \call_user_func($handler);
                $first = false;
            }
        });
        return $this->_storage->add(spl_object_hash($coroutine), $coroutine->getId());
    }

    /** @inheritDoc */
    public function delTimer(string $timerId): void
    {
        $id = $this->_storage->get($timerId);
        if($id !== null){
            Coroutine::get($id)->kill();
        }
        $this->_storage->del($timerId);
    }

    /** @inheritDoc */
    public function run(): void
    {
        $this->_stopped = false;
        waitAll();
    }

    /** @inheritDoc */
    public function stop(): void
    {
        $this->_stopped = true;
        Coroutine::killAll();
    }

    /** @inheritDoc */
    public function destroy(): void
    {
        $this->stop();
        $this->clear();
    }

    /** 获取小数点位数 */
    protected function _floatToInt(float|false $float): int|false
    {
        if($float === false){
            return false;
        }
        $float = $float * 1000;
        if($float < 0.0){
            throw new InvalidArgumentException('Minimum support 0.001');
        }
        return (int)($float);
    }
}

