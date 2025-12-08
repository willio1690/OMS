<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_ticket_openapi_feisuo_response_order extends erpapi_ticket_response_order
{    

    /**
     * 添加
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function add($params)
    {

        $this->__apilog['title']       = $this->__channelObj->channel['channel_name'].'补发单创建';
        $this->__apilog['original_bn'] = $params['tid'];
 
        $this->__apilog['result']['msg'] = '暂未实现';

        return false;
    }
}
