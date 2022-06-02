<?php
declare(strict_types=1);

namespace WorkBunny\Test;

use EvIo;
use http\Encoding\Stream;
use WorkBunny\EventLoop\Drivers\AbstractLoop;
use WorkBunny\EventLoop\Drivers\EvLoop;
use WorkBunny\Test\Events\StreamsTest;

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

    /**
     * @see AbstractLoopTest::testReadStreamBeforeTimer()
     * @dataProvider provider
     * @param bool $bio
     * @return void
     */
    public function testReadStreamBeforeTimer(bool $bio)
    {
        $this->markTestSkipped('The priority of the EvLoop does not apply to this test.');
    }

    /**
     * @see AbstractLoopTest::testWriteStreamBeforeTimer()
     * @dataProvider provider
     * @param bool $bio
     * @return void
     */
    public function testWriteStreamBeforeTimer(bool $bio)
    {
        $this->markTestSkipped('The priority of the EvLoop does not apply to this test.');
    }

    /**
     * @see AbstractLoopTest::testReadStreamBeforeTimer() 与之相反
     * @dataProvider provider
     * @param bool $bio
     * @return void
     */
    public function testReadStreamAfterTimer(bool $bio)
    {
        list ($input, $output) = $this->createSocketPair();
        stream_set_blocking($input, $bio);
        stream_set_blocking($output, $bio);

        fwrite($output, 'foo' . PHP_EOL);

        $string = '';

        $this->getLoop()->addTimer(0.0,0.0, function () use (&$string){
            $string .= 'timer' . PHP_EOL;
        });

        $this->getLoop()->addReadStream($input, function() use(&$string){
            $string .= 'read' . PHP_EOL;
        });

        $this->tickLoop();

        $this->assertEquals('timer' . PHP_EOL . 'read' . PHP_EOL, $string);
    }

    /**
     * @see AbstractLoopTest::testWriteStreamBeforeTimer() 与之相反
     * @dataProvider provider
     * @param bool $bio
     * @return void
     */
    public function testWriteStreamAfterTimer(bool $bio)
    {

        list ($input, $output) = $this->createSocketPair();
        stream_set_blocking($output, $bio);
        fwrite($output, 'foo' . PHP_EOL);

        $string = '';

        $this->getLoop()->addTimer(0.0,0.0, function () use (&$string){
            $string .= 'timer' . PHP_EOL;
        });

        $this->getLoop()->addWriteStream($output, function() use(&$string){
            $string .= 'write' . PHP_EOL;
        });

        $this->tickLoop();

        $this->assertEquals('timer' . PHP_EOL . 'write' . PHP_EOL, $string);
    }

    /** 
     * @see StreamsTest::testReadStreamHandlerReceivesDataFromStreamReference()
     * @dataProvider provider
     * @param bool $bio
     * @return void
     */
    public function testReadStreamHandlerReceivesDataFromStreamReference(bool $bio)
    {
        list ($input, $output) = $this->createSocketPair();
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
     * @see StreamsTest::testRemoveReadStreams()
     * @dataProvider provider
     * @param bool $bio
     * @return void
     */
    public function testRemoveReadStreams(bool $bio)
    {
        list ($input1, $output1) = $this->createSocketPair();
        list ($input2, $output2) = $this->createSocketPair();

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
     * @see StreamsTest::testRemoveWriteStreams()
     * @dataProvider provider
     * @param bool $bio
     * @return void
     */
    public function testRemoveWriteStreams(bool $bio)
    {
        list ($input1) = $this->createSocketPair();
        list ($input2) = $this->createSocketPair();

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
