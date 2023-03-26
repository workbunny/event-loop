<?php declare(strict_types=1);

namespace WorkBunny\Tests\Benchmarks\Future;

use WorkBunny\Tests\Benchmarks\AbstractBenchmark;

class WhileFuture extends AbstractBenchmark
{
    public function handler(): void
    {
        while (true){
            $this->setCount($this->getCount() + 1);
            if($this->getInitialTime() + 1 <= microtime(true)) {
                break;
            }
        }
    }
}