<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_system_response_msg extends erpapi_system_response_abstract
{
    /**
     * 消息类通知
     *
     * @return void
     * @author 
     */
    public function notify($params)
    {
        $this->__apilog['title']       = '系统消息类通知';
        $this->__apilog['original_bn'] = $params['to_node_id'];

        if (!$params['data']) {
            $this->__apilog['result']['msg'] = '消息内容不能为空';
            return false;
        }

        $content = json_decode($params['data'],true);
        $content['node_id'] = $params['to_node_id'];
        $sdf = array(
            'node_id'    => $params['node_id'],
            'date'       => $params['date'],
            'content'    => $content,
        );

        return $sdf;
    }
}
