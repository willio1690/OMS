<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

/**
 * 库存冻结异常
 *
 */
namespace Shopex\OMS\ome\exception;

class BranchStoreFreezeException extends \Exception
{
    protected $additionalInfo;

    /**
     * __construct
     * @param mixed $message message
     * @param mixed $code code
     * @param Exception $previous previous
     * @param mixed $additionalInfo additionalInfo
     * @return mixed 返回值
     */
    public function __construct($message, $code = 0, \Exception $previous = null, $additionalInfo = [])
    {
        parent::__construct($message, $code, $previous);

        $this->additionalInfo = $additionalInfo;
    }

    /**
     * 获取AdditionalInfo
     * @return mixed 返回结果
     */
    public function getAdditionalInfo()
    {
        return $this->additionalInfo;
    }

    public function __toString(): string
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n"
             . ($this->additionalInfo ? "\nAdditional Info: " . json_encode($this->additionalInfo, JSON_UNESCAPED_UNICODE) : '');
    }
}
