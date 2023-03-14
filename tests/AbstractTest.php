<?php
declare(strict_types=1);

namespace WorkBunny\Tests;

use PHPUnit\Framework\TestCase;
use WorkBunny\EventLoop\Drivers\AbstractLoop;
use WorkBunny\EventLoop\Drivers\LoopInterface;

abstract class AbstractTest extends TestCase
{
    /** @var int  */
    const PHP_DEFAULT_CHUNK_SIZE = 8192;

    /** @var float 模拟loop一圈的时长，20ms */
    public float $tickTimeout = 0.02;

    /** @var ?string */
    protected ?string $received = null;

    /** @var AbstractLoop|null  */
    protected ?AbstractLoop $loop = null;

    /** 创建循环 */
    abstract public function createLoop();

    /** 获取循环 */
    public function getLoop(): ? AbstractLoop
    {
        return $this->loop;
    }

    /** @before */
    public function setUpLoop()
    {
        $this->loop = $this->createLoop();
    }

    /** 创建socket */
    public function createSocketPair()
    {
        $domain = (DIRECTORY_SEPARATOR === '\\') ? STREAM_PF_INET : STREAM_PF_UNIX;
        $sockets = stream_socket_pair($domain, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

        foreach ($sockets as $socket) {
            if (function_exists('stream_set_read_buffer')) {
                stream_set_read_buffer($socket, 0);
            }
        }
        return $sockets;
    }

    /** 单次loop */
    public function tickLoop(float $delay = 0.0)
    {
        $this->getLoop()->addTimer($delay, 0.0, function () {
            $this->getLoop()->destroy();
        });

        $this->getLoop()->loop();
    }

    /** 比较时间 */
    public function assertRunFasterThan(float $maxInterval)
    {
        $start = microtime(true);

        $this->getLoop()->loop();

        $end = microtime(true);
        $interval = $end - $start;

        $this->assertGreaterThan($interval, $maxInterval);
    }
}
