<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_store_response_process_organization
{
    /**
     * listing
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function listing($params){


        $data = kernel::single('openapi_data_original_organization')->getList($params['filter'],$params['offset'],$params['limit']);
       
        $rs = array('rsp'=>'succ','data'=>$data);
        return $rs;

    }

   
}

?>