# workbunny/event-loop

**🐇 A high-performance event loop library for PHP 🐇**

## 简介

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

### 1. 安装
```
composer require workbunny/event-loop
```

### 2. 创建loop

```php
use WorkBunny\EventLoop\Loop;
use WorkBunny\EventLoop\Drivers\NativeLoop;
use WorkBunny\EventLoop\Drivers\EventLoop;
use WorkBunny\EventLoop\Drivers\EvLoop;
use WorkBunny\EventLoop\Drivers\OpenSwooleLoop;

# 创建PHP原生loop
$loop = Loop::create(NativeLoop::class);

# 创建ext-event loop
$loop = Loop::create(EventLoop::class);

# 创建ext-ev loop
$loop = Loop::create(EvLoop::class);

# 创建ext-openswoole loop
$loop = Loop::create(OpenSwooleLoop::class);
```

### 3. 创建定时器

- 无延迟触发器

  通过loop立即执行一次回调函数；
```php
# 立即执行
$id = $loop->addTimer(0.0, 0.0, function (){
    # 业务
});
```

- 延迟触发器

  延迟 delay 参数的数值后，执行注册的回调函数，仅执行一次；
```php
# 延迟1秒后执行一次
$id = $loop->addTimer(1.0, 0.0, function (){
    # 业务
});
```

- 无延迟定时器

  通过loop立即执行一次回调函数后，根据 repeat 参数的数值间隔执行，直到主动移除该定时器；
```php
# 立即执行一次以后间隔0.1s执行
$id = $loop->addTimer(0.0, 0.1, function (){
    # 业务
});
```

- 延迟定时器

  延迟 delay 参数的数值后，执行一次注册的回调函数，之后根据 repeat 参数的数值间隔执行，直到主动移除该定时器；
```php
# 延迟0.1s后间隔0.1s执行
$id = $loop->addTimer(0.1, 0.1, function (){
    # 业务
});

# 延迟0.5s后间隔0.1s执行
$id = $loop->addTimer(0.5, 0.1, function (){
    # 业务
});
```

### 4. 创建流事件

  这里的流是指 **[PHP Streams](https://www.php.net/manual/zh/book.stream.php)**

- 读取流
```php
$loop->addReadStream(resource, function (){
    # 业务
});
$loop->delReadStream(resource);
```

- 写入流
```php
$loop->addWriteStream(resource, function (){
    # 业务
});
$loop->delWriteStream(resource);
```

### 5. 创建信号事件

  用于接收系统的信号，比如kill等
```php
$loop->addSignal(\SIGUSR1, function (){
    # 业务
});

$loop->delSignal(\SIGUSR1, function (){
    # 业务
});
```

### 6. 启动/停止

- 启动

  以下代码会持续阻塞，请放在程序最后一行
```php
# 该函数后会阻塞
$loop->loop();

# 该行代码不会执行
var_dump('123');
```

- 停止

  以下代码不会阻塞等待
```php
$loop->destroy();

# 该行代码会执行
var_dump('123');
```

## 说明
### 1. 测试用例中各个loop比较特殊的地方会在对应测试用例中说明
- EvLoop 的Stream总是后于Timer **详见EvLoopTest.php**
- EventLoop 的延迟定时器区别于其他Loop的定时器，需要多一个loop周期 **详见EventLoopTest.php**
- OpenSwoole 的读/写流不能通过 **testReadStreamHandlerTriggeredMultiTimes** 测试 **详见OpenSwooleLoopTest.php**

### 2. 相同定时器/触发器的优先级遵循先注册先触发

### 3. OpenSwoole在同一周期内是有优先级的

> 1. 通过 Process::signal 设置的信号处理回调函数
> 
> 2. 通过 Timer::tick 和 Timer::after 设置的定时器回调函数
> 
> 3. 通过 Event::defer 设置的延迟执行函数
> 
> 4. 通过 Event::cycle 设置的周期回调函数


### 4. OpenSwoole的无延迟触发器/无延迟定时器利用了 Event::defer，需要注意优先级

### 5. OpenSwoole的 Event::defer 可以重复注册多个回调