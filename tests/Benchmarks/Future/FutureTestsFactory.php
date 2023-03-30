<?php declare(strict_types=1);

namespace WorkBunny\Tests\Benchmarks\Future;

use WorkBunny\Tests\Benchmarks\AbstractBenchmark;

final class FutureTestsFactory
{
    /**
     * 测试类
     *
     * @var string[]
     */
    private array $_futureTests = [
//        'while'           => WhileFuture::class,
//        'while-has-sleep' => SleepWhileFuture::class,
//        'native'          => NativeLoopFuture::class,
//        'event'           => EventLoopFuture::class,
//        'ev'              => EvLoopFuture::class,
        'swow'            => SwowLoopFuture::class
    ];

    /**
     * 结果数据
     *
     * @var array[][]
     */
    private array $_result = [
        'schema' => [
            'name_strlen'   => 4,
            'count_strlen'  => 5,
            'memory_strlen' => 6
        ]
    ];

    /**
     * 输出结果
     *
     * @return void
     */
    protected function _resultTable(): void
    {
        $nameStrlenArr = array_column($this->_result, 'name_strlen');
        $countStrlenArr = array_column($this->_result, 'count_strlen');
        $memoryStrlenArr = array_column($this->_result, 'memory_strlen');
        rsort($nameStrlenArr);
        rsort($countStrlenArr);
        rsort($memoryStrlenArr);
        $nameStrlen = 2 + $nameStrlenArr[0];
        $countStrlen = 2 + $countStrlenArr[0];
        $memoryStrlen = 2 + $memoryStrlenArr[0];
        echo ' | Test Result: ' . str_repeat('-', 2 + $nameStrlen + $countStrlen + $memoryStrlen - 14) . '+' . PHP_EOL;
        echo ' |' . str_pad('Name', $nameStrlen, ' ', \STR_PAD_BOTH) .
        '|' . str_pad('Count', $countStrlen, ' ', \STR_PAD_BOTH) .
        '|' . str_pad('Memory', $memoryStrlen, ' ', \STR_PAD_BOTH) .
        '|' . PHP_EOL;
        echo ' |' . str_pad('-', $nameStrlen, '-', \STR_PAD_BOTH) .
            '|' . str_pad('-', $countStrlen, '-', \STR_PAD_BOTH) .
            '|' . str_pad('-', $memoryStrlen, '-', \STR_PAD_BOTH) .
            '|' . PHP_EOL;
        foreach ($this->_result as $key => $item) {
            if($key !== 'schema'){
                echo ' |' . str_pad((string)$key, $nameStrlen, ' ', \STR_PAD_BOTH) .
                    '|' . str_pad((string)$item['count'], $countStrlen, ' ', \STR_PAD_BOTH) .
                    '|' . str_pad("{$item['memory']} B", $memoryStrlen, ' ', \STR_PAD_BOTH) .
                    '|' . PHP_EOL;
            }
        }
        echo ' |' . str_repeat('-', 2 + $nameStrlen + $countStrlen + $memoryStrlen) . '+' . PHP_EOL;
    }

    /**
     * 运行测试
     *
     * @return void
     */
    final public function startTests(): void
    {
        echo 'ℹ️  Wait. ' . PHP_EOL;
        foreach ($this->_futureTests as $name => $futureTest) {
            /** @var AbstractBenchmark $obj */
            $obj = new $futureTest;
            $this->_result[$name]['count'] = $obj->getCount();
            $this->_result[$name]['count_strlen'] = strlen((string)$obj->getCount());
            $this->_result[$name]['memory'] = $obj->getUsedMemory(false);
            $this->_result[$name]['memory_strlen'] = strlen((string)$obj->getUsedMemory(false));
            $this->_result[$name]['name_strlen'] = strlen($name);
        }
        echo chr(27) . "[1A";
        echo '✅  Done! ' . PHP_EOL;
        $this->_resultTable();
    }
}