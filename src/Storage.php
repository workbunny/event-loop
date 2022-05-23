<?php
declare(strict_types=1);

namespace EventLoop;

final class Storage
{
    /** @var int id */
    private int $_id = 1;

    /** @var array storage */
    private array $_storage = [];

    /**
     * @param mixed $value
     * @return int
     */
    public function add($value): int
    {
        $this->_storage[$this->_id] = $value;
        return $this->_id ++;
    }

    /**
     * @param int $id
     * @param $value
     * @return int
     */
    public function set(int $id, $value): int
    {
        if($this->exist($id)){
            $this->_storage[$id] = $value;
        }
        return $id;
    }

    /**
     * @param int $id
     */
    public function del(int $id): void
    {
        unset($this->_storage[$id]);
    }

    /**
     * @param int $id
     * @return mixed|null
     */
    public function get(int $id)
    {
        return $this->exist($id) ? $this->_storage[$id] : null;
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
        return isset($this->_storage[$id]);
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->_storage);
    }
}