<?php
declare(strict_types=1);

namespace WorkBunny\Test;

use WorkBunny\EventLoop\Drivers\OpenSwooleLoop;

/**
 * 在测试OpenSwoole拓展的过程中，我有一个感受：
 *  它的Event给我的感觉是和signal以及Timer在底层是隔离的，
 *  这也可能是因为其包含了一个调度协程的线程和主线程的原因，
 *  Event肯定是在一个循环中做的一系列响应，但signal或者
 *  timer可能不一定在这个循环中，有可能是在协程线程里做的
 *  响应，从而影响主体进程。
 *  当然，这以上都是我的猜测。
 */
class OpenSwooleLoopTest extends AbstractLoopTest
{
    /** 创建循环 */
    public function createLoop(): OpenSwooleLoop
    {
        if (!extension_loaded('openswoole')) {
            $this->markTestSkipped('OpenSwooleLoop tests skipped because ext-openswoole extension is not installed.');
        }
        return new OpenSwooleLoop();
    }

    /** add read stream 后注册应覆盖前注册 */
    public function testAddReadStreamIgnoresSecondAddReadStreamBIO()
    {
        list ($input, $output) = $this->createSocketPair();

        stream_set_blocking($input, true);
        stream_set_blocking($output, true);

        $count1 = 0;
        $count2 = 0;
        $this->loop->addReadStream($input, function () use(&$count1){
            $count1 ++;
        });
        $this->loop->addReadStream($input, function () use(&$count2){
            $count2 ++;
        });

        fwrite($output, "foo\n");
        $this->tickLoop();

        $this->assertEquals(1, $count1);

        $this->assertEquals(0, $count2);
    }

    /** add read stream 后注册应覆盖前注册 */
    public function testAddReadStreamIgnoresSecondAddReadStreamNIO()
    {
        list ($input, $output) = $this->createSocketPair();

        stream_set_blocking($input, false);
        stream_set_blocking($output, false);

        $count1 = 0;
        $count2 = 0;
        $this->loop->addReadStream($input, function () use(&$count1){
            $count1 ++;
        });
        $this->loop->addReadStream($input, function () use(&$count2){
            $count2 ++;
        });

        fwrite($output, "foo\n");
        $this->tickLoop();

        $this->assertEquals(1, $count1);

        $this->assertEquals(0, $count2);
    }

    /** 读流处理器多次触发 */
    public function testReadStreamHandlerTriggeredMultiTimesBIO()
    {
        list ($input, $output) = $this->createSocketPair();
        stream_set_blocking($input, true);
        stream_set_blocking($output, true);

        $count = 0;
        $this->loop->addReadStream($input, function() use(&$count){
            $count ++;
        });

        fwrite($output, "foo\n");
        $this->tickLoop();

        $this->assertEquals(1, $count);
    }

    /** 读流处理器多次触发 */
    public function testReadStreamHandlerTriggeredMultiTimesNIO()
    {
        list ($input, $output) = $this->createSocketPair();
        stream_set_blocking($input, false);
        stream_set_blocking($output, false);

        $count = 0;
        $this->loop->addReadStream($input, function() use(&$count){
            $count ++;
        });

        fwrite($output, "foo\n");
        $this->tickLoop();

        $this->assertEquals(1, $count);
    }

    /** add write stream 后注册应覆盖前注册 */
    public function testAddWriteStreamIgnoresSecondAddBIO()
    {
        list ($input) = $this->createSocketPair();
        stream_set_blocking($input, true);
        $count1 = $count2 = 0;
        $this->loop->addWriteStream($input, function() use(&$count1){
            $count1 ++;
        });
        $this->loop->addWriteStream($input, function() use(&$count2){
            $count2 ++;
        });
        $this->tickLoop();
        $this->assertEquals(1, $count1);
        $this->assertEquals(0, $count2);
    }

    /** add write stream 后注册应覆盖前注册 */
    public function testAddWriteStreamIgnoresSecondAddNIO()
    {
        list ($input) = $this->createSocketPair();
        stream_set_blocking($input,false);
        $count1 = $count2 = 0;
        $this->loop->addWriteStream($input, function() use(&$count1){
            $count1 ++;
        });
        $this->loop->addWriteStream($input, function() use(&$count2){
            $count2 ++;
        });
        $this->tickLoop();
        $this->assertEquals(1, $count1);
        $this->assertEquals(0, $count2);
    }

    /** 写流处理器的多次触发 */
    public function testWriteStreamHandlerTriggeredMultiTimesBIO()
    {
        list ($input) = $this->createSocketPair();
        stream_set_blocking($input, true);
        $count = 0;
        $this->loop->addWriteStream($input, function() use(&$count){
            $count ++;
        });
        $this->tickLoop();

        $this->assertEquals(1, $count);
    }

    /** 写流处理器的多次触发 */
    public function testWriteStreamHandlerTriggeredMultiTimesNIO()
    {
        list ($input) = $this->createSocketPair();
        stream_set_blocking($input, false);
        $count = 0;
        $this->loop->addWriteStream($input, function() use(&$count){
            $count ++;
        });
        $this->tickLoop();

        $this->assertEquals(1, $count);
    }

    /** @runInSeparateProcess 测试信号相应 */
    public function testSignalResponse()
    {
        if (
            !function_exists('posix_kill') or
            !function_exists('posix_getpid')
        ) {
            $this->markTestSkipped('Signal test skipped because functions "posix_kill" and "posix_getpid" are missing.');
        }

        $count1 = $count2 = 0;
        $this->loop->addSignal(12, function () use (&$count1) {
            $count1 ++;
            $this->loop->delSignal(12);
            $this->loop->destroy();
        });

        $this->loop->addSignal(10, function () use (&$count2) {
            $count2 ++;
            $this->loop->delSignal(10);
            $this->loop->destroy();
        });

        $this->loop->addTimer(0.0,0.0,function () {
            posix_kill(posix_getpid(), 10);
        });

        # 猜测是因为发送信号会被转为异步的缘故，所以当去除这个timer时，则不满足预期
        # 但是不可以使用Event::defer，这里的无延迟定时器使用的就是Event::defer
//        $this->loop->addTimer(0.0,0.0, function (){});
        $this->loop->addTimer($this->tickTimeout,0.0, function (){});

        $this->loop->loop();


        $this->assertEquals(0, $count1);
        $this->assertEquals(1, $count2);
    }

    /** @runInSeparateProcess 测试添加相同信号 */
    public function testAddSameSignalListener()
    {
        if (
            !function_exists('posix_kill') or
            !function_exists('posix_getpid')
        ) {
            $this->markTestSkipped('Signal test skipped because functions "posix_kill" and "posix_getpid" are missing.');
        }
        $funcCallCount = 0;

        $this->loop->addSignal(10, function () use (&$funcCallCount) {
            $funcCallCount ++;
            $this->loop->delSignal(10);
            $this->loop->destroy();
        });
        $this->loop->addSignal(10, function(){});

        $this->loop->addTimer(0.1,0.0, function () {
            posix_kill(posix_getpid(), 10);
        });

        # 猜测是因为发送信号会被转为异步的缘故，所以当去除这个timer时，则不满足预期
        # 但是不可以使用Event::defer，这里的无延迟定时器使用的就是Event::defer
//        $this->loop->addTimer(0.0,0.0, function (){});
        $this->loop->addTimer($this->tickTimeout,0.0, function (){});

        $this->loop->loop();

        $this->assertEquals(1, $funcCallCount);
    }
}
