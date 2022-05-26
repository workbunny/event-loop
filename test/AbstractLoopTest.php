<?php
declare(strict_types=1);

namespace WorkBunny\Test;

use WorkBunny\EventLoop\Drivers\AbstractLoop;
use WorkBunny\EventLoop\Drivers\EvLoop;

abstract class AbstractLoopTest extends AbstractTest
{
    /** @var float */
    protected float $tickTimeout = 0.0;

    /** @var ?string */
    protected ?string $received = null;

    /** @var int  */
    const PHP_DEFAULT_CHUNK_SIZE = 8192;
    
    /** @before */
    public function setUpLoop()
    {
        // It's a timeout, don't set it too low. Travis and other CI systems are slow.
        $this->tickTimeout = 0.01;
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

    # 流相关

    /** 测试socket接收数据时创建read stream handler */
    public function testAddReadStreamHandlerWhenSocketReceivesDataBIO()
    {
        list ($input, $output) = $this->createSocketPair();
        stream_set_blocking($input, true);
        stream_set_blocking($output, true);

        $loop = $this->loop;
        $timeout = $loop->addTimer(0.1,0.0, function () use ($input, $loop) {
            $loop->delReadStream($input);
            $loop->destroy();
        });

        $called = 0;
        $this->loop->addReadStream($input, function () use (&$called, $loop, $input, $timeout) {
            ++$called;
            $loop->delReadStream($input);
            $loop->delTimer($timeout);
            $loop->destroy();
        });

        fwrite($output, "foo\n");

        $this->loop->loop();

        $this->assertEquals(1, $called);
    }

    /** 测试socket接收数据时创建read stream handler */
    public function testAddReadStreamHandlerWhenSocketReceivesDataNIO()
    {
        list ($input, $output) = $this->createSocketPair();
        stream_set_blocking($input, false);
        stream_set_blocking($output, false);

        $loop = $this->loop;
        $timeout = $loop->addTimer(0.1,0.0, function () use ($input, $loop) {
            $loop->delReadStream($input);
            $loop->destroy();
        });

        $called = 0;
        $this->loop->addReadStream($input, function () use (&$called, $loop, $input, $timeout) {
            ++$called;
            $loop->delReadStream($input);
            $loop->delTimer($timeout);
            $loop->destroy();
        });

        fwrite($output, "foo\n");

        $this->loop->loop();

        $this->assertEquals(1, $called);
    }

    /** 测试socket关闭时创建read stream handler */
    public function testAddReadStreamHandlerWhenSocketClosesBIO()
    {
        list ($input, $output) = $this->createSocketPair();

        stream_set_blocking($input, true);
        stream_set_blocking($output, true);

        $loop = $this->loop;
        $timeout = $loop->addTimer(0.1,0.0, function () use ($input, $loop) {
            $loop->delReadStream($input);
            $loop->destroy();
        });

        $called = 0;
        $this->loop->addReadStream($input, function () use (&$called, $loop, $input, $timeout) {
            ++$called;
            $loop->delReadStream($input);
            $loop->delTimer($timeout);
            $loop->destroy();
        });

        fclose($output);

        $this->loop->loop();

        $this->assertEquals(1, $called);
    }

    /** 测试socket关闭时创建read stream handler */
    public function testAddReadStreamHandlerWhenSocketClosesNIO()
    {
        list ($input, $output) = $this->createSocketPair();
        stream_set_blocking($output,false);
        stream_set_blocking($input, false);

        $loop = $this->loop;
        $timeout = $loop->addTimer(0.1,0.0, function () use ($input, $loop) {
            $loop->delReadStream($input);
            $loop->destroy();
        });

        $called = 0;
        $this->loop->addReadStream($input, function () use (&$called, $loop, $input, $timeout) {
            ++$called;
            $loop->delReadStream($input);
            $loop->delTimer($timeout);
            $loop->destroy();
        });

        fclose($output);

        $this->loop->loop();

        $this->assertEquals(1, $called);
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

        fwrite($output, "bar\n");
        $this->tickLoop();

        $this->assertEquals(2, $count1);

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

        fwrite($output, "bar\n");
        $this->tickLoop();

        $this->assertEquals(2, $count1);

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

        fwrite($output, "bar\n");
        $this->tickLoop();

        $this->assertEquals(2, $count);
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

        fwrite($output, "bar\n");
        $this->tickLoop();

        $this->assertEquals(2, $count);
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

        $this->loop->addReadStream($output, function ($output) {
            $chunk = fread($output, 1024);
            if ($chunk === '') {
                $this->received .= 'X';
                $this->loop->delReadStream($output);
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

        $this->loop->addReadStream($output, function ($output) {
            $chunk = fread($output, 1024);
            if ($chunk === '') {
                $this->received .= 'X';
                $this->loop->delReadStream($output);
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

    /** 在添加读流之后立即移除 */
    public function testRemoveReadStreamInstantlyBIO()
    {
        list ($input, $output) = $this->createSocketPair();
        stream_set_blocking($input, true);
        stream_set_blocking($output, true);
        $count = 0;
        $this->loop->addReadStream($input, function() use(&$count){
            $count ++;
        });
        $this->loop->delReadStream($input);

        fwrite($output, "bar\n");
        $this->tickLoop();

        $this->assertEquals(0, $count);
    }

    /** 在添加读流之后立即移除 */
    public function testRemoveReadStreamInstantlyNIO()
    {
        list ($input, $output) = $this->createSocketPair();
        stream_set_blocking($input, false);
        stream_set_blocking($output, false);
        $count = 0;
        $this->loop->addReadStream($input, function() use(&$count){
            $count ++;
        });
        $this->loop->delReadStream($input);

        fwrite($output, "bar\n");
        $this->tickLoop();

        $this->assertEquals(0, $count);
    }

    /** 读流读取后移除 */
    public function testRemoveReadStreamAfterReadingBIO()
    {
        list ($input, $output) = $this->createSocketPair();
        stream_set_blocking($input, true);
        stream_set_blocking($output, true);
        $count = 0;
        $this->loop->addReadStream($input, function() use (&$count){
            $count ++;
        });

        fwrite($output, "foo\n");
        $this->tickLoop();

        $this->loop->delReadStream($input);

        fwrite($output, "bar\n");
        $this->tickLoop();

        $this->assertEquals(1, $count);
    }

    /** 读流读取后移除 */
    public function testRemoveReadStreamAfterReadingNIO()
    {
        list ($input, $output) = $this->createSocketPair();
        stream_set_blocking($input, false);
        stream_set_blocking($output, false);
        $count = 0;
        $this->loop->addReadStream($input, function() use (&$count){
            $count ++;
        });

        fwrite($output, "foo\n");
        $this->tickLoop();

        $this->loop->delReadStream($input);

        fwrite($output, "bar\n");
        $this->tickLoop();

        $this->assertEquals(1, $count);
    }

    /** 测试socket连接成功时创建write stream handler */
    public function testAddWriteStreamHandlerWhenSocketConnectionSucceedsBIO()
    {
        $server = stream_socket_server('127.0.0.1:0');

        $errcode = $errmsg = null;
        $connecting = stream_socket_client(
            stream_socket_get_name($server, false),
            $errcode,
            $errmsg,

            0, STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT
        );
        stream_set_blocking($connecting, true);

        $timeout = $this->loop->addTimer(0.1,0.0, function () use ($connecting) {
            $this->loop->delWriteStream($connecting);
            $this->loop->destroy();
        });

        $called = 0;
        $this->loop->addWriteStream($connecting, function () use (&$called, $connecting, $timeout) {
            $called ++;
            $this->loop->delWriteStream($connecting);
            $this->loop->delTimer($timeout);
            $this->loop->destroy();
        });

        $this->loop->loop();

        $this->assertEquals(1, $called);
    }

    /** 测试socket连接成功时创建write stream handler */
    public function testAddWriteStreamHandlerWhenSocketConnectionSucceedsNIO()
    {
        $server = stream_socket_server('127.0.0.1:0');

        $errcode = $errmsg = null;
        $connecting = stream_socket_client(
            stream_socket_get_name($server, false),
            $errcode,
            $errmsg,

            0, STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT
        );
        stream_set_blocking($connecting, false);

        $timeout = $this->loop->addTimer(0.1,0.0, function () use ($connecting) {
            $this->loop->delWriteStream($connecting);
            $this->loop->destroy();
        });

        $called = 0;
        $this->loop->addWriteStream($connecting, function () use (&$called, $connecting, $timeout) {
            $called ++;
            $this->loop->delWriteStream($connecting);
            $this->loop->delTimer($timeout);
            $this->loop->destroy();
        });

        $this->loop->loop();

        $this->assertEquals(1, $called);
    }

    /** socket连接被拒绝时添加write stream handler */
    public function testAddWriteStreamHandlerWhenSocketConnectionRefusedBIO()
    {

        // first verify the operating system actually refuses the connection and no firewall is in place
        // use higher timeout because Windows retires multiple times and has a noticeable delay
        // @link https://stackoverflow.com/questions/19440364/why-do-failed-attempts-of-socket-connect-take-1-sec-on-windows
        $errcode = $errmsg = null;
        if (
            @stream_socket_client('127.0.0.1:1', $errcode, $errmsg, 10.0) !== false or
            (defined('SOCKET_ECONNREFUSED') and $errcode !== SOCKET_ECONNREFUSED)
        ) {
            $this->markTestSkipped('Expected host to refuse connection, but got error ' . $errcode . ': ' . $errmsg);
        }

        $connecting = stream_socket_client(
            '127.0.0.1:1',
            $errcode,
            $errmsg,
            0,
            STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT
        );
        stream_set_blocking($connecting, true);

        $timeout = $this->loop->addTimer(10.0,0.0, function () use ($connecting) {
            $this->loop->delWriteStream($connecting);
            $this->loop->destroy();
        });

        $called = 0;
        $this->loop->addWriteStream($connecting, function () use (&$called, $connecting, $timeout) {
            $called ++;
            $this->loop->delWriteStream($connecting);
            $this->loop->delTimer($timeout);
            $this->loop->destroy();
        });

        $this->loop->loop();

        $this->assertEquals(1, $called);
    }

    /** socket连接被拒绝时添加write stream handler */
    public function testAddWriteStreamHandlerWhenSocketConnectionRefusedNIO()
    {

        // first verify the operating system actually refuses the connection and no firewall is in place
        // use higher timeout because Windows retires multiple times and has a noticeable delay
        // @link https://stackoverflow.com/questions/19440364/why-do-failed-attempts-of-socket-connect-take-1-sec-on-windows
        $errcode = $errmsg = null;
        if (
            @stream_socket_client('127.0.0.1:1', $errcode, $errmsg, 10.0) !== false or
            (defined('SOCKET_ECONNREFUSED') and $errcode !== SOCKET_ECONNREFUSED)
        ) {
            $this->markTestSkipped('Expected host to refuse connection, but got error ' . $errcode . ': ' . $errmsg);
        }

        $connecting = stream_socket_client(
            '127.0.0.1:1',
            $errcode,
            $errmsg,
            0,
            STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT
        );
        stream_set_blocking($connecting, false);

        $timeout = $this->loop->addTimer(10.0,0.0, function () use ($connecting) {
            $this->loop->delWriteStream($connecting);
            $this->loop->destroy();
        });

        $called = 0;
        $this->loop->addWriteStream($connecting, function () use (&$called, $connecting, $timeout) {
            $called ++;
            $this->loop->delWriteStream($connecting);
            $this->loop->delTimer($timeout);
            $this->loop->destroy();
        });

        $this->loop->loop();

        $this->assertEquals(1, $called);
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
        $this->tickLoop();
        $this->assertEquals(2, $count1);
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
        $this->tickLoop();
        $this->assertEquals(2, $count1);
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

        $this->tickLoop();
        $this->assertEquals(2, $count);
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

        $this->tickLoop();
        $this->assertEquals(2, $count);
    }

    /** 添加写流后立即移除 */
    public function testRemoveWriteStreamInstantlyBIO()
    {
        list ($input) = $this->createSocketPair();
        stream_set_blocking($input, true);
        $count = 0;
        $this->loop->addWriteStream($input, function() use(&$count){
            $count ++;
        });
        $this->loop->delWriteStream($input);
        $this->tickLoop();
        $this->assertEquals(0, $count);
    }

    /** 添加写流后立即移除 */
    public function testRemoveWriteStreamInstantlyNIO()
    {
        list ($input) = $this->createSocketPair();
        stream_set_blocking($input, false);
        $count = 0;
        $this->loop->addWriteStream($input, function() use(&$count){
            $count ++;
        });
        $this->loop->delWriteStream($input);
        $this->tickLoop();
        $this->assertEquals(0, $count);
    }

    /** 写流写入后移除 */
    public function testRemoveWriteStreamAfterWritingBIO()
    {
        list ($input) = $this->createSocketPair();
        stream_set_blocking($input, true);
        $count = 0;
        $this->loop->addWriteStream($input, function() use (&$count){
            $count ++;
        });
        $this->tickLoop();

        $this->loop->delWriteStream($input);
        $this->tickLoop();
        $this->assertEquals(1, $count);
    }

    /** 写流写入后移除 */
    public function testRemoveWriteStreamAfterWritingNIO()
    {
        list ($input) = $this->createSocketPair();
        stream_set_blocking($input, false);
        $count = 0;
        $this->loop->addWriteStream($input, function() use (&$count){
            $count ++;
        });
        $this->tickLoop();

        $this->loop->delWriteStream($input);
        $this->tickLoop();
        $this->assertEquals(1, $count);
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

        $this->loop->addReadStream($input1, function ($stream) {
            $this->loop->delReadStream($stream);
        });

        $this->loop->addReadStream($input2, function ($stream) {
            $this->loop->delReadStream($stream);
        });

        fwrite($output1, "foo1\n");
        fwrite($output2, "foo2\n");

        $this->tickLoop();

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

        $this->loop->addReadStream($input1, function ($stream) {
            $this->loop->delReadStream($stream);
        });

        $this->loop->addReadStream($input2, function ($stream) {
            $this->loop->delReadStream($stream);
        });

        fwrite($output1, "foo1\n");
        fwrite($output2, "foo2\n");

        $this->tickLoop();

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

        $this->loop->addWriteStream($input1, function ($stream) {
            $this->loop->delWriteStream($stream);
        });

        $this->loop->addWriteStream($input2, function ($stream) {
            $this->loop->delWriteStream($stream);
        });

        $this->tickLoop();

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

        $this->loop->addWriteStream($input1, function ($stream) {
            $this->loop->delWriteStream($stream);
        });

        $this->loop->addWriteStream($input2, function ($stream) {
            $this->loop->delWriteStream($stream);
        });

        $this->tickLoop();

        $this->assertCount(0, $this->loop->getWriteFds());
        $this->assertCount(0, $this->loop->getWrites());
    }

    /** 仅移除读流 */
    public function testRemoveStreamForReadOnlyBIO()
    {
        list ($input, $output) = $this->createSocketPair();
        stream_set_blocking($input, true);
        stream_set_blocking($output, true);
        $count1 = $count2 = 0;
        $this->loop->addReadStream($input, function() use(&$count1){
            $count1 ++;
        });
        $this->loop->addWriteStream($output, function() use(&$count2){
            $count2 ++;
        });
        $this->loop->delReadStream($input);

        fwrite($output, "foo\n");
        $this->tickLoop();
        $this->assertEquals(0, $count1);
        $this->assertEquals(1, $count2);
    }

    /** 仅移除读流 */
    public function testRemoveStreamForReadOnlyNIO()
    {
        list ($input, $output) = $this->createSocketPair();
        stream_set_blocking($input, false);
        stream_set_blocking($output, false);
        $count1 = $count2 = 0;
        $this->loop->addReadStream($input, function() use(&$count1){
            $count1 ++;
        });
        $this->loop->addWriteStream($output, function() use(&$count2){
            $count2 ++;
        });
        $this->loop->delReadStream($input);

        fwrite($output, "foo\n");
        $this->tickLoop();
        $this->assertEquals(0, $count1);
        $this->assertEquals(1, $count2);
    }

    /** 仅移除写流 */
    public function testRemoveStreamForWriteOnlyBIO()
    {
        list ($input, $output) = $this->createSocketPair();
        stream_set_blocking($input, true);
        stream_set_blocking($output, true);
        fwrite($output, "foo\n");

        $count1 = $count2 = 0;
        $this->loop->addReadStream($input, function() use(&$count1){
            $count1 ++;
        });
        $this->loop->addWriteStream($output, function() use(&$count2){
            $count2 ++;
        });
        $this->loop->delWriteStream($output);

        $this->tickLoop();
        $this->assertEquals(1, $count1);
        $this->assertEquals(0, $count2);
    }

    /** 仅移除写流 */
    public function testRemoveStreamForWriteOnlyNIO()
    {
        list ($input, $output) = $this->createSocketPair();
        stream_set_blocking($input, false);
        stream_set_blocking($output, false);
        fwrite($output, "foo\n");

        $count1 = $count2 = 0;
        $this->loop->addReadStream($input, function() use(&$count1){
            $count1 ++;
        });
        $this->loop->addWriteStream($output, function() use(&$count2){
            $count2 ++;
        });
        $this->loop->delWriteStream($output);

        $this->tickLoop();
        $this->assertEquals(1, $count1);
        $this->assertEquals(0, $count2);
    }

    /** 移除未注册的流事件 */
    public function testRemoveUnregisteredStreamBIO()
    {
        list ($stream) = $this->createSocketPair();
        stream_set_blocking($stream, true);
        $this->loop->delReadStream($stream);
        $this->loop->delWriteStream($stream);

        $this->assertTrue(true);
    }

    /** 移除未注册的流事件 */
    public function testRemoveUnregisteredStreamNIO()
    {
        list ($stream) = $this->createSocketPair();
        stream_set_blocking($stream, false);
        $this->loop->delReadStream($stream);
        $this->loop->delWriteStream($stream);

        $this->assertTrue(true);
    }

    # 定时器

    /** 无延迟单次定时器 */
    public function testNonDelayOneShotTimer()
    {
        $called = false;

        $this->loop->addTimer(0.0,0.0, function() use (&$called){
            $called = true;
        });

        $this->assertFalse($called);

        $this->tickLoop();

        $this->assertTrue($called);
    }

    /** 无延迟循环定时器 */
    public function testNonDelayRepeatTimer()
    {

        $count = 0;
        $id = $this->loop->addTimer(0.0,0.1, function () use(&$count, &$id){
            $count ++;
            if($count < 3){
                $this->loop->delTimer($id);
                $this->loop->destroy();
            }
        });

        $this->assertRunFasterThan($this->tickTimeout + 0.3);
    }

    /** 有延迟循环定时器 */
    public function testDelayRepeatTimer()
    {
        $count = 0;
        $id = $this->loop->addTimer(0.1,0.1, function () use(&$count, &$id){
            $count ++;
            if($count < 3){
                $this->loop->delTimer($id);
                $this->loop->destroy();
            }
        });

        $this->assertRunFasterThan($this->tickTimeout + 0.4);

        $count = 0;
        $id = $this->loop->addTimer(0.2,0.1, function () use(&$count, &$id){
            $count ++;
            if($count < 3){
                $this->loop->delTimer($id);
                $this->loop->destroy();
            }
        });

        $this->assertRunFasterThan($this->tickTimeout + 0.5);
    }

    /** 无延迟单次定时器嵌套 */
    public function testNonDelayOneShotTimerNesting()
    {
        $this->loop->addTimer(0.0,0.0,
            function () {
                $this->loop->addTimer(0.0,0.0,
                    function () {
                        echo 'non-delay one-shot timer' . PHP_EOL;
                        $this->loop->destroy();
                    }
                );
            }
        );

        $this->expectOutputString('non-delay one-shot timer' . PHP_EOL);

        $this->loop->loop();
    }

    /** 无延迟单次定时器在BIO之后触发 */
    public function testNonDelayOneShotTimerFiresAfterBIO()
    {
        if($this->loop instanceof EvLoop){
            $this->markTestSkipped('Timer in Evloop executes before IO.');
        }
        list ($stream) = $this->createSocketPair();
        stream_set_blocking($stream, true);
        $this->loop->addTimer(0.0,0.0,
            function () {
                echo 'non-delay one-shot timer' . PHP_EOL;
            }
        );
        $this->loop->addWriteStream(
            $stream,
            function () {
                echo 'stream' . PHP_EOL;
            }
        );
        $this->expectOutputString('stream' . PHP_EOL . 'non-delay one-shot timer' . PHP_EOL);
        $this->tickLoop();
    }

    /** 无延迟单次定时器在NIO之后触发 */
    public function testNonDelayOneShotTimerFiresAfterNIO()
    {
        if($this->loop instanceof EvLoop){
            $this->markTestSkipped('Timer in Evloop executes before IO.');
        }
        list ($stream) = $this->createSocketPair();
        stream_set_blocking($stream, false);
        $this->loop->addTimer(0.0,0.0,
            function () {
                echo 'non-delay one-shot timer' . PHP_EOL;
            }
        );

        $this->loop->addWriteStream(
            $stream,
            function () {
                echo 'stream' . PHP_EOL;
            }
        );

        $this->expectOutputString('stream' . PHP_EOL . 'non-delay one-shot timer' . PHP_EOL);

        $this->tickLoop();
    }

    /** 定时器最大间隔 */
    public function testTimerIntervalCanBeFarInFuture()
    {
        $interval = ((int) (PHP_INT_MAX / 1000)) - 1;
        $timer = $this->loop->addTimer((float)$interval,0.0, function () {});
        $this->loop->addTimer(0.0,0.0,function () use ($timer) {
            $this->loop->delTimer($timer);
            $this->loop->destroy();
        });
        $this->assertRunFasterThan($this->tickTimeout);
    }

    # 信号相关

    /** @runInSeparateProcess 移除一个未注册的信号 */
    public function testDelSignalNotRegisteredIsNoOp()
    {
        $this->loop->delSignal(2, function () {});
        $this->assertTrue(true);
    }

    /** @runInSeparateProcess 测试信号响应 */
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

        $this->loop->addTimer(1.0,0.0, function () {});

        $this->loop->addSignal(10, $func = function () use (&$funcCallCount) {
            $funcCallCount ++;
        });
        $this->loop->addSignal(10, $func);

        $this->loop->addTimer(0.4,0.0, function () {
            posix_kill(posix_getpid(), 10);
        });

        $this->loop->addTimer(0.9,0.0, function () use (&$func) {
            $this->loop->delSignal(10, $func);
            $this->loop->destroy();
        });

        $this->loop->loop();

        $this->assertEquals(1, $funcCallCount);
    }

    # 其他

    /** 测试移除回调 */
    public function testIgnoreRemovedCallback()
    {
        // two independent streams, both should be readable right away
        list ($input1, $output1) = $this->createSocketPair();
        list ($input2, $output2) = $this->createSocketPair();

        $called = false;

        $this->loop->addReadStream($input1, function ($stream) use (& $called, $input2) {
            // stream1 is readable, remove stream2 as well => this will invalidate its callback
            $this->loop->delReadStream($stream);
            $this->loop->delReadStream($input2);
            $this->loop->destroy();

            $called = true;
        });

        // this callback would have to be called as well, but the first stream already removed us
        $that = $this;
        $this->loop->addReadStream($input2, function () use (& $called, $that) {
            if ($called) {
                $that->fail('Callback 2 must not be called after callback 1 was called');
            }
        });

        fwrite($output1, "foo\n");
        fwrite($output2, "foo\n");

        $this->loop->loop();

        $this->assertTrue($called);
    }

    /** 空循环无法循环 */
    public function testEmptyRunShouldSimplyReturn()
    {
        $this->assertRunFasterThan($this->tickTimeout);
    }

}
