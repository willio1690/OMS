<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 售后换货接口
 *
 * @version 2024.04.11
 */
class erpapi_dealer_response_exchange extends erpapi_dealer_response_abstract
{
    
    /**
     * 添加
     * @param mixed $params 参数
     * @return mixed 返回值
     */

    public function add($params){
        $original_bn = $params['dispute_id'];

        $this->__apilog['result']['msg'] = '一键代发不支持换货';
        return false;
        
       
    }


    
}
