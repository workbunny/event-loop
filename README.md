# workbunny/event-loop

**🐇 A high-performance event loop library for PHP 🐇**

## 更新

>    🐇 2022-05-27:
>
>    1. ext-ev 的 stream 回调入参是 EvIo 对象，这里 EvIo->fd 获取的 stream 和注册时候的 stream 不是一个流，
> 无法用 (int)stream 做标记，详细请看 EvLoopTest::testRemoveReadStreams() 等流相关测试；
>    
>    2. ext-ev 的无延迟定时器区别于其他循环，是在IO之前触发 EvLoopTest::testNonDelayOneShotTimerFiresBeforeBIO() 等；
>    3. ext-openswoole 信号注册相关有无法通过测试的地方 OpenSwooleLoopTest::testSignalResponse() 等；
>    4. ext-openswoole 定时器慎用无延迟定时器，这里是使用 Event::defer() 结合 Timer 实现的，不能做到注册多个无延迟定时器，
> 后注册的 defer 会覆盖前注册的；

>    🐇 2022-05-09:
>
>    1. 目前ext-parallel还未支持PHP8.X，所以该项目仅实现了简单的基于libevent等基于系统I/O复用事件驱动的event-loop； 
> 等待ext-parallel的支撑。

## 说明

    一个event-loop实验品；

    是一个类似ReactPHP、AMPHP的事件循环组件；

    该项目主要研究ext-parallel和PHP-fiber在event-loop中如何有效结合，
    研究PHP在不利用ext-event、ext-ev、ext-uv等拓展的前提下是否可以实现
    更高的处理能力。

    An event loop experiment;

    It is an event loop component similar to ReactPHP and AMPHP;

    This project mainly studies how ext-parallel and PHP-fiber can
    be effectively combined in event-loop, and studies whether PHP 
    can achieve higher processing power without using extensions 
    like ext-event, ext-ev, ext-uv, etc.

## 使用

- 安装
```
composer require workbunny/event-loop
```

- 定时器

```php
$loop = \WorkBunny\EventLoop\Loop::create(\WorkBunny\EventLoop\Drivers\NativeLoop::class);
$id = $loop->addTimer(0.0, 1.0, function (){
    # 业务
});
$loop->delTimer($id);
```

- 流
```php
$loop->addReadStream(resource, function (){
    # 业务
});
$loop->delReadStream(resource);

$loop->addWriteStream(resource, function (){
    # 业务
});
$loop->delWriteStream(resource);
```

- 信号
```php
$loop->addSignal(\SIGUSR1, function (){
    # 业务
});

$loop->delSignal(\SIGUSR1, function (){
    # 业务
});
```

- 启动/停止
```php
$loop->loop();

$loop->destroy();
```