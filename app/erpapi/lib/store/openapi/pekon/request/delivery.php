<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * pos发货单对接shopex pos
 *
 * https://docs.pekon.com/docCenter/home?docId=8b76bfb5
 *
 * @author sunjing@shopex.cn
 * @version 0.1
 *
 */
class erpapi_store_openapi_pekon_request_delivery extends erpapi_store_request_delivery
{
    protected $_shop_type_mapping = array(
        'taobao'       => array('code' => 'TB', 'name' => '淘宝'),
        'tmall'        => array('code' => 'TM', 'name' => '天猫'),
        'paipai'       => array('code' => 'PP', 'name' => '拍拍'),
        '360buy'       => array('code' => 'JD', 'name' => '京东'),
        'yihaodian'    => array('code' => 'YHD', 'name' => '1号店'),
        'qq_buy'       => array('code' => 'QQ', 'name' => 'QQ网购'),
        'dangdang'     => array('code' => 'DD', 'name' => '当当'),
        'amazon'       => array('code' => 'AMAZON', 'name' => '亚马逊'),
        'yintai'       => array('code' => 'YT', 'name' => '银泰'),
        'vjia'         => array('code' => 'VANCL', 'name' => '凡客'),
        'alibaba'      => array('code' => '1688', 'name' => '阿里巴巴'),
        'suning'       => array('code' => 'SN', 'name' => '苏宁'),
        'gome'         => array('code' => 'GM', 'name' => '国美'),
        'guomei'       => array('code' => 'GM', 'name' => '国美'),
        'mogujie'      => array('code' => 'MGJ', 'name' => '蘑菇街'),
        'vop'          => array('code' => 'WPH', 'name' => '唯品会'),
        'pinduoduo'    => array('code' => 'PDD', 'name' => '拼多多'),
        'luban'        => array('code' => 'DY', 'name' => '抖音'),
        'kuaishou'     => array('code' => 'KS', 'name' => '快手'),
        'alibaba4ascp' => array('code' => 'TM', 'name' => '淘工厂'),
        'youzan'       => array('code' => 'YZ', 'name' => '有赞'),
        'haoshiqi'     => array('code' => 'HSQ', 'name' => '好食期'),
        'gegejia'      => array('code' => 'GGJ', 'name' => '格格家'),
        'xhs'          => array('code' => 'XHS', 'name' => '小红书'),
        'weimobv'      => array('code' => 'WM', 'name' => '微盟'),
        'weimobr'      => array('code' => 'WM', 'name' => '微盟'),
        'xiaomi'       => array('code' => 'XMYP', 'name' => '小米有品'),
        'wx'           => array('code' => 'WeChat', 'name' => '微信'),
        'pekon'        => array('code' => 'WeChat', 'name' => 'POS'), 
    );

