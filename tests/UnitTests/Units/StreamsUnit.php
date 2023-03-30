<?php declare(strict_types=1);

namespace WorkBunny\Tests\UnitTests\Units;

trait StreamsUnit
{
    public static function provider(): array
    {
        return [
            [true],
            [false]
        ];
    }

    /**
     * 测试socket接收数据时创建read stream handler
     *
     * @dataProvider provider
     * @param bool $bio
     * @return void
     */
    public function testAddReadStreamHandlerWhenSocketReceivesData(bool $bio): void
    {
        list ($input, $output) = \stream_socket_pair(
            (DIRECTORY_SEPARATOR === '\\') ? STREAM_PF_INET : STREAM_PF_UNIX,
            STREAM_SOCK_STREAM,
            STREAM_IPPROTO_IP
        );
        stream_set_blocking($input, $bio);
        stream_set_blocking($output, $bio);

        $loop = $this->getLoop();
        $timeout = $loop->addTimer(0.1,0.0, function () use ($input, $loop) {
            $loop->delReadStream($input);
            $loop->stop();
        });

        $called = 0;
        $this->getLoop()->addReadStream($input, function () use (&$called, $loop, $input, $timeout) {
            ++$called;
            $loop->delReadStream($input);
            $loop->delTimer($timeout);
            $loop->stop();
        });

        fwrite($output, 'foo' . PHP_EOL);

        $this->getLoop()->run();

        $this->assertEquals(1, $called);
    }

    /**
     * 测试socket关闭时创建read stream handler
     *
     * @dataProvider provider
     * @param bool $bio
     * @return void
     */
    public function testAddReadStreamHandlerWhenSocketCloses(bool $bio): void
    {
        list ($input, $output) = \stream_socket_pair(
            (DIRECTORY_SEPARATOR === '\\') ? STREAM_PF_INET : STREAM_PF_UNIX,
            STREAM_SOCK_STREAM,
            STREAM_IPPROTO_IP
        );

        stream_set_blocking($input, $bio);
        stream_set_blocking($output, $bio);

        $loop = $this->getLoop();
        $timeout = $loop->addTimer(0.1,0.0, function () use ($input, $loop) {
            $loop->delReadStream($input);
            $loop->stop();
        });

        $called = 0;
        $this->getLoop()->addReadStream($input, function () use (&$called, $loop, $input, $timeout) {
            ++$called;
            $loop->delReadStream($input);
            $loop->delTimer($timeout);
            $loop->stop();
        });

        fclose($output);

        $this->getLoop()->run();

        $this->assertEquals(1, $called);
    }

    /**
     * read stream重复创建后者无效
     *
     * @dataProvider provider
     * @param bool $bio
     * @return void
     */
    public function testAddReadStreamIgnoresSecondAddReadStream(bool $bio): void
    {
        list ($input, $output) = \stream_socket_pair(
            (DIRECTORY_SEPARATOR === '\\') ? STREAM_PF_INET : STREAM_PF_UNIX,
            STREAM_SOCK_STREAM,
            STREAM_IPPROTO_IP
        );

        stream_set_blocking($input, $bio);
        stream_set_blocking($output, $bio);

        $count1 = 0;
        $count2 = 0;
        $this->getLoop()->addReadStream($input, function () use(&$count1){
            $count1 ++;
        });
        $this->getLoop()->addReadStream($input, function () use(&$count2){
            $count2 ++;
        });

        $this->assertCount(1, $this->getLoop()->getReadFds());

        fwrite($output, 'foo' . PHP_EOL);
        $this->tickLoop();

        $this->assertEquals(1, $count1);
        $this->assertEquals(0, $count2);
    }

    /**
     * 读流处理器多次触发
     *
     * @dataProvider provider
     * @param bool $bio
     * @return void
     */
    public function testReadStreamHandlerTriggeredMultiTimes(bool $bio): void
    {
        list ($input, $output) = \stream_socket_pair(
            (DIRECTORY_SEPARATOR === '\\') ? STREAM_PF_INET : STREAM_PF_UNIX,
            STREAM_SOCK_STREAM,
            STREAM_IPPROTO_IP
        );
        stream_set_blocking($input, $bio);
        stream_set_blocking($output, $bio);

        $count = 0;
        $this->getLoop()->addReadStream($input, function() use(&$count){
            $count ++;
        });

        fwrite($output, 'foo' . PHP_EOL);
        $this->tickLoop();

        fwrite($output, 'bar' . PHP_EOL);
        $this->tickLoop();

        $this->assertEquals(2, $count);
    }

