<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_event_trigger_shop_logistics
{
    //同步商店地址
    public function searchAddress($rdef,$shop_id){
        kernel::single('erpapi_router_request')->set('shop',$shop_id)->logistics_searchAddress($rdef);
    }
    public function syncOrderRule($shop_id,$is_open_cnauto='false'){
        $result = app::get('ome')->model('shop')->getList('addon',array('shop_id'=>$shop_id));
        if(empty($result[0]['addon']['nickname'])){
            return false;
        }
        #是否合单
        $confirmCombine = app::get('ome')->getConf('ome.confirm.combine');
        if($confirmCombine !='true'){
            $confirmCombine = 'false';
        }
        $data = array(
            'shop_code'=>$result[0]['addon']['nickname'],#店铺nick  测试：yanqiu02
            'is_open_cnauto'=>$is_open_cnauto,#是否开启菜鸟自动流转规则
            'is_auto_check'=>'true',#是否系统智能处理订单（无人工介入）,写死为TRUE
            'check_rule_msg'=>'有留言;有赠品;检查库存;开发票;物流匹配',#人工审单规则描述
            'is_sys_merge_order'=>$confirmCombine,#是否开启了订单合单
            'merge_order_cycle'=>'',#订单合单时长（单位：分钟）
            'other_rule'=>''#其他未定义订单处理规则，格式｛name;stauts;cycle;｝
        );
        kernel::single('erpapi_router_request')->set('shop',$shop_id)->logistics_syncOrderRule($data);
    }

    //直邮获取资源列表
    public function sync_crossbordercorp($shop_id){
        $data['region_id'] = '228';# 发货地区域id
        #这是随意写写死的地址
        $data['to_address'] =  array(
           'country'=>'中国',
           'province'=>'上海',
           'city '=>'上海市',
           'county'=>'徐汇区',
           'detail_address'=>''
        );
        kernel::single('erpapi_router_request')->set('shop',$shop_id)->logistics_crossbordercorp($data);
    }

    //获取推荐物流
    public function getRecommend($order) {
        $orderId = $order['order_id'];
        $shopId = $order['shop_id'];
        $createway = $order['createway'];
        if(empty($orderId) || empty($shopId) || $createway != 'matrix') {
            return ['rs'=>true, 'msg'=>'无需获取推荐物流', 'logistics_code'=>[]];
        }
        $data = app::get('ome')->model('order_receiver')->db_dump(['order_id'=>$orderId], 'order_id,platform_province_id,platform_city_id');
        $data['order_bn'] = $order['order_bn'];
        $rs = kernel::single('erpapi_router_request')->set('shop',$shopId)->logistics_getRecommend($data);
        if($rs['rsp'] == 'fail') {
            return ['rs'=>false, 'msg'=>$rs['msg']];
        }
        return ['rs'=>true, 'msg'=>$rs['msg'], 'logistics_code'=>($rs['data']['code'] ? : []), 'logistics_name'=>($rs['data']['name'] ? : [])];
    }

    //判断推荐物流
    public function judgeRecommend($order, $corpType) {
        if(empty($corpType)) {
            return [true, '没有物流编码'];
        }
        $rs = $this->getRecommend($order);
        if($rs['rs']) {
            if(empty($rs['logistics_code'])) {
                return [true, $rs['msg']];
            }
            if(in_array($corpType, $rs['logistics_code'])) {
                return [true, $rs['msg']];
            }
            $rs['msg'] = '建议使用可发货物流：'.implode('、', $rs['logistics_name']);
        }
        return [false, $rs['msg']];
    }
    
    /**
     * 平台承运商履约信息查询接口
     * @param $params
     * @return array
     * @date 2025-03-12 5:23 下午
     */
    public function getCarrierPlatform($orderIds)
    {
        $orderMdl       = app::get('ome')->model('orders');
        $orderExtendMdl = app::get('ome')->model('order_extend');
        $orderList      = $orderMdl->getList('order_id,order_bn,shop_id,createtime', ['order_id' => $orderIds]);
        foreach ($orderList as $order) {
            $order_id = $order['order_id'];
            $shop_id  = $order['shop_id'];
            $order_bn = $order['order_bn'];
            $gxdSdf   = [
                'order_bn'       => $order_bn,
                'shippingMethod' => 0,//发货方式 1：平台结算 2：自行结算 0：平台+自行结算 不传默认1
            ];
            $res      = kernel::single('erpapi_router_request')->set('shop', $shop_id)->logistics_getCarrierPlatform($gxdSdf);
            if ($res['rsp'] != 'succ') {
                //获取失败，暂停订单处理
                $orderMdl->update(['is_delivery' => 'N'], ['order_id' => $order_id, 'is_delivery' => 'Y', 'process_status' => 'unconfirmed']);
                $error_msg = $res['err_msg'] ? $res['err_msg'] : $res['msg'];
                
                //每10分钟检查并重试最近1小时内的单据
                if ((time() - $order['createtime']) > 3600) {
                    $time = time() + 600;
                    app::get('ome')->model('operation_log')->write_log('order_edit@ome', $order_id, "平台承运商履约信息获取失败，重新请求记录写入成功:" . date('Y-m-d H:i:s', $time));
                    $task = array(
                        'obj_id'    => $order_id,
                        'obj_type'  => 'timing_carrier_platform',
                        'exec_time' => $time,
                    );
                    app::get('ome')->model('misc_task')->saveMiscTask($task);
                }
                
                return [false, $error_msg];
            }
            
            $resultData = $res['data']['result'];
            
            $shippingMethod   = [];
            $label_value      = 0;
            $bizDeliveryCode  = [];//建议快递名单
            $whiteDeliveryCps = [];//快递白名单
            
            
            //物流白名单保存在sdb_ome_order_extend.white_delivery_cps
            //推荐承运商编码
            if ($resultData['carrierCode']) {
                $bizDeliveryCode[] = $resultData['carrierCode'];
            }
            
            //自行结算
            if (isset($resultData['selfSettlePerformanceInfoMap']) && $resultData['selfSettlePerformanceInfoMap']) {
                $selfSettlePerformanceInfoMap = $resultData['selfSettlePerformanceInfoMap'];
                foreach ($selfSettlePerformanceInfoMap as $carrierCode => $val) {
                    if ($val['ability'] == true) {
                        $whiteDeliveryCps[] = $carrierCode;
                        $shippingMethod[]   = '自行结算';
                        $label_value        = 2;
                    }
                }
            }
            
            
            //平台结算
            if (isset($resultData['carrierPerformanceInfoMap']) && $resultData['carrierPerformanceInfoMap']) {
                $carrierPerformanceInfoMap = $resultData['carrierPerformanceInfoMap'];
                foreach ($carrierPerformanceInfoMap as $carrierCode => $val) {
                    if ($val['ability'] == true) {
                        $whiteDeliveryCps[] = $carrierCode;
                        $shippingMethod[]   = '平台结算';
                        $label_value        = 1;
                    }
                }
            }
            
            
            if ($bizDeliveryCode || $whiteDeliveryCps) {
                $extendInfo = $orderExtendMdl->db_dump(array('order_id' => $order_id), 'order_id,biz_delivery_code,white_delivery_cps');
                if ($extendInfo) {
                    $biz      = @json_decode($extendInfo['biz_delivery_code'], true);
                    $white    = @json_decode($extendInfo['white_delivery_cps'], true);
                    $newBiz   = array_merge((array)$biz, $bizDeliveryCode);
                    $newWhite = array_merge((array)$white, $whiteDeliveryCps);
                    
                    $logisticsInfos = [
                        'biz_delivery_code'  => json_encode($newBiz),
                        'white_delivery_cps' => json_encode($newWhite),
                    ];
                    
                    $orderExtendMdl->update($logisticsInfos, ['order_id' => $order_id]);
                }
            }
            
            //并在标记“工小达”上添加平台结算/自行结算sdb_ome_bill_label.extend_info
            $labelLib = kernel::single('ome_bill_label');
            $labelLib->markBillLabel($order_id, '', 'SOMS_GXD', 'order', $err, $label_value, implode(' ', $shippingMethod));
        }
        
        return [true, '成功'];
    }
}