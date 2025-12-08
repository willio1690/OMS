<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class o2o_event_trigger_logistics_data_electron_common {
    public $channel;
    protected $directRet = array();
    protected $needRequestId = array();
    protected $needRequestDeliveryId = array();
    protected $needGetWBExtend = false;

    /**
     * 设置Channel
     * @param mixed $channel channel
     * @return mixed 返回操作结果
     */
    public function setChannel($channel) {
        $this->channel = $channel;
        return $this;
    }

    /**
     * 获取DeliverySdf
     * @param mixed $delivery delivery
     * @param mixed $shop shop
     * @return mixed 返回结果
     */
    public function getDeliverySdf($delivery, $shop) {
        return false;
    }

    /**
     * 获取DirectSdf
     * @param mixed $arrDelivery arrDelivery
     * @param mixed $arrBill arrBill
     * @param mixed $shop shop
     * @return mixed 返回结果
     */
    public function getDirectSdf($arrDelivery, $arrBill, $shop) {

        $sdf = array();
        $deliveryOrder = $this->getDeliveryOrder($this->needRequestDeliveryId);
        $order_bns = array();
        foreach($deliveryOrder as $val) {
            // 御城河
            if ($val['shop_type'] == 'taobao' && $val['createway'] == 'matrix') $order_bns[]= $val['order_bn'];
        }
        foreach($arrDelivery as $delivery) {
            $package_items = $this->getDeliveryItems($delivery['delivery_id']);
            $failArray = array('delivery_id'=>$delivery['delivery_id'],'delivery_bn'=>$delivery['delivery_bn']);
            if(empty($delivery['ship_province']) || empty($delivery['ship_addr'])){
                $failArray['msg'] = '收货地址省份和详细地址不能少';
                $this->directRet['fail'][] =  $failArray;
                continue;
            }
            if(empty($package_items)){
                $failArray['msg'] = '包裹明细不能少';
                $this->directRet['fail'][] =  $failArray;
                continue;
            }
            if(empty($delivery['ship_mobile']) && empty($delivery['ship_tel'])){
                $failArray['msg'] = '收货地址手机号和电话不能同时少';
                $this->directRet['fail'][] =  $failArray;
                continue;
            }
            $tmpDelivery = $delivery;
            $tmpDelivery['package_items'] = $package_items;
            $deliveryExtend[] = $tmpDelivery;
            $this->needRequestId[] = $delivery['delivery_id'];
        }
        $sdf['order_bns'] = $order_bns;
        $sdf['primary_bn'] = $primary_bn;
        $sdf['shop']       = $shop;
        $sdf['delivery']   = $deliveryExtend;
        return $sdf;
    }

    /**
     * directDealParam
     * @param mixed $arrDeliveryId ID
     * @param mixed $arrBillId ID
     * @return mixed 返回值
     */
    public function directDealParam($arrDeliveryId, $arrBillId) {
        $this->directRet = array();
        $this->needRequestId = array();
        $this->needRequestDeliveryId = array();
        $arrDelivery = $this->preDealDelivery($arrDeliveryId);
        if(empty($arrDelivery) || (isset($arrBill) && empty($arrBill))) {
            return array(
                'succ' => $this->directRet['succ'],
                'fail' => $this->directRet['fail']
            );
        }
        $arrDelivery = $this->__format_delivery($arrDelivery, $arrBill);
        if(empty($arrDelivery)) {
            return array(
                'succ' => $this->directRet['succ'],
                'fail' => $this->directRet['fail']
            );
        }
        $shop = app::get('logisticsmanager')->model('channel_extend')->dump(array('channel_id'=>$this->channel['channel_id']),'shop_name,province,city,area,street,address_detail,seller_id,default_sender,mobile,tel,zip');
        $sdf = $this->getDirectSdf($arrDelivery, $arrBill, $shop);
        return array(
            'succ' => $this->directRet['succ'],
            'fail' => $this->directRet['fail'],
            'sdf' =>  $sdf,
            'need_request_id' => $this->needRequestId
        );
    }

    /**
     * preDealDelivery
     * @param mixed $arrDeliveryId ID
     * @return mixed 返回值
     */
    public function preDealDelivery($arrDeliveryId) {

        $arrDelivery = app::get('wap')->model('delivery')->getList('*', array('delivery_id'=>$arrDeliveryId));
        $billObj = app::get('wap')->model('delivery_bill');
        $objWaybill = app::get('logisticsmanager')->model('waybill');
        $needRequest = $hasDelivery = array();
        foreach($arrDelivery as $delivery) {
            $arrBill = $billObj->dump(array('delivery_id'=>$delivery['delivery_id'],'type'=>'1','status'=>'0'),'logi_no');
            $logi_no = $arrBill['logi_no'];
            $hasDelivery[] = $delivery['delivery_id'];
            $filter = array('channel_id' => $this->channel['channel_id'], 'waybill_number' => $logi_no);
            if(!$this->checkLogisticsChannel($delivery['logi_id'])) {
                $this->directRet['fail'][] = array(
                    'delivery_id' => $delivery['delivery_id'],
                    'delivery_bn' => $delivery['delivery_bn'],
                    'msg' => '该发货单已经切换物流公司了'
                );
                continue;
            }
            if (!empty($logi_no) && $objWaybill->dump($filter)) {
                $this->directRet['succ'][] = array(
                    'logi_no' => $logi_no,
                    'delivery_id' => $delivery['delivery_id'],
                    'delivery_bn' => $delivery['delivery_bn']
                );
            } else {
                $needRequest[] = $delivery;
                $this->needRequestDeliveryId[] = $delivery['delivery_id'];
            }
        }
        $noDelivery = array_diff($arrDeliveryId, $hasDelivery);
        if($noDelivery) {
            foreach($noDelivery as $val) {
                $this->directRet['fail'][] = array(
                    'delivery_id' => $val,
                    'msg' => '没有该发货单'
                );
            }
        }
        return $needRequest;
    }

    protected function getDeliveryOrder($deliveryId) {
        $db = kernel::database();
        $deliveryIds = $this->getDeliveryIdByWap($deliveryId);
        $where = is_array($deliveryId) ? 'd.delivery_id in (' . (implode(',', $deliveryIds)) . ')' : 'd.delivery_id = "' . $deliveryIds .'"';
        $field = 'o.order_id, o.order_bn, o.total_amount, o.shop_id, o.createway, o.shop_type,d.delivery_id';
        $sql = 'select '. $field . ' from sdb_ome_delivery_order as d left join sdb_ome_orders as o using(order_id) where ' . $where;
        $result = $db->select($sql);
        return $result;
    }

    protected function getShop($shop_id)
    {
        static $shop_list;

        if (isset($shop_list[$shop_id])) return $shop_list[$shop_id];

        $shop_list[$shop_id] = app::get('ome')->model('shop')->db_dump($shop_id,'shop_id,node_id,node_type');

        return $shop_list[$shop_id];
    }
    //获取两条订单明细
    protected function getOrderItems($order_id) {
        static $orderItems = array();
        if (!$orderItems[$order_id]) {
            $orderItems[$order_id] = app::get('ome')->model('order_items')->getList('*', array('order_id' => $order_id), 0, 2);
        }
        return $orderItems[$order_id];
    }

    //获取两条发货单明细
    /**
     * 获取DeliveryItems
     * @param mixed $delivery_id ID
     * @return mixed 返回结果
     */
    public function getDeliveryItems($delivery_id) {
        static $deliveryItems = array();
        if(!$deliveryItems[$delivery_id]) {
            $deliveryItems[$delivery_id] = app::get('wap')->model('delivery_items')->getList('*', array('delivery_id' => $delivery_id),0,2);
        }
        return $deliveryItems[$delivery_id];
    }

    #验证物流公司是否是该渠道的
    protected function checkLogisticsChannel($logiId) {
        return true;
    }

    /**
     * 获取DeliveryIdByWap
     * @param mixed $deliveryId ID
     * @return mixed 返回结果
     */
    public function getDeliveryIdByWap($deliveryId){
        $db = kernel::database();
        $sql = "SELECT w.outer_delivery_bn FROM sdb_wap_delivery as w WHERE w.delivery_id in (".implode(',', $deliveryId).")";
        $delivery_list = $db->select($sql);
        $delivery_bnList = array();
        foreach ($delivery_list as $delivery){
            $delivery_bnList[] = $delivery['outer_delivery_bn'];
        }

        $deliveryArr = $db->select("SELECT d.delivery_id FROM sdb_ome_delivery as d WHERE d.delivery_bn in(".'\''.implode('\',\'',$delivery).'\''.")");
        $deliveryIds = array();
        foreach ($deliveryArr as $deliverys){
            $deliveryIds[] = $deliverys['delivery_id'];
        }
        return $deliveryIds;
    }

    /**
     * 获取_corp
     * @param mixed $corp_id ID
     * @return mixed 返回结果
     */
    public  function get_corp($corp_id){
        static $corps;
        if (isset($corps[$corp_id])) return $corps[$corp_id];
        $corpModel = app::get('ome')->model('dly_corp');
        $rows = $corpModel->getList('corp_id,type,name,protect,protect_rate',array('corp_id'=>$corp_id));
        foreach ($rows as $row) {
            $corps[$row['corp_id']] = $row;
        }
        return $corps[$corp_id] ? $corps[$corp_id] : array();
    }

    # 扩展发货单
    private function __format_delivery($arrDelivery, $arrBill)
    {
        foreach($arrDelivery as $key => $value) {
            $is_jd_order = false; $totalAmount = 0;
            $deliveryOrder = $this->getDeliveryOrder((array)$value['delivery_id']);
            $arrShopOrderBn = array();
            $receivable = 0;
            $order_bns = array ();
            foreach ($deliveryOrder as $k => $v) {
                if ($v['shop_type'] == '360buy') $is_jd_order = true;
                if ($v['createway'] == 'matrix') $arrShopOrderBn[$v['shop_id']][] = $v['order_bn'];
                $orderExtend = $this->getOrderExtend($v['order_id']);
                $receivable += $orderExtend[$v['order_id']]['receivable'];
                $totalAmount += $v['total_amount'];

                $order_bns[] = $v['order_bn'];
            }

            // 京东订单推送物流公司，手机号隐藏
            if ($is_jd_order && $this->channel['channel_type']!='taobao') $arrDelivery[$key]['ship_mobile'] = '00000000000';

            $arrDelivery[$key]['total_amount'] = $totalAmount;

            $arrDelivery[$key]['delivery_order'] = $deliveryOrder;
            $arrDelivery[$key]['receivable'] = $receivable;
            $arrDelivery[$key]['order_bns']      = $order_bns;
            $arrDelivery[$key]['shop']           = $this->getShop($value['shop_id']);
        }

        return $arrDelivery;
    }

    protected function getOrderExtend($arrOrderId) {
        $extend = app::get('ome')->model('order_extend')->getList('order_id,receivable', array('order_id'=>$arrOrderId));
        $orderExtend = array();
        foreach($extend as $val) {
            $orderExtend[$val['order_id']] = $val;
        }
        return $orderExtend;
    }


    final protected function _formate_receiver_province($province,$district='')
    {
        $mapping = array(
            '新疆' => '新疆维吾尔自治区',
            '宁夏' => '宁夏回族自治区',
            '广西' => '广西壮族自治区',
        );

        if ($mapping[$province]) return $mapping[$province];

        $zhixiashi = array('北京','上海','天津','重庆');
        $zizhiqu = array('内蒙古','宁夏回族','新疆维吾尔','西藏','广西壮族');

        if (in_array($province,$zhixiashi) && !$district) { // 如果三级不存在，直接将省提升为市
            $province = $province.'市';
        } elseif (in_array(rtrim($province, '市'),$zhixiashi)) {
            $province = rtrim($province, '市');
        }elseif (in_array($province,$zizhiqu)) {
            $province = $province.'自治区';
        }elseif(!preg_match('/(.*?)省/',$province)){
            $province = $province.'省';
        }

        return $province;
    }
}
