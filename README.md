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
