<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_shop_response_params_stock extends erpapi_validate {
    /**
     * 获取
     * @return mixed 返回结果
     */
    public function get(){
       $arr = array(
           'bn' => array(
               'required' => 'true',
               'errmsg' => '货号不能为空'
           )
       );
        return $arr;
    }
}