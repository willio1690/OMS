<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_unionpay_response_params_logistics extends erpapi_unionpay_response_params_abstract {

    protected function push(){
        return array(
            'logi_status' => array(
                'type'=> 'enum',
                'required' => 'true',
                'errmsg' => '只接受已揽收或已签收物流信息',
                'value' => array('1','2','3','4','5','6')
            ),
            'logi_no' => array(
                'required' => 'true',
                'errmsg' => '缺少物流单号不接收！'
            ),
            'delivery_bn' => array(
                'required' => 'true',
                'errmsg' => '缺少发货单号不接收！'
          ),

        );
    }
}