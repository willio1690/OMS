<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/*
 * 自动取消过期未支付且未确认的订单(除货到付款)
 */
class ome_sync_order{

    function cancel_order(){

        $time = time();
        /*排除shopex内部系统的前端店铺 --begin--*/
        //$remove_shopex = '';
        $shopex_shop_list = ome_shop_type::shopex_shop_type();
        //$remove_shopex = ' AND shop_type NO IN('.implode(',', $shopex_shop_list).')';
        /*排除shopex内部系统的前端店铺 --end--*/
        $oQueue = app::get('base')->model('queue');
        
        //加入复审、跨境申报订单失效
        $selectWhere = "SELECT count(*)";
        $sql0 = " FROM `sdb_ome_orders` WHERE order_limit_time is NOT NULL AND order_limit_time<'".$time."' AND pay_status='0' AND process_status in('unconfirmed', 'is_retrial', 'is_declare') AND is_cod='false' AND shop_type IN('".implode("','", $shopex_shop_list)."')";
        $sql = $selectWhere.$sql0;
        $count = kernel::database()->count($sql);
        $page = 1;
        $limit = 10;
        $pagecount = ceil($count/$limit);
        for ($i=0;$i<$pagecount;$i++){
            $lim = ($page+$i-1)*$limit;
            $sql = " SELECT order_id ".$sql0." LIMIT ".$lim.",".$limit;
            $data = kernel::database()->select($sql);
            if ($data){
                $sdfdata['order_id'] = array();
                foreach ($data as $k=>$v){
                    $sdfdata['order_id'][] = $v['order_id'];
                }
                $queueData = array(
                    'queue_title'=>'自动取消过期订单队列'.$page.'(共'.count($sdfdata['order_id']).'个)',
                    'start_time'=>$time,
                    'params'=>array(
                        'sdfdata'=>$sdfdata['order_id'],
                        'app' => 'ome',
                        'mdl' => 'order'
                    ),
                    'status' => 'hibernate',
                    'worker'=> 'ome_order_to_api.run',
                   );
                $oQueue->save($queueData);

                $log = app::get('ome')->model('api_log');
                $log->write_log($log->gen_id(), '自动取消订单同步', __CLASS__, __METHOD__, '', '', 'response', 'success', var_export($queueData, true));
            }
        }
    }
    
    /**
     * 自动重试京东工小达平台承运商履约信息查询
     * @return array
     * @date 2025-03-14 5:43 下午
     */
    public function getCarrier()
    {
        $time = time() - 86400 * 7;
        
        $selectWhere = "SELECT count(*)";
        $sql0        = " FROM `sdb_ome_orders` WHERE createtime >'" . $time . "' AND pay_status='1' AND process_status in('unconfirmed') AND is_cod='false' AND shop_type = 'jd' AND is_delivery = 'N'";
        $sql         = $selectWhere . $sql0;
        $count       = kernel::database()->count($sql);
        $page        = 1;
        $limit       = 10;
        $pagecount   = ceil($count / $limit);
        for ($i = 0; $i < $pagecount; $i++) {
            $lim  = ($page + $i - 1) * $limit;
            $sql  = " SELECT order_id,shop_id,order_bn " . $sql0 . " LIMIT " . $lim . "," . $limit;
            $data = kernel::database()->select($sql);
            if ($data) {
                foreach ($data as $k => $v) {
                    $isGxd = kernel::single('ome_bill_label')->getBillLabelInfo($v['order_id'], 'order', kernel::single('ome_bill_label')->isSomsGxd());
                    if ($isGxd) {
                        $gxdSdf = [
                            'order_id'       => $v['order_id'],
                            'shop_id'        => $v['shop_id'],
                            'order_bn'       => $v['order_bn'],
                            'shippingMethod' => 0,//发货方式 1：平台结算 2：自行结算 0：平台+自行结算 默认1
                        ];
                        list($res, $msg) = kernel::single('ome_event_trigger_shop_logistics')->getCarrierPlatform($gxdSdf);
                        if ($res) {
                            app::get('ome')->model('orders')->update(['is_delivery' => 'Y'], ['order_id' => $v['order_id'], 'is_delivery' => 'N', 'process_status' => 'unconfirmed']);
                        }
                        $log = app::get('ome')->model('api_log');
                        $log->write_log($log->gen_id(), '自动重试京东工小达平台承运商履约信息查询', __CLASS__, __METHOD__, '', '', 'response', 'success', var_export($gxdSdf, true));
                        
                    }
                    
                }
            }
        }
        return [true,'成功'];
    }

}