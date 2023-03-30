
<p align="center"><img width="260px" src="https://chaz6chez.cn/images/workbunny-logo.png" alt="workbunny"></p>

**<p align="center">workbunny/event-loop</p>**

**<p align="center">🐇 A high-performance event loop library for PHP 🐇</p>**

<div align="center">
    <a href="https://github.com/workbunny/event-loop/actions">
        <img src="https://github.com/workbunny/event-loop/actions/workflows/CI.yml/badge.svg" alt="Build Status">
    </a>
    <a href="https://github.com/workbunny/event-loop/blob/main/composer.json">
        <img alt="PHP Version Require" src="http://poser.pugx.org/workbunny/event-loop/require/php">
    </a>
    <a href="https://github.com/workbunny/event-loop/blob/main/LICENSE">
        <img alt="GitHub license" src="http://poser.pugx.org/workbunny/event-loop/license">
    </a>
    
</div>


## 简介

    一个事件循环库，目的是为了构建高性能网络应用。

## 使用

注：本文档为 2.x 版本，旧版请点击 **[1.x 版本](https://github.com/workbunny/event-loop/tree/1.x)** 跳转

### 安装
```
composer require workbunny/event-loop
```

### 创建loop

```php
use WorkBunny\EventLoop\Loop;
use WorkBunny\EventLoop\Drivers\NativeLoop;
use WorkBunny\EventLoop\Drivers\EventLoop;
use WorkBunny\EventLoop\Drivers\EvLoop;
use WorkBunny\EventLoop\Drivers\SwowLoop;

// 创建PHP原生loop
$loop = Loop::create(NativeLoop::class);
// 创建ext-event loop
$loop = Loop::create(EventLoop::class);
// 创建ext-ev loop
$loop = Loop::create(EvLoop::class);
// 创建swow loop
$loop = Loop::create(SwowLoop::class);
```

### 注册loop

- 创建 YourLoopClass 实现 LoopInterface
- 调用 Loop::register() 注册 YourLoopClass

```php
use WorkBunny\EventLoop\Loop;
// 注册
loop::register(YourLoopClass::class);
// 创建
$yourLoop = Loop::create(YourLoopClass::class);
```

### 创建定时器

- Future 触发器
```php
/**
 * @Future [delay=0.0, repeat=false]
 *  在下一个周期执行，执行一次即自动销毁
 */
$loop->addTimer(0.0, false, function (){ echo 'timer'; }); // loop->run()后立即输出字符串
```

- ReFuture 重复触发器
```php
/**
 * @ReFuture [delay=0.0, repeat=0.0]
 *  在每一个周期执行，不会自动销毁
 */
$id = $loop->addTimer(0.0, 0.0, function () use(&$loop, &$id) {
    // 此方法可以实现自我销毁
    $loop->delTimer($id);
});
```

- DelayReFuture 延迟的重复触发器
```php
/**
 * @DelayReFuture [delay>0.0, repeat=0.0]
 *  延迟delay秒后每一个周期执行，不会自动销毁
 */
$id = $loop->addTimer(1.0, 0.0, function () use(&$loop, &$id) {
    // 此方法可以实现自我销毁
    $loop->delTimer($id);
});
```

- Delayer 延迟器
```php
/**
 * @Delayer [delay>0.0, repeat=false]
 *  延迟delay秒后执行，执行一次即自动销毁
 */
$loop->addTimer(2.0, false, function (){ echo 'timer'; }); // loop->run() 2秒后输出字符串
```

- Timer 定时器
```php
/**
 * @Timer [delay=0.0, repeat>0.0]
 *  在下一个周期开始每间隔repeat秒执行，不会自动销毁
 */
$id = $loop->addTimer(0.1, 0.1, function () use(&$loop, &$id) {
    // 此方法可以实现自我销毁
    $loop->delTimer($id);
});
```

- DelayTimer 延迟的定时器
```php
/**
 * @DelayTimer [delay>0.0, repeat>0.0]
 *  延迟delay秒后每间隔repeat秒执行，不会自动销毁
 */
$id = $loop->addTimer(0.2, 0.1, function () use(&$loop, &$id) {
    // 此方法可以实现自我销毁
    $loop->delTimer($id);
});
```

### 流事件

  这里的流是指 **[PHP Streams](https://www.php.net/manual/zh/book.stream.php)**

- 读取流
```php
// 创建
$loop->addReadStream(resource, function (resource $stream) { });
// 注意：EvLoop在这里较为特殊，回调函数的入参为EvIo对象
$loop->addReadStream(resource, function (\EvIo $evio) {
    $evio->stream // resource 资源类型
});
// 移除
$loop->delReadStream(resource);
```

- 写入流
```php
// 创建
$loop->addWriteStream(resource, function (resource $stream) { });
// 注意：EvLoop在这里较为特殊，回调函数的入参为EvIo对象
$loop->addWriteStream(resource, function (\EvIo $evio) {
    $evio->stream // resource 资源类型
});
// 移除
$loop->delWriteStream(resource);
```

### 信号事件

  用于接收系统的信号，比如kill等
```php
// 注册
$loop->addSignal(\SIGUSR1, function (){});
// 移除
$loop->delSignal(\SIGUSR1, function (){});
```

### 启动/停止

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

---