    /** 
     * 读流处理器可以引用接受数据
     *
     * @dataProvider provider
     * @param bool $bio
     * @return void 
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

        $this->getLoop()->addReadStream($output, function ($output) {
            $chunk = fread($output, 1024);
            if ($chunk === '') {
                $this->received .= 'X';
                $this->getLoop()->delReadStream($output);
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
     * 在添加读流之后立即移除
     *
     * @dataProvider provider
     * @param bool $bio
     * @return void
     */
    public function testRemoveReadStreamInstantly(bool $bio): void
    {
        list ($input, $output) = \stream_socket_pair(
            (DIRECTORY_SEPARATOR === '\\') ? STREAM_PF_INET : STREAM_PF_UNIX,
            STREAM_SOCK_STREAM,
            STREAM_IPPROTO_IP
        );
        stream_set_blocking($input, $bio);
        stream_set_blocking($output, $bio);
        $count = 0;
        $this->getLoop()->addReadStream($input, function() use(&$count){
            $count ++;
        });
        $this->getLoop()->delReadStream($input);

        fwrite($output, 'bar' . PHP_EOL);
        $this->tickLoop();

        $this->assertEquals(0, $count);
    }

    /** 
     * 读流读取后移除
     *
     * @dataProvider provider
     * @param bool $bio
     * @return void
     */
    public function testRemoveReadStreamAfterReading(bool $bio): void
    {
        list ($input, $output) = \stream_socket_pair(
            (DIRECTORY_SEPARATOR === '\\') ? STREAM_PF_INET : STREAM_PF_UNIX,
            STREAM_SOCK_STREAM,
            STREAM_IPPROTO_IP
        );
        stream_set_blocking($input, $bio);
        stream_set_blocking($output, $bio);
        $count = 0;
        $this->getLoop()->addReadStream($input, function() use (&$count){
            $count ++;
        });

        fwrite($output, 'foo' . PHP_EOL);
        $this->tickLoop();

        $this->getLoop()->delReadStream($input);

        fwrite($output, 'bar' . PHP_EOL);
        $this->tickLoop();

        $this->assertEquals(1, $count);
    }

    /** 
     * 测试socket连接成功时创建write stream handler
     *
     * @dataProvider provider
     * @param bool $bio
     * @return void
     */
    public function testAddWriteStreamHandlerWhenSocketConnectionSucceeds(bool $bio): void
    {
        $server = stream_socket_server('127.0.0.1:0');

        $errcode = $errmsg = null;
        $connecting = stream_socket_client(
            stream_socket_get_name($server, false),
            $errcode,
            $errmsg,

            0, STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT
        );
        stream_set_blocking($connecting, $bio);

        $timeout = $this->getLoop()->addTimer(0.1,0.0, function () use ($connecting) {
            $this->getLoop()->delWriteStream($connecting);
            $this->getLoop()->destroy();
        });

        $called = 0;
        $this->getLoop()->addWriteStream($connecting, function () use (&$called, $connecting, $timeout) {
            $called ++;
            $this->getLoop()->delWriteStream($connecting);
            $this->getLoop()->delTimer($timeout);
            $this->getLoop()->destroy();
        });

        $this->getLoop()->run();

        $this->assertEquals(1, $called);
    }

    /** 
     * socket连接被拒绝时添加write stream handler
     *
     * @dataProvider provider
     * @param bool $bio
     * @return void
     */
    public function testAddWriteStreamHandlerWhenSocketConnectionRefused(bool $bio): void
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
        stream_set_blocking($connecting, $bio);

        $timeout = $this->getLoop()->addTimer(10.0,0.0, function () use ($connecting) {
            $this->getLoop()->delWriteStream($connecting);
            $this->getLoop()->destroy();
        });

        $called = 0;
        $this->getLoop()->addWriteStream($connecting, function () use (&$called, $connecting, $timeout) {
            $called ++;
            $this->getLoop()->delWriteStream($connecting);
            $this->getLoop()->delTimer($timeout);
            $this->getLoop()->destroy();
        });

        $this->getLoop()->run();

