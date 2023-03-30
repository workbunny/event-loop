<?php
declare(strict_types=1);

namespace WorkBunny\Tests;

use WorkBunny\Tests\Events\SignalsTest;
use WorkBunny\Tests\Events\StreamsTest;
use WorkBunny\Tests\Events\TimerTest;

abstract class AbstractLoopTest extends AbstractTest
{
    use TimerTest;
    use StreamsTest;
    use SignalsTest;
}
