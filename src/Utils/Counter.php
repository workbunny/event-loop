<?php
declare(strict_types=1);

namespace WorkBunny\EventLoop\Utils;

final class Counter
{
    /** @var int id */
    private int $_id = 1;

    /** @var array ids */
    private array $_ids = [];

    /**
     * @param mixed $value
     * @return int
     */
    public function add($value): int
    {
        $this->_ids[$this->_id] = $value;
        return $this->_id ++;
    }

    /**
     * @param int $id
     */
    public function del(int $id): void
    {
        unset($this->_ids[$id]);
    }

    /**
     * @param int $id
     * @return mixed|null
     */
    public function get(int $id)
    {
        return $this->exist($id) ? $this->_ids[$id] : null;
    }

    /**
     * @return int
     */
    public function id(): int
    {
        return $this->_id;
    }

    /**
     * @param int $id
     * @return bool
     */
    public function exist(int $id): bool
    {
        return isset($this->_ids[$id]);
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->_ids);
    }
}