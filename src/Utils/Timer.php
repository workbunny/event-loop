<?php
declare(strict_types=1);

namespace WorkBunny\EventLoop\Utils;

use SplPriorityQueue;

final class Timer
{
    /** @var SplPriorityQueue 创建优先队列 */
    private SplPriorityQueue $_scheduler;

    /** @var Counter 计数器 */
    private Counter $_counter;

    /**
     * TimerTick constructor.
     */
    public function __construct()
    {
        $this->_counter = new Counter();
        $this->_scheduler = new SplPriorityQueue();
        $this->_scheduler->setExtractFlags(SplPriorityQueue::EXTR_BOTH);
    }

    /**
     * @param float $interval 间隔
     * @param callable $handler 处理回调
     * @param bool $periodic 是否周期性
     * @return int id
     */
    public function add(float $interval, callable $handler, bool $periodic): int
    {
        $runTime = \hrtime(true) * 1e-9 + $interval;
        $this->_scheduler->insert($this->_counter->id(), -$runTime);
        return $this->_counter->add([
            'interval' => $interval,
            'periodic' => $periodic,
            'handler'  => $handler
        ]);
    }

    /**
     * @param int $id timer id
     */
    public function del(int $id): void
    {
        $this->_counter->del($id);
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->_scheduler->isEmpty();
    }

    /** 执行 */
    public function tick(): void
    {
        $count = $this->_scheduler->count();
        while ($count--){
            $schedulerData = $this->_scheduler->top();
            $nextRunTime   = -$schedulerData['priority'];
            $timerId       = $schedulerData['data'];
            if($data = $this->_counter->get($timerId)){
                $interval      = $data['interval'];
                $periodic      = $data['periodic'];
                $handler       = $data['handler'];
                $timeNow       = \hrtime(true) * 1e-9;
                if (($nextRunTime - $timeNow) <= 0) {
                    $this->_scheduler->extract();
                    \call_user_func($handler);
                    if($periodic){
                        $nextRunTime = $timeNow + $interval;
                        $this->_scheduler->insert($timerId, -$nextRunTime);
                    }else{
                        $this->del($timerId);
                    }
                }
            }
        }
    }

}