        $this->assertEquals(1, $called);
    }

    /** 
     * write stream 重复创建后者忽略
     *
     * @dataProvider provider
     * @param bool $bio
     * @return void
     */
    public function testAddWriteStreamIgnoresSecondAddWriteStream(bool $bio): void
    {
        list ($input) = \stream_socket_pair(
            (DIRECTORY_SEPARATOR === '\\') ? STREAM_PF_INET : STREAM_PF_UNIX,
            STREAM_SOCK_STREAM,
            STREAM_IPPROTO_IP
        );
        stream_set_blocking($input, $bio);
        $count1 = $count2 = 0;
        $this->getLoop()->addWriteStream($input, function() use(&$count1){
            $count1 ++;
        });
        $this->getLoop()->addWriteStream($input, function() use(&$count2){
            $count2 ++;
        });
        $this->assertCount(1, $this->getLoop()->getWriteFds());
        $this->tickLoop();
        $this->assertEquals(1, $count1);
        $this->assertEquals(0, $count2);
    }
    
    /** 
     * 写流处理器的多次触发
     *
     * @dataProvider provider
     * @param bool $bio
     * @return void
     */
    public function testWriteStreamHandlerTriggeredMultiTimes(bool $bio): void
    {
        list ($input) = \stream_socket_pair(
            (DIRECTORY_SEPARATOR === '\\') ? STREAM_PF_INET : STREAM_PF_UNIX,
            STREAM_SOCK_STREAM,
            STREAM_IPPROTO_IP
        );
        stream_set_blocking($input, $bio);
        $count = 0;
        $this->getLoop()->addWriteStream($input, function() use(&$count){
            $count ++;
        });
        $this->tickLoop();

        $this->tickLoop();
        $this->assertEquals(2, $count);
    }

    /** 
     * 添加写流后立即移除
     *
     * @dataProvider provider
     * @param bool $bio
     * @return void
     */
    public function testRemoveWriteStreamInstantly(bool $bio): void
    {
        list ($input) = \stream_socket_pair(
            (DIRECTORY_SEPARATOR === '\\') ? STREAM_PF_INET : STREAM_PF_UNIX,
            STREAM_SOCK_STREAM,
            STREAM_IPPROTO_IP
        );
        stream_set_blocking($input, $bio);
        $count = 0;
        $this->getLoop()->addWriteStream($input, function() use(&$count){
            $count ++;
        });
        $this->getLoop()->delWriteStream($input);
        $this->tickLoop();
        $this->assertEquals(0, $count);
    }

    /** 
     * 写流写入后移除
     *
     * @dataProvider provider
     * @param bool $bio
     * @return void
     */
    public function testRemoveWriteStreamAfterWriting(bool $bio): void
    {
        list ($input) = \stream_socket_pair(
            (DIRECTORY_SEPARATOR === '\\') ? STREAM_PF_INET : STREAM_PF_UNIX,
            STREAM_SOCK_STREAM,
            STREAM_IPPROTO_IP
        );
        stream_set_blocking($input, $bio);
        $count = 0;
        $this->getLoop()->addWriteStream($input, function() use (&$count){
            $count ++;
        });
        $this->tickLoop();

        $this->getLoop()->delWriteStream($input);
        $this->tickLoop();
        $this->assertEquals(1, $count);
    }

    /** 
     * 移除读流
     *
     * @dataProvider provider
     * @param bool $bio
     * @return void 
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

        $this->getLoop()->addReadStream($input1, function ($stream) {
            $this->getLoop()->delReadStream($stream);
        });

        $this->getLoop()->addReadStream($input2, function ($stream) {
            $this->getLoop()->delReadStream($stream);
        });

        fwrite($output1, "foo1\n");
        fwrite($output2, "foo2\n");

        $this->tickLoop();

        $this->assertCount(0, $this->getLoop()->getReadFds());
        $this->assertCount(0, $this->getLoop()->getReads());
    }

    /** 
     * 移除写流
     *
     * @dataProvider provider
     * @param bool $bio
     * @return void 
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

        $this->getLoop()->addWriteStream($input1, function ($stream) {
            $this->getLoop()->delWriteStream($stream);
        });

        $this->getLoop()->addWriteStream($input2, function ($stream) {
            $this->getLoop()->delWriteStream($stream);
        });

        $this->tickLoop();

        $this->assertCount(0, $this->getLoop()->getWriteFds());
        $this->assertCount(0, $this->getLoop()->getWrites());
    }

    /** 
     * 仅移除读流
     *
     * @dataProvider provider
     * @param bool $bio
     * @return void
     */
    public function testRemoveStreamForReadOnly(bool $bio): void
    {
        list ($input, $output) = \stream_socket_pair(
            (DIRECTORY_SEPARATOR === '\\') ? STREAM_PF_INET : STREAM_PF_UNIX,
            STREAM_SOCK_STREAM,
            STREAM_IPPROTO_IP
        );
        stream_set_blocking($input, $bio);
        stream_set_blocking($output, $bio);
        $count1 = $count2 = 0;
        $this->getLoop()->addReadStream($input, function() use(&$count1){
            $count1 ++;
        });
        $this->getLoop()->addWriteStream($output, function() use(&$count2){
            $count2 ++;
        });
        $this->getLoop()->delReadStream($input);

        fwrite($output, 'foo' . PHP_EOL);
        $this->tickLoop();
        $this->assertEquals(0, $count1);
        $this->assertEquals(1, $count2);
    }

    /** 
     * 仅移除写流
     *
     * @dataProvider provider
     * @param bool $bio
     * @return void
     */
    public function testRemoveStreamForWriteOnly(bool $bio): void
    {
        list ($input, $output) = \stream_socket_pair(
            (DIRECTORY_SEPARATOR === '\\') ? STREAM_PF_INET : STREAM_PF_UNIX,
            STREAM_SOCK_STREAM,
            STREAM_IPPROTO_IP
        );
        stream_set_blocking($input, $bio);
        stream_set_blocking($output, $bio);
        fwrite($output, 'foo' . PHP_EOL);

        $count1 = $count2 = 0;
        $this->getLoop()->addReadStream($input, function() use(&$count1){
            $count1 ++;
        });
        $this->getLoop()->addWriteStream($output, function() use(&$count2){
            $count2 ++;
        });
        $this->getLoop()->delWriteStream($output);

        $this->tickLoop();
        $this->assertEquals(1, $count1);
        $this->assertEquals(0, $count2);
    }

    /** 
     * 移除未注册的流事件
     *
     * @dataProvider provider
     * @param bool $bio
     * @return void
     */
    public function testRemoveUnregisteredStream(bool $bio): void
    {
        list ($stream) = \stream_socket_pair(
            (DIRECTORY_SEPARATOR === '\\') ? STREAM_PF_INET : STREAM_PF_UNIX,
            STREAM_SOCK_STREAM,
            STREAM_IPPROTO_IP
        );
        stream_set_blocking($stream, $bio);
        $this->getLoop()->delReadStream($stream);
        $this->getLoop()->delWriteStream($stream);

        $this->assertTrue(true);
    }

    /**
     * 读流先于timer触发
     *
     * @dataProvider provider
     * @param bool $bio
     * @return void
     */
    public function testReadStreamBeforeTimer(bool $bio): void
    {
        list ($input, $output) = \stream_socket_pair(
            (DIRECTORY_SEPARATOR === '\\') ? STREAM_PF_INET : STREAM_PF_UNIX,
            STREAM_SOCK_STREAM,
            STREAM_IPPROTO_IP
        );
        stream_set_blocking($input, $bio);
        stream_set_blocking($output, $bio);
        fwrite($input, 'read' . PHP_EOL);

        $this->expectOutputString('read' . PHP_EOL . 'timer' . PHP_EOL);
        $this->getLoop()->addTimer(0.0,false, function () {
            echo 'timer' . PHP_EOL;
        });
        $this->getLoop()->addReadStream($output, function($stream) {
            $this->getLoop()->delReadStream($stream);
            echo 'read' . PHP_EOL;
        });
        $this->tickLoop($this->tickTimeout);
    }

    /**
     * 写流先于timer触发
     *
     * @dataProvider provider
     * @param bool $bio
     * @return void
     */
    public function testWriteStreamBeforeTimer(bool $bio): void
    {
        list ($input, $output) = \stream_socket_pair(
            (DIRECTORY_SEPARATOR === '\\') ? STREAM_PF_INET : STREAM_PF_UNIX,
            STREAM_SOCK_STREAM,
            STREAM_IPPROTO_IP
        );
        stream_set_blocking($input, $bio);
        stream_set_blocking($output, $bio);
        fwrite($input, 'write' . PHP_EOL);

        $this->expectOutputString('write' . PHP_EOL . 'timer' . PHP_EOL);
        $this->getLoop()->addTimer(0.0,false, function () {
            echo 'timer' . PHP_EOL;
        });
        $this->getLoop()->addWriteStream(\STDOUT, function($stream) {
            $this->getLoop()->delWriteStream($stream);
            echo 'write' . PHP_EOL;
        });
        $this->tickLoop($this->tickTimeout);
    }
}