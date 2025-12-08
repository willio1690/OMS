<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_order_refund_status_api extends ome_order_refund_status_abstract {

    /**
     * fetch
     * @param mixed $tid ID
     * @param mixed $nodeId ID
     * @param mixed $shopId ID
     * @return mixed 返回值
     */
    public function fetch($tid, $nodeId, $shopId){
        $rs = kernel::single('erpapi_router_request')->set('shop',$shopId)->finance_getNotifyOid(['order_bn'=>$tid]);
        if($rs['rsp'] == 'fail') {
            return [false, ['msg'=>'接口失败：'.$rs['msg']]];
        }
        return [true, ['data'=>$rs['data']]];
    }

    /**
     * store
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function store($sdf) {
        return [false, ['msg'=>'接口方式，无法写入数据']];
    }
}