<?php
declare(strict_types=1);

namespace WorkBunny\EventLoop;

final class Storage
{
    /** @var int  */
    private int $_count = 0;

    /** @var array storage */
    private array $_storage = [];

    /**
     * @param string $key
     * @param mixed|null $value
     * @return string
     */
    public function add(string $key, $value): string
    {
        $this->_storage[$key] = $value;
        $this->_count ++;
        return $key;
    }

    /**
     * @param string $key
     * @param mixed|null $value
     * @return string
     */
    public function set(string $key, $value): string
    {
        if($this->exist($key)){
            $this->_storage[$key] = $value;
        }
        return $key;
    }

    /**
     * @param string $key
     */
    public function del(string $key): void
    {
        unset($this->_storage[$key]);
        $this->_count --;
    }

    /**
     * @param string $key
     * @return mixed|null
     */
    public function get(string $key)
    {
        return $this->exist($key) ? $this->_storage[$key] : null;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return $this->_count;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function exist(string $key): bool
    {
        return isset($this->_storage[$key]);
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->_storage);
    }
}