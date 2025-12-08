<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
abstract class erpapi_dealer_response_components_order_abstract
{
    protected $_platform = null;
    
    /**
     * convert
     * @return mixed 返回值
     */
    public function convert(){}
    
    /**
     * 更新
     * @return mixed 返回值
     */
    public function update(){}

    /**
     * 平台
     *
     * @return object
     **/
    public function setPlatform($platform)
    {
        $this->_platform = $platform;

        return $this;
    }

    /**
     * 比较数组值
     *
     * @return int
     **/
    public function comp_array_value($a,$b)
    {
        if ($a == $b) {
            return 0;
        }

        return $a > $b ? 1 : -1 ;
    }

    /**
     * 过滤空
     *
     * @return string
     **/
    public function filter_null($var)
    {
        return !is_null($var) && $var !== '';
    }
}