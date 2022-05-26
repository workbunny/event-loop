<?php
declare(strict_types=1);

namespace WorkBunny\Test;

use EvIo;
use WorkBunny\EventLoop\Drivers\AbstractLoop;
use WorkBunny\EventLoop\Drivers\EvLoop;

class EvLoopTest extends AbstractLoopTest
{
    /** 创建循环 */
    public function createLoop(): EvLoop
    {
        if (!class_exists('EvLoop')) {
            $this->markTestSkipped('ExtEvLoop tests skipped because ext-ev extension is not installed.');
        }

        return new EvLoop();
    }

    /** 读流处理器可以引用接受数据 */
    public function testReadStreamHandlerReceivesDataFromStreamReferenceBIO()
    {
        list ($input, $output) = $this->createSocketPair();
        stream_set_blocking($input, true);
        stream_set_blocking($output, true);

        $this->received = '';
        fwrite($input, 'hello');
        fclose($input);

        $this->loop->addReadStream($output, function (EvIo $io) {
            $output = $io->fd;
            $chunk = fread($output, 1024);
            if ($chunk === '') {
                $this->received .= 'X';
                $this->loop->delReadStream($io);
                fclose($output);
                $this->loop->destroy();
            } else {
                $this->received .= '[' . $chunk . ']';
            }

        });
        $this->assertEquals('', $this->received);

        $this->assertRunFasterThan($this->tickTimeout * 2);

        $this->assertEquals('[hello]X', $this->received);
    }

    /** 读流处理器可以引用接受数据 */
    public function testReadStreamHandlerReceivesDataFromStreamReferenceNIO()
    {
        list ($input, $output) = $this->createSocketPair();
        stream_set_blocking($input, false);
        stream_set_blocking($output, false);

        $this->received = '';
        fwrite($input, 'hello');
        fclose($input);

        $this->loop->addReadStream($output, function (EvIo $io) {
            $output = $io->fd;
            $chunk = fread($output, 1024);
            if ($chunk === '') {
                $this->received .= 'X';
                $this->loop->delReadStream($io);
                fclose($output);
                $this->loop->destroy();
            } else {
                $this->received .= '[' . $chunk . ']';
            }

        });
        $this->assertEquals('', $this->received);

        $this->assertRunFasterThan($this->tickTimeout * 2);

        $this->assertEquals('[hello]X', $this->received);
    }

    /** 无延迟单次定时器在BIO之前触发 */
    public function testNonDelayOneShotTimerFiresBeforeBIO()
    {
        list ($stream) = $this->createSocketPair();
        stream_set_blocking($stream, true);

        $this->loop->addWriteStream(
            $stream,
            function () {
                echo 'stream' . PHP_EOL;
            }
        );
        $this->loop->addTimer(0.0,0.0,
            function () {
                echo 'non-delay one-shot timer' . PHP_EOL;
            }
        );
        $this->expectOutputString( 'non-delay one-shot timer' . PHP_EOL . 'stream' . PHP_EOL);
        $this->tickLoop();
    }

    /** 无延迟单次定时器在NIO之前触发 */
    public function testNonDelayOneShotTimerFiresBeforeNIO()
    {
        list ($stream) = $this->createSocketPair();
        stream_set_blocking($stream, false);

        $this->loop->addWriteStream(
            $stream,
            function () {
                echo 'stream' . PHP_EOL;
            }
        );
        $this->loop->addTimer(0.0,0.0,
            function () {
                echo 'non-delay one-shot timer' . PHP_EOL;
            }
        );
        $this->expectOutputString('non-delay one-shot timer' . PHP_EOL . 'stream' . PHP_EOL);

        $this->tickLoop();
    }

    /** 移除读流 */
    public function testRemoveReadStreamsBIO()
    {
        if(!$this->loop instanceof AbstractLoop){
            $this->markTestSkipped('Remove read streams test skipped because because loop not instance of AbstractLoop.');
        }
        list ($input1, $output1) = $this->createSocketPair();
        list ($input2, $output2) = $this->createSocketPair();

        stream_set_blocking($input1, true);
        stream_set_blocking($input2, true);
        stream_set_blocking($output1, true);
        stream_set_blocking($output2, true);


        $this->loop->addReadStream($input1, function (EvIo $io) {
            $this->loop->delReadStream($io);
        });

        $this->loop->addReadStream($input2, function (EvIo $io) {
            $this->loop->delReadStream($io);
        });

        fwrite($output1, "foo1\n");
        fwrite($output2, "foo2\n");

        $this->tickLoop($this->tickTimeout);

        $this->assertCount(0, $this->loop->getReadFds());
        $this->assertCount(0, $this->loop->getReads());
    }

    /** 移除读流 */
    public function testRemoveReadStreamsNIO()
    {
        if(!$this->loop instanceof AbstractLoop){
            $this->markTestSkipped('Remove read streams test skipped because because loop not instance of AbstractLoop.');
        }
        list ($input1, $output1) = $this->createSocketPair();
        list ($input2, $output2) = $this->createSocketPair();

        stream_set_blocking($input1, false);
        stream_set_blocking($input2, false);
        stream_set_blocking($output1, false);
        stream_set_blocking($output2, false);

        $this->loop->addReadStream($input1, function (EvIo $io) {
            $this->loop->delReadStream($io);
        });

        $this->loop->addReadStream($input2, function (EvIo $io) {
            $this->loop->delReadStream($io);
        });

        fwrite($output1, "foo1\n");
        fwrite($output2, "foo2\n");

        $this->tickLoop($this->tickTimeout);

        $this->assertCount(0, $this->loop->getReadFds());
        $this->assertCount(0, $this->loop->getReads());
    }

    /** 移除写流 */
    public function testRemoveWriteStreamsBIO()
    {
        if(!$this->loop instanceof AbstractLoop){
            $this->markTestSkipped('Remove write streams test skipped because because loop not instance of AbstractLoop.');
        }
        list ($input1) = $this->createSocketPair();
        list ($input2) = $this->createSocketPair();

        stream_set_blocking($input1, true);
        stream_set_blocking($input2, true);

        $this->loop->addWriteStream($input1, function (EvIo $io) {
            $this->loop->delWriteStream($io);
        });

        $this->loop->addWriteStream($input2, function (EvIo $io) {
            $this->loop->delWriteStream($io);
        });

        $this->tickLoop($this->tickTimeout);

        $this->assertCount(0, $this->loop->getWriteFds());
        $this->assertCount(0, $this->loop->getWrites());
    }

    /** 移除写流 */
    public function testRemoveWriteStreamsNIO()
    {
        if(!$this->loop instanceof AbstractLoop){
            $this->markTestSkipped('Remove write streams test skipped because because loop not instance of AbstractLoop.');
        }
        list ($input1) = $this->createSocketPair();
        list ($input2) = $this->createSocketPair();

        stream_set_blocking($input1, false);
        stream_set_blocking($input2, false);

        $this->loop->addWriteStream($input1, function (EvIo $io) {
            $this->loop->delWriteStream($io);
        });

        $this->loop->addWriteStream($input2, function (EvIo $io) {
            $this->loop->delWriteStream($io);
        });

        $this->tickLoop($this->tickTimeout);

        $this->assertCount(0, $this->loop->getWriteFds());
        $this->assertCount(0, $this->loop->getWrites());
    }
}
