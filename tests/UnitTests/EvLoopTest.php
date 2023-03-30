<?php
declare(strict_types=1);

namespace WorkBunny\Tests\UnitTests;

use EvIo;
use WorkBunny\EventLoop\Drivers\EvLoop;
use WorkBunny\tests\UnitTests\Units\StreamsUnit;

class EvLoopTest extends AbstractTestCase
{
    /** @inheritDoc */
    public function setLoop(): EvLoop
    {
        if (!class_exists('EvLoop')) {
            $this->markTestSkipped('ExtEvLoop tests skipped because ext-ev extension is not installed.');
        }

        return new EvLoop();
    }

    /** @inheritDoc */
    public function setTickTimeout(): float
    {
        return 0.02;
    }


    public function testTimerPriority(): void
    {
        $this->markTestSkipped('TimerPriority of the EvLoop does not apply to this test.');
    }

    public function testDelayTimerPriority(): void
    {
        $this->markTestSkipped('DelayTimerPriority of the EvLoop does not apply to this test.');
    }

    /**
     * @see AbstractTestCase::testReadStreamBeforeTimer()
     * @dataProvider provider
     * @param bool $bio
     * @return void
     */
    public function testReadStreamBeforeTimer(bool $bio): void
    {
        $this->markTestSkipped('The priority of the EvLoop does not apply to this test.');
    }

    /**
     * @see AbstractTestCase::testWriteStreamBeforeTimer()
     * @dataProvider provider
     * @param bool $bio
     * @return void
     */
    public function testWriteStreamBeforeTimer(bool $bio): void
    {
        $this->markTestSkipped('The priority of the EvLoop does not apply to this test.');
    }

    /**
     * @see AbstractTestCase::testReadStreamBeforeTimer() 与之相反
     * @dataProvider provider
     * @param bool $bio
     * @return void
     */
    public function testReadStreamAfterTimer(bool $bio): void
    {
        list ($input, $output) = \stream_socket_pair(
            (DIRECTORY_SEPARATOR === '\\') ? STREAM_PF_INET : STREAM_PF_UNIX,
            STREAM_SOCK_STREAM,
            STREAM_IPPROTO_IP
        );
        stream_set_blocking($input, $bio);
        stream_set_blocking($output, $bio);

        fwrite($output, 'foo' . PHP_EOL);

        $string = '';

        $this->getLoop()->addTimer(0.0,false, function () use (&$string){
            $string .= 'timer' . PHP_EOL;
        });

        $this->getLoop()->addReadStream($input, function() use(&$string){
            $string .= 'read' . PHP_EOL;
        });

        $this->tickLoop();

        $this->assertEquals('timer' . PHP_EOL . 'read' . PHP_EOL, $string);
    }

    /**
     * @see AbstractTestCase::testWriteStreamBeforeTimer() 与之相反
     * @dataProvider provider
     * @param bool $bio
     * @return void
     */
    public function testWriteStreamAfterTimer(bool $bio): void
    {

        list ($input, $output) = \stream_socket_pair(
            (DIRECTORY_SEPARATOR === '\\') ? STREAM_PF_INET : STREAM_PF_UNIX,
            STREAM_SOCK_STREAM,
            STREAM_IPPROTO_IP
        );
        stream_set_blocking($output, $bio);
        fwrite($output, 'foo' . PHP_EOL);

        $string = '';

        $this->getLoop()->addTimer(0.0,false, function () use (&$string){
            $string .= 'timer' . PHP_EOL;
        });

        $this->getLoop()->addWriteStream($output, function() use(&$string){
            $string .= 'write' . PHP_EOL;
        });

        $this->tickLoop();

        $this->assertEquals('timer' . PHP_EOL . 'write' . PHP_EOL, $string);
    }