    /**
     * 发货单创建
     * 因目前shopex 发货业务 用wap端所以继承了原wap业务
     * @return void
     * @author
     **/
    public function delivery_create($sdf)
    {
        $delivery_bn = $sdf['outer_delivery_bn'];

        $iscancel = kernel::single('ome_interface_delivery')->iscancel($delivery_bn);
        if ($iscancel) {
            return $this->succ('发货单已取消,终止同步');
        }

        #如果 delivery = SHIPED 则自发货
        if ($sdf['delivery'] == 'SHIPED') {
            $filter = [
                'store_bn' => $sdf['branch_bn'],
            ];
            $storeMdl = app::get('o2o')->model('store');
            $store    = $storeMdl->dump($filter, 'store_id');

            $data['status']      = 'delivery';
            $data['delivery_bn'] = $delivery_bn;
            $delivery_items = $sdf['delivery_items'];

            $item = [];
            foreach($delivery_items as $v){

                $item[] = array(
                    'product_bn'    =>  $v['bn'],
                    'num'           =>  $v['number'],
                    'sn_list'       =>  $v['sn_list'],
                );
            }
            $data['item'] = json_encode($item);
            return kernel::single('erpapi_router_response')->set_channel_id($store['store_id'])->set_api_name('store.delivery.status_update')->dispatch($data);
        }

        $title = $this->__channelObj->store['channel_name'] . '发货单添加';

        $params = $this->_format_delivery_create_params($sdf);

        if (!$params) {
            return $this->error('参数为空,终止同步');
        }

        $result = $this->call('CreateSalesOrder', $params, null, $title, 30, $delivery_bn);

        if ($result['rsp'] == 'succ') {
            $result['data']['wms_order_code'] = $result['data']['orderNo'];
            //电子券
            if ($sdf['bool_type'] & ome_delivery_bool_type::__ETICKET_CODE) {
                $filter = [
                    'store_bn' => $sdf['branch_bn'],
                ];
                $storeMdl = app::get('o2o')->model('store');
                $store    = $storeMdl->dump($filter, 'store_id');

                $data['status']      = 'delivery';
                $data['delivery_bn'] = $delivery_bn;

            kernel::single('erpapi_router_response')->set_channel_id($store['store_id'])->set_api_name('store.delivery.status_update')->dispatch($data);
            }
        }

        return $result;
    }

