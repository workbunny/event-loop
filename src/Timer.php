<?php
/**
 * This file is part of workbunny.
 *
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    chaz6chez<250220719@qq.com>
 * @copyright chaz6chez<250220719@qq.com>
 * @link      https://github.com/workbunny/event-loop
 * @license   https://github.com/workbunny/event-loop/blob/main/LICENSE
 */
declare(strict_types=1);

namespace WorkBunny\EventLoop;

use Closure;

final class Timer
{
    /** @var float 延迟 */
    private float $delay;

    /** @var float 重复 */
    private float $repeat;

    /** @var Closure 处理函数 */
    private Closure $handler;

    /**
     * @param float $delay
     * @param float $repeat
     * @param Closure $handler
     */
    public function __construct(float $delay, float $repeat, Closure $handler)
    {
        $this->delay = $delay;
        $this->repeat = $repeat;
        $this->handler = $handler;
    }

    /**
     * @return float
     */
    public function getDelay(): float
    {
        return $this->delay;
    }

    /**
     * @return float
     */
    public function getRepeat(): float
    {
        return $this->repeat;
    }

    /**
     * @return Closure
     */
    public function getHandler(): Closure
    {
        return $this->handler;
    }
}