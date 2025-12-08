<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2022/9/30 10:21:09
 * @describe: tmc消息通知
 * ============================
 */
class erpapi_shop_response_tmcnotify extends erpapi_shop_response_abstract {

    /**
     * refund
     * @param mixed $params 参数
     * @return mixed 返回值
     */

    public function refund($params){
        $params = json_decode($params['content'], 1);
        $this->__apilog['title'] = '退款消息通知';
        $this->__apilog['original_bn'] = $params['tid'];
        if(!defined('TMC_REFUND_WRITE_MODE')) {
            $this->__apilog['result']['msg'] = '未配置消息接收';
            return false;
        }
        $sdf = [
            'tid' => $params['tid'],
            'oid' => $params['oid'],
            'buyer_nick' => $params['buyer_nick'],
            'buyer_open_uid' => $params['buyer_open_uid'],
            'seller_nick' => $params['seller_nick'],
            'refund_phase' => $params['refund_phase'],
            'modified' => $params['modified'],
            'bill_type' => $params['bill_type'],
            'refund_id' => $params['refund_id'],
            'shop_id' => $this->__channelObj->channel['shop_id'],
            'node_id' => $this->__channelObj->channel['node_id'],
        ];
        return $sdf;
    }
    
}
