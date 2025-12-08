<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2022/9/30 10:30:34
 * @describe: tmc 消息通知
 * ============================
 */
class erpapi_shop_response_process_tmcnotify {
    
    /**
     * refund
     * @param mixed $params 参数
     * @return mixed 返回值
     */

    public function refund($params) {
        list($rs, $rsData) = kernel::single('ome_order_refund_status')->store($params);
        if(!$rs) {
            return array('rsp'=>'fail', 'msg'=>$rsData['msg']);
        }
        return array('rsp'=>'succ', 'msg'=>$rsData['msg'] ? :'消息写入成功');
    }
}