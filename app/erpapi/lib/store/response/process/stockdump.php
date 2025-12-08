<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_store_response_process_stockdump
{
    

    /**
     * 添加
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function add($params){


        $rs = kernel::single('console_event_receive_transferorder')->ioStorage($params);
        return $rs;

    }


}

?>