<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @author ykm 2016/6/17
 * @describe 获取模板数据
 */

class erpapi_logistics_request_template extends erpapi_logistics_request_abstract {
    protected $title = '获取打印模板';
    protected $timeOut = 10;
    protected $primaryBn = '';

    #模板获取同一接口
    final protected function requestCall($method,$params,$callback=array())
    {
        return $this->__caller->call($method,$params,$callback,$this->title, $this->timeOut, $this->primaryBn);
    }
}
