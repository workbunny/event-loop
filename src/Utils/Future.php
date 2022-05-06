<?php
declare(strict_types=1);

namespace WorkBunny\EventLoop\Utils;

use SplPriorityQueue;

final class Future
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
        $this->_scheduler->setExtractFlags(SplPriorityQueue::EXTR_DATA);
    }

    /**
     * @param callable $handler 处理回调
     * @param int $priority 优先级
     * @return int id
     */
    public function add(callable $handler, int $priority = 0): int
    {
        $this->_scheduler->insert($this->_counter->id(), $priority);
        return $this->_counter->add($handler);
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
            $futureId = $this->_scheduler->extract();
            if($handler = $this->_counter->get($futureId)){
                \call_user_func($handler);
            }
        }
    }

}