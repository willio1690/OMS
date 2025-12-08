<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 订单相关业务
 *
 * @author wangbiao@shopex.cn
 * @version $Id: Z
 */
class erpapi_wms_matrix_yjdf_request_order extends erpapi_wms_request_order
{
    /**
     * 推送云交易离线对账
     * 
     * @param array $sdf
     * @return array
     */

    public function order_pushTradeBill($sdf)
    {
        $deliveryObj = app::get('ome')->model('delivery');
        
        $order_id = $sdf['order_id'];
        $order_bn = $sdf['order_bn'];
        $pay_time = date('Y-m-d H:i:s', $sdf['paytime']);
        $out_shop_id = $sdf['out_shop_id'];
        $source_type = ($sdf['source_type'] ? $sdf['source_type'] : '2'); //数据来源，目前仅有抖音店铺，默认为2
        
        if(empty($order_id) || empty($order_bn)){
            return $this->error('请求参数没有传订单信息。');
        }
        
        $log_title = '推送云交易离线对账';
        $gateway = ''; //判断是否加密
        
        //查询订单关联的发货单
        $dlySql = "SELECT b.delivery_id,b.delivery_bn,b.branch_id,b.wms_channel_id FROM sdb_ome_delivery_order AS a LEFT JOIN sdb_ome_delivery AS b ON a.delivery_id=b.delivery_id ";
        $dlySql .= " WHERE a.order_id=". $order_id; //AND b.status IN('succ','return_back')
        $deliveryList = $deliveryObj->db->select($dlySql);
        if(empty($deliveryList)){
            return $this->error('订单号['. $order_bn .']没有发货单。');
        }
        
        $deliveryList = array_column($deliveryList, null, 'delivery_id');
        
        //查询发货单关联京东订单号
        $packSql = "SELECT * FROM sdb_ome_delivery_package WHERE delivery_id IN(". implode(',', array_keys($deliveryList)).")";
        $packageList = $deliveryObj->db->select($packSql);
        if(empty($packageList)){
            return $this->error('订单号['. $order_bn .']没有关联的京东订单号。');
        }
        
        //平台订单号过滤掉A字母
        $platform_order_bn = str_replace('A', '', $order_bn);
        
        //delivery
        $result = array();
        foreach ($packageList as $key => $val)
        {
            $delivery_id = $val['delivery_id'];
            $package_bn = $val['package_bn'];
            
            //title
            $logTitle = $log_title.'['. $package_bn .']';
            
            //delivery
            $deliveryInfo = $deliveryList[$delivery_id];
            
            //channel_id
            $wms_channel_id = $deliveryInfo['wms_channel_id'];
            if(empty($wms_channel_id)){
                $branchSql = "SELECT branch_id,branch_bn FROM sdb_ome_branch WHERE branch_id=". $deliveryInfo['branch_id'];
                $branchInfo = $deliveryObj->db->selectrow($branchSql);
                
                $wms_channel_id = $this->get_warehouse_code($this->__channelObj->wms['channel_id'], $branchInfo['branch_bn']);
            }
            
            //params
            $params = array(
                    'out_shop_id' => $out_shop_id, //官旗店铺shopid的对应关系
                    'source_type' => $source_type, //数据来源，目前仅有抖音店铺，默认为2
                    'order_id' => $package_bn, //京东订单号
                    'parent_order_id' => $platform_order_bn, //抖音订单号
                    'pay_time' => $pay_time, //平台订单付款时间
                    'pin' => $this->__channelObj->wms['crop_config']['pin'], //pin
                    'warehouse_code' => $wms_channel_id, //渠道ID
            );
            
            //request
            $callback = array();
            $rsp = $this->__caller->call(WMS_PUSH_TRADE_ORDERS, $params, $callback, $logTitle, 10, $order_bn, true, $gateway);
            if($rsp['rsp'] == 'succ'){
                if(is_string($rsp['data'])){
                    $rsp['data'] = json_decode($rsp['data'], true);
                }
                
                if($rsp['data']['result'] == 'success'){
                    $result[$package_bn] = array('rsp'=>'succ');
                }else{
                    $error_msg = '推送失败';
                    $result[$package_bn] = array('rsp'=>'fail', 'error_msg'=>$error_msg);
                }
            }else {
                $error_msg = ($rsp['err_msg'] ? $rsp['err_msg'] : $rsp['msg']);
                $result[$package_bn] = array('rsp'=>'fail', 'error_msg'=>$error_msg);
            }
        }
        
        return $this->succ('请求成功', '', $result);
    }
    
