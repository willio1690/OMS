<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class wap_delivery_process{

    /**
     * 确认 接单
     * 
     * @param Array $params
     * @return boolean
     */
    function accept($params){
        
        //确认接收该门店门店自提单或门店配送
        $deliveryObj    = app::get('wap')->model('delivery');
        $update_data    = array('delivery_id'=>$params['delivery_id'], 'confirm'=>1, 'last_modified'=>time());
        $deliveryObj->save($update_data);
        
        //门店仓不需要wms_id直接走门店绑定的服务端去识别接口
        $store_id      = kernel::single('ome_branch')->isStoreBranch($params['branch_id']);
        $channel_id    = $store_id;

        #获取发货仓库对应的门店店铺信息
        $wapDeliveryLib    = kernel::single('wap_delivery');
        $branchShopInfo    = $wapDeliveryLib->getBranchShopInfo($params['branch_id']);
        
        //获取是门店自提还是门店配送
        $corpTypeLib = kernel::single('o2o_corp_type');
        $dlyCorpInfo = $corpTypeLib->get_corp_type($params["logi_id"], true);
        
        $memo    = '门店('. $branchShopInfo['store_name'] .')已接单，';
        if($dlyCorpInfo["type"] == 'o2o_pickup')
        {
            $memo .= '请到门店自提';
        }
        else
        {
            $memo .= '请耐心等待门店为您配送';
        }

        //订单确认参数组织
        $request_params    = array(
            'delivery_bn' => $params['outer_delivery_bn'],
            'memo' => $memo,
        );
        
        $res    = kernel::single('wap_event_trigger_delivery')->confirm($channel_id, $request_params, true);
        return true;
    }

    //门店自提拒绝
    function refuse($params){

        $deliveryObj    = app::get('wap')->model('delivery');

        //门店自提单拒绝打回
        $update_data    = array('confirm'=>'2', 'status'=>'1', 'last_modified'=>time());
        $rs = $deliveryObj->update($update_data, ['delivery_id'=>$params['delivery_id'],'confirm'=>'3', 'status'=>'0']);
        if(is_bool($rs)) {
            return false;
        }

        //门店信息
        $wapDeliveryLib    = kernel::single('wap_delivery');
        $branchShopInfo    = $wapDeliveryLib->getBranchShopInfo($params['branch_id']);

        //门店仓不需要wms_id直接走门店绑定的服务端去识别接口
        $store_id      = kernel::single('ome_branch')->isStoreBranch($params['branch_id']);
        $channel_id    = $store_id;

        //获取门店拒绝的原因
        $reasonObj    = app::get('o2o')->model('refuse_reason');
        $reasonInfo         = $reasonObj->dump(array('reason_id'=>$params['reason_id']), '*');
        $memo = $params['memo'] ? $params['memo'] : $reasonInfo['reason_name'];

        $request_params = array(
            'delivery_bn' => $params['outer_delivery_bn'],
            'store_bn' => $branchShopInfo['store_bn'],
            'store_name' => $branchShopInfo['store_name'],
            'reason_id' => $params['reason_id'],
            'memo' => $memo,
        );

        $res = kernel::single('wap_event_trigger_delivery')->reback($channel_id, $request_params, true);
        return true;
    }

    //重发提货校验码短信
    function reSendMsg($params){

        $deliveryObj    = app::get('wap')->model('delivery');

        //门店仓不需要wms_id直接走门店绑定的服务端去识别接口
        $store_id      = kernel::single('ome_branch')->isStoreBranch($params['branch_id']);
        $channel_id    = $store_id;

        #获取发货仓库对应的门店店铺信息
        $wapDeliveryLib    = kernel::single('wap_delivery');
        $branchShopInfo    = $wapDeliveryLib->getBranchShopInfo($params['branch_id']);
        
        //获取是门店自提还是门店配送
        $corpTypeLib = kernel::single('o2o_corp_type');
        $dlyCorpInfo = $corpTypeLib->get_corp_type($params["logi_id"],true);

        //判断是否开启销单校验码 关闭状态下$pickup_code不生成不赋值
        if(app::get('o2o')->getConf('o2o.delivery.confirm.code') == "true"){
            #生成六位提货单的校验码
            $wapCodeLib = kernel::single('wap_code');
            //作废老的校验码
            $wapCodeLib->del_code($params['delivery_bn']);
            //生成六位提货单的校验码
            $pickup_code = $wapCodeLib->gen_code($params['delivery_bn']);
        }
        
        //订单确认参数组织
        $request_params    = array(
            'dly_corp_type' => $dlyCorpInfo["type"],
            'delivery_bn' => $params['outer_delivery_bn'],
            'pickup_bn' => $params['delivery_bn'],
            'pickup_code' => $pickup_code,
            'ship_mobile' => $params['consignee']['mobile'],
            'ship_name' => $params['consignee']['name'],
            'store_name' => $branchShopInfo['store_name'],#门店名称
            'store_contact_tel' => $branchShopInfo['tel'] ? $branchShopInfo['tel'] : $branchShopInfo['mobile'],#门店联系电话
            'sms_flag' => 'reSendMsg',#重发短信标识
        );

        //[自提]需要门店地址
        if($dlyCorpInfo["type"] == 'o2o_pickup'){
            $request_params["store_addr"] = $branchShopInfo['addr'];
        }

        
        /* 重新发送短信给收货人 */
        $logstr    = '';
        
        //门店自提单发送短信
        if($request_params['dly_corp_type'] == 'o2o_pickup')
        {
            $sendArr = array(
                    'event_type' => 'o2opickup',
                    'ship_mobile' => $request_params['ship_mobile'],
                    'ship_name' => $request_params['ship_name'],
                    'pickup_bn' => $request_params['pickup_bn'],#提货单
                    'pickup_code' => $request_params['pickup_code'],#校验码
                    'store_name' => $request_params['store_name'],#门店名称
                    'store_addr' => $request_params['store_addr'],#门店地址
                    'store_contact_tel' => $request_params['store_contact_tel'],#门店联系方式
            );
            
            $logstr = '重新发短信生成提货码,请到('.$request_params['store_name'].')门店自提;';
        }
        elseif($request_params['dly_corp_type'] == 'o2o_ship')
        {
            $sendArr = array(
                    'event_type' => 'o2oship',
                    'ship_mobile' => $request_params['ship_mobile'],
                    'ship_name' => $request_params['ship_name'],
                    'pickup_bn' => $request_params['pickup_bn'],#提货单
                    'pickup_code' => $request_params['pickup_code'],#校验码
                    'store_name' => $request_params['store_name'],#门店名称
                    'store_contact_tel' => $request_params['store_contact_tel'],#门店联系方式
            );
            
            $logstr = '重新发短信生成校验码,请耐心等待门店('.$request_params['store_name'].')为您配送;';
        }
        
        $sendSms    = kernel::single('taoexlib_sms')->sendSms($sendArr, $error_msg);
        if(!$sendSms)
        {
            $logstr = $logstr."短信发送失败(". $error_msg .")";
        }
        
        /* ome订单操作日志 */
        $deliveryOrderObj   = app::get('ome')->model('delivery_order');
        $deliveryObj        = app::get('ome')->model('delivery');
        
        $delivery_info    = $deliveryObj->dump(array('delivery_bn'=>$request_params['delivery_bn']), 'status, delivery_id');
        $order_ids        = $deliveryOrderObj->getList('order_id', array('delivery_id'=>$delivery_info['delivery_id']), 0, -1);
        
        #订单记录日志
        $opObj    = app::get('ome')->model('operation_log');
        foreach ($order_ids as $row)
        {
            $opObj->write_log('order_modify@ome', $row['order_id'], $logstr);
        }
        
        return true;
    }
    
    /**
     * 确认发货
     */
    function consign($params)
    {
        $deliveryObj    = app::get('wap')->model('delivery');
        
        #wap发货单更新
        $dlydata    = array();
        $delivery_time    = time();
        
        $dlydata['status'] = 3;
        $dlydata['process_status'] = 7;
        $dlydata['last_modified'] = $delivery_time;
        $dlydata['delivery_time'] = $delivery_time;
        if($params['retry_sync']) {
            $result = 1;
        } else {
            $result    = $deliveryObj->update($dlydata, ['delivery_id'=>$params['delivery_id'], 'status'=>'0', 'confirm'=>'1']);
        }
        
        if(is_bool($result))
        {
            return false;
        }
        
        //门店仓不需要wms_id直接走门店绑定的服务端去识别接口
        $store_id      = kernel::single('ome_branch')->isStoreBranch($params['branch_id']);
        $channel_id    = $store_id;
        
        #获取发货仓库对应的门店店铺信息
        $wapDeliveryLib    = kernel::single('wap_delivery');
        $branchShopInfo    = $wapDeliveryLib->getBranchShopInfo($params['branch_id']);
        
        //获取是门店自提还是门店配送
        $corpTypeLib = kernel::single('o2o_corp_type');
        $dlyCorpInfo = $corpTypeLib->get_corp_type($params["logi_id"], true);
        
        //判断是否开启销单校验码 关闭状态下$pickup_code不生成不赋值
        if(app::get('o2o')->getConf('o2o.delivery.confirm.code') == "true"){
            #生成六位提货单的校验码
            $wapCodeLib = kernel::single('wap_code');
            $pickup_code = $wapCodeLib->gen_code($params['delivery_bn']);
        }
        
        //订单确认参数组织 门店配送短信模板只需要ship_mobile ship_name store_name store_contact_tel
        $request_params = array(
                'delivery_bn' => $params['outer_delivery_bn'],
                'delivery_time' => $delivery_time,
                'weight' => $params['weight'],
                'delivery_cost_actual' => $params['delivery_cost_actual'],
                'logi_id' => $params['logi_id'],
                'logi_no' => $params['logi_no'] ? : $params['outer_delivery_bn'],
                'dly_corp_type' => $dlyCorpInfo["type"],
                'pickup_bn' => $params['delivery_bn'],
                'pickup_code' => $pickup_code,
                'ship_mobile' => $params['consignee']['mobile'],
                'ship_name' => $params['consignee']['name'],
                'store_name' => $branchShopInfo['store_name'],#门店名称
                'store_contact_tel' => $branchShopInfo['tel'] ? $branchShopInfo['tel'] : $branchShopInfo['mobile'],#门店联系电话
        );
        
        //[自提]需要门店地址
        if($dlyCorpInfo["type"] == "o2o_pickup")
        {
            $request_params["store_addr"] = $branchShopInfo['addr'];//自提短信需要门店地址
        }
        
        $res    = kernel::single('wap_event_trigger_delivery')->consign($channel_id, $request_params, true);
        
        return true;
    }
    
    /**
     * 确认签收
     */
    function sign($params)
    {
        $deliveryObj    = app::get('wap')->model('delivery');
        
        #更新校验码状态
        $wapDeliveryCodeObj    = app::get('wap')->model('delivery_code');
        $wapDeliveryCodeObj->update(array('status'=>1), array('delivery_bn'=>$params['delivery_bn']));
        
        #wap发货单更新为已签收
        $dlydata    = array();
        
        $dlydata['delivery_id']    = $params['delivery_id'];
        $dlydata['is_received']    = 2;
        $dlydata['last_modified']  = time();
      
        $result    = $deliveryObj->save($dlydata);
        if(!$result)
        {
            return false;
        }
        
        #获取发货仓库对应的门店店铺信息
        $wapDeliveryLib    = kernel::single('wap_delivery');
        $branchShopInfo    = $wapDeliveryLib->getBranchShopInfo($params['branch_id']);
        
        //获取是门店自提还是门店配送
        $corpTypeLib = kernel::single('o2o_corp_type');
        $dlyCorpInfo = $corpTypeLib->get_corp_type($params['logi_id'], true);
        
        //门店仓不需要wms_id直接走门店绑定的服务端去识别接口
        $store_id      = kernel::single('ome_branch')->isStoreBranch($params['branch_id']);
        $channel_id    = $store_id;

        if($dlyCorpInfo["type"] == 'o2o_pickup')
        {
            $memo = '顾客已提货';
        }
        else
        {
            $memo = '顾客已签收';
        }

        $request_params = array(
            'delivery_bn' => $params['outer_delivery_bn'],
            'sign_time'=>date('Y-m-d H:i:s'),
            'memo' => $memo,
            
        );
        $res    = kernel::single('wap_event_trigger_delivery')->sign($channel_id, $request_params, true);
        
        return true;
    }
}