    /**
     * @param bool $bio
     * @return void
     *@see StreamsUnit::testReadStreamHandlerReceivesDataFromStreamReference()
     * @dataProvider provider
     */
    public function testReadStreamHandlerReceivesDataFromStreamReference(bool $bio): void
    {
        list ($input, $output) = \stream_socket_pair(
            (DIRECTORY_SEPARATOR === '\\') ? STREAM_PF_INET : STREAM_PF_UNIX,
            STREAM_SOCK_STREAM,
            STREAM_IPPROTO_IP
        );
        stream_set_blocking($input, $bio);
        stream_set_blocking($output, $bio);

        $this->received = '';
        fwrite($input, 'hello');
        fclose($input);

        $this->getLoop()->addReadStream($output, function (EvIo $io) {
            $output = $io->fd;
            $chunk = fread($output, 1024);
            if ($chunk === '') {
                $this->received .= 'X';
                $this->getLoop()->delReadStream($io);
                fclose($output);
                $this->getLoop()->destroy();
            } else {
                $this->received .= '[' . $chunk . ']';
            }

        });
        $this->assertEquals('', $this->received);

        $this->assertRunFasterThan($this->tickTimeout * 2);

        $this->assertEquals('[hello]X', $this->received);
    }

    /**
     * @param bool $bio
     * @return void
     *@see StreamsUnit::testRemoveReadStreams()
     * @dataProvider provider
     */
    public function testRemoveReadStreams(bool $bio): void
    {
        list ($input1, $output1) = \stream_socket_pair(
            (DIRECTORY_SEPARATOR === '\\') ? STREAM_PF_INET : STREAM_PF_UNIX,
            STREAM_SOCK_STREAM,
            STREAM_IPPROTO_IP
        );
        list ($input2, $output2) = \stream_socket_pair(
            (DIRECTORY_SEPARATOR === '\\') ? STREAM_PF_INET : STREAM_PF_UNIX,
            STREAM_SOCK_STREAM,
            STREAM_IPPROTO_IP
        );

        stream_set_blocking($input1, $bio);
        stream_set_blocking($input2, $bio);
        stream_set_blocking($output1, $bio);
        stream_set_blocking($output2, $bio);


        $this->getLoop()->addReadStream($input1, function (EvIo $io) {
            $this->getLoop()->delReadStream($io);
        });

        $this->getLoop()->addReadStream($input2, function (EvIo $io) {
            $this->getLoop()->delReadStream($io);
        });

        fwrite($output1, "foo1\n");
        fwrite($output2, "foo2\n");

        $this->tickLoop($this->tickTimeout);

        $this->assertCount(0, $this->getLoop()->getReadFds());
        $this->assertCount(0, $this->getLoop()->getReads());
    }

    /**
     * @param bool $bio
     * @return void
     *@see StreamsUnit::testRemoveWriteStreams()
     * @dataProvider provider
     */
    public function testRemoveWriteStreams(bool $bio): void
    {
        list ($input1) = \stream_socket_pair(
            (DIRECTORY_SEPARATOR === '\\') ? STREAM_PF_INET : STREAM_PF_UNIX,
            STREAM_SOCK_STREAM,
            STREAM_IPPROTO_IP
        );
        list ($input2) = \stream_socket_pair(
            (DIRECTORY_SEPARATOR === '\\') ? STREAM_PF_INET : STREAM_PF_UNIX,
            STREAM_SOCK_STREAM,
            STREAM_IPPROTO_IP
        );

        stream_set_blocking($input1, $bio);
        stream_set_blocking($input2, $bio);

        $this->getLoop()->addWriteStream($input1, function (EvIo $io) {
            $this->getLoop()->delWriteStream($io);
        });

        $this->getLoop()->addWriteStream($input2, function (EvIo $io) {
            $this->getLoop()->delWriteStream($io);
        });

        $this->tickLoop($this->tickTimeout);

        $this->assertCount(0, $this->getLoop()->getWriteFds());
        $this->assertCount(0, $this->getLoop()->getWrites());
    }
}