    /**
     * [按京东订单纬度]推送云交易离线对账
     * 
     * @param array $sdf
     * @return array
     */
    public function order_pushPackage($sdf)
    {
        $log_title = '推送京东云交易离线对账';
        $gateway = ''; //判断是否加密
        
        $order_bn = $sdf['order_bn'];
        $package_bn = $sdf['package_bn'];
        $pay_time = date('Y-m-d H:i:s', $sdf['paytime']);
        $out_shop_id = $sdf['out_shop_id'];
        $source_type = ($sdf['source_type'] ? $sdf['source_type'] : '2'); //数据来源，目前仅有抖音店铺，默认为2
        
        //channel_id
        $wms_channel_id = $sdf['wms_channel_id'];
        
        //平台订单号过滤掉A字母
        $platform_order_bn = str_replace('A', '', $order_bn);
        
        if(empty($order_bn) || empty($package_bn)){
            return $this->error('请求参数没有传订单信息。');
        }
        
        if(empty($wms_channel_id)){
            return $this->error('wms_channel_id渠道ID不存在。');
        }
        
        //params
        $params = array(
                'out_shop_id' => $out_shop_id, //官旗店铺shopid的对应关系
                'source_type' => $source_type, //数据来源，目前仅有抖音店铺，默认为2
                'order_id' => $package_bn, //京东订单号
                'parent_order_id' => $platform_order_bn, //抖音订单号
                'pay_time' => $pay_time, //平台订单付款时间
                'pin' => $this->__channelObj->wms['crop_config']['pin'], //pin
                'warehouse_code' => $wms_channel_id, //渠道ID
        );
        
        //request
        $result = array();
        $callback = array();
        $rsp = $this->__caller->call(WMS_PUSH_TRADE_ORDERS, $params, $callback, $log_title, 10, $package_bn, true, $gateway);
        if($rsp['rsp'] == 'succ'){
            if($rsp['data'] && is_string($rsp['data'])){
                $rsp['data'] = json_decode($rsp['data'], true);
            }
            
            $error_msg = '';
            if($rsp['data']['result'] == 'success' && $rsp['data']['message']){
                foreach($rsp['data']['message'] as $key => $val)
                {
                    if($val['result']['errCode'] == '200'){
                        return $this->succ('请求成功');
                    }
                    
                    if($val['result']['errMsg']){
                        $error_msg = $val['result']['errMsg'];
                    }
                }
                
                $error_code = $jdRsp['returnType']['errCode'];
                
                return $this->error('推送失败('. $error_msg .')', $error_code);
            }else{
                return $this->error('推送失败', 'Error002');
            }
        }
        
        $error_msg = ($rsp['err_msg'] ? $rsp['err_msg'] : $rsp['msg']);
        return $this->error($error_msg, 'Error001');
    }
    
    /**
     * [按京东订单纬度]推送云交易SKU信息
     * 
     * @param array $sdf
     * @return array
     */
    public function order_pushSku($sdf)
    {
        $log_title = '推送京东云交易EBS对账';
        $gateway = ''; //判断是否加密
        
        //sdf
        $source_type = ($sdf['source_type'] ? $sdf['source_type'] : '2'); //数据来源，目前仅有抖音店铺，默认为2
        $platform_source = ($sdf['platform_source'] ? $sdf['platform_source'] : '1'); //三方平台来源，抖音传1
        $appKey = $sdf['appKey'];
        
        $order_bn = str_replace('A', '', $sdf['order_bn']); //平台订单号过滤掉A字母
        $package_bn = $sdf['package_bn'];
        $out_shop_id = $sdf['out_shop_id'];
        $sku_id = $sdf['sku_id'];
        $outer_sku = $sdf['outer_sku'];
        
        //channel_id
        $wms_channel_id = $sdf['wms_channel_id'];
        
        //check
        if(empty($order_bn) || empty($package_bn)){
            return $this->error('请求参数没有传订单信息。');
        }
        
        if(empty($wms_channel_id)){
            return $this->error('wms_channel_id渠道ID不存在。');
        }
        
        if(empty($appKey)){
            return $this->error('矩阵appKey不能为空。');
        }
        
        //params
        $params = array(
                'outPlatformShopId' => $out_shop_id, //官旗店铺shopid的对应关系
                'orderId' => $package_bn, //京东订单号
                'sourceType' => $source_type, //数据来源，目前仅有抖音店铺，默认为2
                'outPlatformSource' => $platform_source, //三方平台来源，抖音传1
                'skuId' => $outer_sku, //京东SKU货号
                'outPlatformSkuId' => $sku_id, //抖音sku_id
                'outPlatformParentOrderId' => $order_bn, //抖音订单号
                //'pin' => $this->__channelObj->wms['crop_config']['pin'], //pin
                //'warehouse_code' => $wms_channel_id, //渠道ID
                'channelId' => $wms_channel_id, //渠道ID
                'appKey' => $appKey, //矩阵appKey
        );
        
        //json
        $requestParams = array(
                'pin' => $this->__channelObj->wms['crop_config']['pin'], //pin
                'warehouse_code' => $wms_channel_id, //渠道ID
                'jos_method' => 'jingdong.ctp.order.pushOutPlatFormDataWithSku',
                'data' => json_encode($params),
        );
        
        //request
        $result = array();
        $callback = array();
        $rsp = $this->__caller->call(WMS_PUSH_TRADE_ORDER_SKU, $requestParams, $callback, $log_title, 10, $package_bn, true, $gateway);
        if($rsp['rsp'] == 'succ'){
            if($rsp['data'] && is_string($rsp['data'])){
                $rsp['data'] = json_decode($rsp['data'], true);
            }
            
            $error_msg = '';
            if($rsp['data']){
                $jdRsp = $rsp['data']['jingdong_ctp_order_pushOutPlatFormDataWithSku_responce'];
                
                if($jdRsp['returnType']['errCode'] == '200'){
                    return $this->succ('请求成功');
                }
                
                if($jdRsp['returnType']['errMsg']){
                    $error_msg = $jdRsp['returnType']['errMsg'];
                }
                
                $error_code = $jdRsp['returnType']['errCode'];
                
                return $this->error('推送失败('. $error_msg .')', $error_code);
            }else{
                return $this->error('推送失败', 'Error002');
            }
        }
        
        $error_msg = ($rsp['err_msg'] ? $rsp['err_msg'] : $rsp['msg']);
        return $this->error($error_msg, 'Error001');
    }
}