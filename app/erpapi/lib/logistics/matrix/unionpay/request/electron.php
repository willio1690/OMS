<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * sunjing@shopex.cn
 * Date: 2016/11/23
 */
class erpapi_logistics_matrix_unionpay_request_electron extends erpapi_logistics_request_electron
{

    /**
     * bufferRequest
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */

    public function bufferRequest($sdf){
        return $this->directNum;
    }

    /**
     * directRequest
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function directRequest($sdf){
        
        
        $delivery = $sdf['delivery'];
        $this->primaryBn = $delivery['delivery_bn'];
        $shopInfo = $sdf['shop'];
        $to_address = $delivery['ship_addr'] ? $delivery['ship_province'] . $delivery['ship_city'] . $delivery['ship_district'] . $delivery['ship_addr'] : '_SYSTEM';
     
        $receiver = array(
            'company'   =>  $this->charFilter($delivery['ship_name']),
            'name'      =>  $this->charFilter($delivery['ship_name']),
            'tel'       =>  $delivery['ship_tel'],
            'mobile'    =>  $delivery['ship_mobile'],
            'post_code' =>  $delivery['ship_zip'],
            'province'  =>  $delivery['ship_province'],
            'city'      =>  $delivery['ship_city'],
            'area'      =>  $delivery['ship_district'],
            'address'   =>  $this->charFilter($to_address),
        );
         $area = explode(':',$shopInfo['area']);
         $area = explode('/',$area[1]);  
        $sender = array(
            'company'   =>  $shopInfo['default_sender'] ? $shopInfo['default_sender'] : '_SYSTEM',
            'name'      =>  $shopInfo['default_sender'] ? $shopInfo['default_sender'] : '_SYSTEM',
            'tel'       =>  $shopInfo['tel'],
            'mobile'    =>  $shopInfo['mobile'],
            'post_code' =>  $shopInfo['zip'],
            'province'  =>  $area[0],
            'city'      =>  $area[1],
            'area'      =>  $area[2],
            'address'   =>  $this->charFilter($shopInfo['addr']),
        );

        $params = array(
            //'member_id'     =>  $delivery['member_id'],
            'send_site'     =>  "",  # 收件网点标识
            'shipper_code'  =>  $this->__channelObj->channel['logistics_code'],  # 物流公司编码
            'logistic_code' =>  '',  # 运单号
            'tid'           =>  $delivery['delivery_bn'],  # 订单号
            'cost'          =>  '',
            'other_cost'    =>  '',
            'receiver'      =>  json_encode($receiver),
            'sender'        =>  json_encode($sender),
            'start_date'    =>  '',#上门取货时间段
            'end_date'      =>  '',
            'weight'        =>  '',
            'volume'        =>  '',
            'remark'        =>  '',
            'qty'           =>  '',
            'service_list'  =>  '',#增值服务
            'commoditys'     =>  json_encode($this->format_delivery_item($sdf['delivery_item'])),#货品明细信息
            'device_type' =>'pc',//pos android ios wp qt pc
            'is_auto_bill'  =>'1',
            'exp_type'=>'1',
        );

        // 是否加密
        $is_encrypt = false;
        if (!$is_encrypt) {
            $is_encrypt = kernel::single('ome_security_router',$delivery['shop_type'])->is_encrypt($delivery,'delivery');
        }


        // 云鼎解密
        $gateway = ''; $jst = array ('order_bns' => $delivery['order_bns']);
        if ($is_encrypt) {
            $params['s_node_id']    = $delivery['shop']['node_id'];
            $params['s_node_type']  = $delivery['shop_type'];
            // 新增解密字段
            $params['order_bns']    = implode(',', $delivery['order_bns']);

            $gateway = $delivery['shop_type'];
        }

        $back =   $this->requestCall(SHOP_LOGISTICS_SUBSCRIBE, $params,array(),$sdf, $gateway);
        return $this->backToResult($back, $delivery);
    }
    #获取货物名称
    /**
     * format_delivery_item
     * @param mixed $deliveryItems deliveryItems
     * @return mixed 返回值
     */
    public function format_delivery_item($deliveryItems = null) {
        $items = array();
        foreach($deliveryItems as $key=>$item){
            $items[$key]['code']    = $item['bn'];
            $items[$key]['name']    = $item['product_name'];
            $items[$key]['num']     = $item['number'];
            $items[$key]['price']   = $item['price'];//weight desc vol
            $items[$key]['weight']  = $item['weight'];
            $items[$key]['desc']    = $item['desc'];
            $items[$key]['GoodsSku'] = $item['barcode'];
            $items[$key]['vol']     = $item['vol'];
        }
        return $items;
    }

    /**
     * backToResult
     * @param mixed $back back
     * @param mixed $delivery delivery
     * @return mixed 返回值
     */
    public function backToResult($back, $delivery){

        $data = empty($back['data']) ? '' : json_decode($back['data'], true);
        $data = empty($data['data']) ? '' :  json_decode($data['data'], true);
        if(empty($data)) {
            return false;
        }
        $msg = '';
        #以下这种情况，说明银联做过特殊处理了，需要在打印页面提示客户
        if($data['UmsbillNo'] && (($data['Success'] == true && empty($data['Order'] ['LogisticCode'])) && empty($data['Reason']))){
            $msg = '单据已推送到仓储';
        }elseif(!empty($data['Reason'])){
            $msg = $data['Reason'];
        }
        $result = array();
        $result[] = array(
            'succ' => $data['Order'] ['LogisticCode']? true : false,
            'msg' => $msg,
            'delivery_id' => $delivery['delivery_id'],
            'delivery_bn' => $delivery['delivery_bn'],
            'logi_no' => $data['Order'] ['LogisticCode'],
            'position' =>  $data['Order'] ['DestinatioName']?$data['Order'] ['DestinatioName']:'',
            'position_no' =>  $data['Order'] ['DestinatioCode']?$data['Order'] ['DestinatioCode']:'',
            'package_wdjc' => $data['Order'] ['DestinatioName'],
            'package_wd' => $data['Order'] ['DestinatioCode'],
        );
        $this->directDataProcess($result);
        return $result;
    }

    /**
     * recycleWaybill
     * @param mixed $waybillNumber waybillNumber
     * @param mixed $delivery_bn delivery_bn
     * @return mixed 返回值
     */
    public function recycleWaybill($waybillNumber,$delivery_bn = '') {
        
        $this->title = '银联_' . $this->__channelObj->channel['logistics_code'] . '取消电子面单';
        $this->primaryBn = $waybillNumber;
        $params = array(
            'order_id' => $waybillNumber,
            'shipper_code'=>  $this->__channelObj->channel['logistics_code'],
             'is_auto_bill'  =>'1',
            'device_type' =>'pc',
            'exp_type'=>'1',
        );
        
        $this->requestCall(STORE_TRADE_CANCEL, $params, $callback);
    }
}