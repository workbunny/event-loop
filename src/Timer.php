<?php
declare(strict_types=1);

namespace EventLoop;

use Closure;

final class Timer
{
    private float $delay;

    private float $repeat;

    private Closure $handler;

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