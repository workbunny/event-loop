<?php
declare(strict_types=1);

namespace WorkBunny\Test;

use WorkBunny\Test\Events\SignalsTest;
use WorkBunny\Test\Events\StreamsTest;
use WorkBunny\Test\Events\TimerTest;

abstract class AbstractLoopTest extends AbstractTest
{
    use TimerTest;
    use StreamsTest;
    use SignalsTest;

    public function provider(): array
    {
        return [
            [true],
            [false]
        ];
    }


    /**
     * 读流先于timer触发
     * @dataProvider provider
     * @param bool $bio
     * @return void
     */
    public function testReadStreamBeforeTimer(bool $bio)
    {
        list ($input, $output) = $this->createSocketPair();
        stream_set_blocking($input, $bio);
        stream_set_blocking($output, $bio);

        fwrite($output, 'foo' . PHP_EOL);

        $string = '';

        $this->getLoop()->addTimer(0.0,0.0, function () use (&$string){
            $string .= 'timer' . PHP_EOL;
        });

        $this->getLoop()->addReadStream($input, function() use(&$string){
            $string .= 'read' . PHP_EOL;
        });

        $this->tickLoop();

        $this->assertEquals('read' . PHP_EOL . 'timer' . PHP_EOL, $string);
    }

    /**
     * 写流先于timer触发
     * @dataProvider provider
     * @param bool $bio
     * @return void
     */
    public function testWriteStreamBeforeTimer(bool $bio)
    {
        list ($input, $output) = $this->createSocketPair();
        stream_set_blocking($output, $bio);
        fwrite($output, 'foo' . PHP_EOL);

        $string = '';

        $this->getLoop()->addTimer(0.0,0.0, function () use (&$string){
            $string .= 'timer' . PHP_EOL;
        });

        $this->getLoop()->addWriteStream($output, function() use(&$string){
            $string .= 'write' . PHP_EOL;
        });

        $this->tickLoop();

        $this->assertEquals('write' . PHP_EOL . 'timer' . PHP_EOL, $string);
    }
}
