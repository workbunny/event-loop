<?php
declare(strict_types=1);

namespace WorkBunny\EventLoop\Protocols;

use WorkBunny\EventLoop\Loop;
use WorkBunny\EventLoop\Utils\Future;

/**
 * Class AbstractLoop
 * @package WorkBunny\EventLoop\Protocols
 * @author chaz6chez
 */
abstract class AbstractLoop implements LoopInterface
{

    /** @var bool CPU switching */
    protected bool $_switching = true;

    /** @var bool  */
    protected bool $_stopped = true;

    /** @var array All listeners for read event. */
    protected array $_reads = [];

    /** @var array All listeners for write event. */
    protected array $_writes = [];

    /** @var array Event listeners of signal. */
    protected array $_signals = [];

    /** @var Future */
    protected Future $_future;

    /**
     * AbstractLoop constructor.
     */
    public function __construct()
    {
        $this->_switching = Loop::$switching;
        $this->_future = new Future();
    }
}