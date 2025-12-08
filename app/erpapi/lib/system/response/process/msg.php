<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_system_response_process_msg
{
    /**
     * notify
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function notify($sdf)
    {
        if (!$sdf['node_id']) {
            array('rsp' => 'fail', 'msg' => '节点不能为空');
        }
        
        $shopMdl = app::get('ome')->model('shop');
        
        $shop = $shopMdl->dump(array('node_id' => $sdf['node_id']), 'shop_id,addon,name');
        
        if (!$shop) {
            array('rsp' => 'fail', 'msg' => '店铺未绑定');
        }
        
        $rpcNotifyMdl = app::get('base')->model('rpcnotify');
        $sdf['content']['info'] = '【' . $shop['name'] . '】' . $sdf['content']['info'];
        $data         = [
            'callback'   => '',
            'rsp'        => 'succ',
            'msg'        => json_encode($sdf['content'],JSON_UNESCAPED_UNICODE),
            'notifytime' => strtotime($sdf['date'])
        ];
        $rpcNotifyMdl->insert($data);
       
        // 店铺到期主动提醒
        kernel::single('monitor_event_notify')->addNotify('system_message', [
            'errmsg'         => $sdf['content']['info'],
        ]);

        return array('rsp' => 'succ', 'msg' => '消息已接收');
    }
}
