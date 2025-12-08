<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @author ykm 2019/4/22
 * @describe 待寻仓订单数据验证
 */

class erpapi_shop_response_params_branch extends erpapi_validate {

    protected function wait() {
        $params = array(
            'available_warehouses' => array('type'=>'string','required'=>'true','errmsg'=>'可用仓库必填'),
            'items' => array('type'=>'array','required'=>'true','errmsg'=>'明细必填'),
        );
        return $params;
    }
}