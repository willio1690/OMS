<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

#智选物流(快递鸟和菜鸟智选物流)
class erpapi_exrecommend_request_recommend extends erpapi_exrecommend_request_abstract{
    #智选物流请求统一出口
    final protected function requestCall($method,$params,$callback=array(),$orign_params=array())
    {
        if(!$this->title) {
            $this->title = '获取智选物流';
        }
        return $this->__caller->call($method,$params,$callback,$this->title, $this->timeOut, $this->primaryBn);
    }
}