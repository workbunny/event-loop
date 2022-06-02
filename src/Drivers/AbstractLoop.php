<?php
declare(strict_types=1);

namespace WorkBunny\EventLoop\Drivers;

use WorkBunny\EventLoop\Exception\LoopException;
use WorkBunny\EventLoop\Storage;

abstract class AbstractLoop implements LoopInterface
{
    /** @var resource[] */
    protected array $_readFds = [];

    /** @var resource[] */
    protected array $_writeFds = [];

    /** @var array All listeners for read event. */
    protected array $_reads = [];

    /** @var array All listeners for write event. */
    protected array $_writes = [];

    /** @var array Event listeners of signal. */
    protected array $_signals = [];

    /** @var Storage 定时器容器 */
    protected Storage $_storage;

    /**
     * 初始化
     * @throws LoopException
     */
    public function __construct()
    {
        $this->_storage = new Storage();
    }

    /**
     * @return resource[]
     */
    public function getReadFds(): array
    {
        return $this->_readFds;
    }

    /**
     * @return resource[]
     */
    public function getWriteFds(): array
    {
        return $this->_writeFds;
    }

    /**
     * @return array
     */
    public function getReads(): array
    {
        return $this->_reads;
    }

    /**
     * @return array
     */
    public function getWrites(): array
    {
        return $this->_writes;
    }

    /**
     * @return array
     */
    public function getSignals(): array
    {
        return $this->_signals;
    }

    /**
     * @return Storage
     */
    public function getStorage(): Storage
    {
        return $this->_storage;
    }

    /**
     * @return void
     */
    public function clear()
    {
        $this->_storage = new Storage();
        $this->_writeFds = [];
        $this->_readFds = [];
        $this->_writes = [];
        $this->_reads = [];
    }
}