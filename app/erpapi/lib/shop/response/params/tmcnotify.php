<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0 
 * @DateTime: 2022/9/30 10:28:55
 * @describe: 类
 * ============================
 */

class erpapi_shop_response_params_tmcnotify extends erpapi_shop_response_params_abstract{

    protected function refund(){
        return array(
            'tid' => array(
                'required' => 'true',
                'errmsg' => '缺少单号'
            ),
        );
    }
}