    protected function _format_delivery_create_params($sdf)
    {
        $b_type = $sdf['b_type'];
        $deliveryOrgType = $b_type == '1' ? 'DC' : 'Store';

        $eticked = kernel::single('ome_delivery_bool_type')->isEticket($sdf['bool_type']);
       
        $params = [
            //'salesEmpCode'       => '18770038856', // 销售人员编码
            //'operatorEmpCode'    => 'JK',
            'orderSource'        => $eticked ? 'TMALL':'SHOP',
            'orderType'          => 'NM', // NM：普通订单,YD：预订单,JS：寄售单,YPLY：礼品领用,EXCHANGE：换货单
            'actualOrderSource'  => 'OMS',
            'deliveryOrgType'    =>  $deliveryOrgType,
            'businessType'       => 'immediate', // immediate：即时业务,additional：补录业务,presell：预售业务
            'salesType'          => 'S', // S：销售,H：换货
            'thirdpartyOrderNo'  => $sdf['outer_delivery_bn'],
            'businessTime'       => date('Y-m-d H:i:s', $sdf['order_createtime']),
            'orderTime'          => date('Y-m-d H:i:s', $sdf['order_createtime']),
            'salesOrgCode'       => $this->__channelObj->store['store_bn'],
            'totalQuantity'      => $sdf['itemNum'],
            'currencyCode'       => 'CNY',
            'payStatus'          => 'ALL_PAY',
            'deliveryType'       => 'EXPRESS',
            'logisticsStatus'    => 'BUYER_CONFIRM_GOODS',
            'originAmount'       => '0',
            'discountAmount'     => '0',
            'freight'            => '0',
            'amount'             => '0',
            'storeCode'          => $eticked ? $sdf['branch_bn'] : $sdf['shop_code'],
            'thirdpartyOrderNo2' => $sdf['order_bn'],
            'referenceOrderNo'   => $sdf['relate_order_bn'],   
            'receiverAddress'    => [
                'receiverName'   => $sdf['consignee']['name'],
                'receiverMobile' => $eticked ? $sdf['consignee']['addr']:$sdf['consignee']['mobile'],
                'province'       => $sdf['consignee']['province'],
                'city'           => $sdf['consignee']['city'],
                'district'       => $sdf['consignee']['district'],
                'address'        => $sdf['consignee']['addr'],
            ],
        ];

        if ($sdf['logi_code'] == 'o2o_pickup') {
            $params['deliveryType'] = 'PICKUP';
        }
        // if ($sdf['s_type'] == '2') {
           // $params['orderSource'] = 'SHOP';
        // }

        $items = [];

        $sdf['delivery_items'] = array_values($sdf['delivery_items']);
        $adjustments=[];
        foreach ($sdf['delivery_items'] as $key => $value) {
            $oid = $value['oid'] ? list($itemoid,$seqno)=explode('_',$value['oid']) : '';
            $uniqueCodes = [];
            if($value['uniqueCodes']){
                foreach($value['uniqueCodes'] as $sv){
                    $uniqueCodes[]['uniqueCode'] = $sv;
                }
                
            }
            $item = [
                'itemSeqNo'       => $eticked? ($key+1) : $seqno,//订单里的明细行号
                'itemType'        => 'N',
                'productSkuCode'  => $value['bn'],
                'originPrice'     => $value['price'],
                'price'           => $value['price'],
                'quantity'        => $value['number'],
                'amount'          => $eticked ? 0:$value['sale_price'],
                'settlementPrice' => $eticked ? 0:$value['sale_price'] / $value['number'],
            ];
            if($uniqueCodes) $item['uniqueCodes'] = $uniqueCodes;
            $items[] = $item;
            if($eticked){
                /*
                $adjustments[]=[
                    'orderItemSeqNo'    =>  $item['itemSeqNo'],
                    'promotionCode'     =>  'TmallCouponPromotion',
                    'promotionType'     =>  'ALL',
                    'amount'            =>  '-'.$value['sale_price'],
                    'numberOfTimes'     =>  1,

                ];*/
            }
            
            $params['originAmount'] += $item['originPrice'] * $item['quantity'];
            // $params['discountAmount'] += $item['amount'] - $item['settlementPrice'];
            $params['amount'] += $value['sale_price'];
        }
        if($eticked) $params['amount']=0;
        $params['discountAmount'] = $params['originAmount'] - $params['amount'];

        $params['orderItems'] = $items;

        $pay_bn = $sdf['pay_bn'];
        $pay_bns = explode(',',$pay_bn);
        if(count($pay_bns)>=2 && $sdf['relate_order_bn']){
            $orderMdl = app::get('ome')->model('orders');
            $orders = $orderMdl->db_dump(array('order_bn'=>$sdf['relate_order_bn']),'order_id');
            $order_id = $orders['order_id'];
            $paymentsMdl = app::get('ome')->model('payments');
            $payment_list = $paymentsMdl->getlist('*',array('order_id'=>$order_id));
            foreach($payment_list as $v){
                $payments[] =[
                    'payType'   =>  $v['pay_bn'],
                    'payTime'   =>  $v['t_begin'],
                    'amount'    =>  $v['money'],

                    
                ];
            }
            $params['payments'] = $payments;
        }else{
            $params['payments']   = [
                [
                    "payType" => $eticked ? 'TmallCoupon' : $sdf['pay_bn'],
                    "amount"  => $eticked ? 0 : $params['amount'],
                    "payTime" => $sdf['pay_time'],
                ],
            ];
        }
        $promotions = [];
        
        if($eticked){
            $promotions[] = [
                'promotionCode' =>  'TmallCouponPromotion',
                'promotionName' =>  '天猫O2O优惠券促销',
                'promotionType' =>  'ALL',
                'discountPrice' =>  '-'.$sdf['total_amount'],
                'discountAmount'=>  '-'.$sdf['total_amount'],
                'numberOfTimes' =>1,
            ];


        }
        $params['promotions']  = $promotions;
        $params['coupons']     = [];
        

        $params['adjustments'] = $adjustments;
        $relate_order_bn = $sdf['relate_order_bn'];
        $memo = $relate_order_bn.'换货';
        $params['memo'] = $eticked ? '' : $memo;
        return $params;
    }

    /**
     * 发货单取消
     *
     * @return void
     * @author 
     **/
    public function delivery_cancel($sdf){
        $delivery_bn = $sdf['outer_delivery_bn'];

        $title = $this->__channelObj->wms['channel_name'] . '发货单取消';

        
        return array('rsp'=>'succ');

    }
}
