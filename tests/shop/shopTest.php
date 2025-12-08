<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class shopTest extends TestCase
{
    // 在每个测试方法之前执行
    protected function setUp(): void
    {
        parent::setUp();

        // 在 setUp 方法中引入依赖文件
        include_once 'config/config.php';
        include_once 'app/base/define.php';
        include_once 'app/base/kernel.php';
    }

    public function testEquality(): void
    {
        $this->expectOutputString('foo');

        print 'foo';
    }
}