<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_wms_openapi_publicwms_config extends erpapi_wms_openapi_config
{
   
    /**
     * 定义应用参数
     *
     * @return void
     * @author 
     **/
    public function define_query_params(){
        $params  = array( 
            'label'=>'标准',
            'desc'=>'desc',
             'params' => array(
                 //'appkey' =>'appkey',
                // 'owner' => 'owner'
            ),
        );
        return $params;
    }

    

    
}