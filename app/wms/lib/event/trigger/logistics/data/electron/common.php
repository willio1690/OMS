<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class wms_event_trigger_logistics_data_electron_common {
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
        return [];
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

        $sdf['order_bns'] = $order_bns;

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
        if(empty($arrBillId)) {
            $arrDelivery = $this->preDealDelivery($arrDeliveryId);
        } else {
            $arrDelivery = app::get('wms')->model('delivery')->getList('*', array('delivery_id'=>$arrDeliveryId));
            $arrBill = $this->preDealBillDelivery($arrBillId, $arrDelivery[0]);
        }
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
        $shop = app::get('logisticsmanager')->model('channel_extend')->dump(array('channel_id'=>$this->channel['channel_id']),'shop_name,province,city,area,street,address_detail,seller_id,default_sender,mobile,tel,zip,addon');
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

        $arrDelivery = app::get('wms')->model('delivery')->getList('*', array('delivery_id'=>$arrDeliveryId));
        $billObj = app::get('wms')->model('delivery_bill');
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

    /**
     * preDealBillDelivery
     * @param mixed $arrBillId ID
     * @param mixed $delivery delivery
     * @return mixed 返回值
     */
    public function preDealBillDelivery($arrBillId, $delivery) {
        if(!$this->checkLogisticsChannel($delivery['logi_id'])) {
            foreach($arrBillId as $billId) {
                $this->directRet['fail'][] = array(
                    'b_id' => $billId,
                    'delivery_bn' => $delivery['delivery_bn'],
                    'msg' => '物流公司已经切换，不能补打'
                );
            }
            return array();
        }
        $arrBill = app::get('wms')->model('delivery_bill')->getList('*', array('b_id'=>$arrBillId));
        $objWaybill = app::get('logisticsmanager')->model('waybill');
        $needRequest = $hasBill = array();
        foreach($arrBill as $bill) {
            $filter = array('channel_id' => $this->channel['channel_id'], 'waybill_number' => $bill['logi_no']);
            if (!empty($bill['logi_no']) && ($this->channel['channel_type'] == '360buy' || $objWaybill->dump($filter))) {
                $this->directRet['succ'][] = array(
                    'logi_no' => $bill['logi_no'],
                    'b_id' => $bill['b_id'],
                    'delivery_id' => $delivery['delivery_id'],
                    'delivery_bn' => $delivery['delivery_bn']
                );
            } else {
                $needRequest[] = $bill;
            }
            $hasBill[] = $bill['b_id'];
        }
        if(!empty($needRequest)) {
            $this->needRequestDeliveryId[] = $delivery['delivery_id'];
        }
        $noBill = array_diff($arrBillId, $hasBill);
        if($noBill) {
            foreach($noBill as $val) {
                $this->directRet['fail'][] = array(
                    'b_id' => $val,
                    'msg' => '没有该补打单'
                );
            }
        }
        return $needRequest;
    }

    protected function getDeliveryOrder($deliveryId) {
        $db = kernel::database();
        $deliveryIdInfo = $this->getDeliveryIdBywms($deliveryId);
        $deliveryIds = array_column($deliveryIdInfo,'ome_delivery_id');

        if (!$deliveryIds){
            $deliveryIds = is_array($deliveryId) ? [0] : 0;
        }

        $where = is_array($deliveryId) ? 'd.delivery_id in (' . (implode(',', $deliveryIds)) . ')' : 'd.delivery_id = "' . $deliveryIds .'"';

        $field = 'o.order_id, o.order_bn, o.total_amount, o.shop_id, o.createway, o.shop_type,d.delivery_id,o.order_bool_type';
        $sql = 'select '. $field . ' from sdb_ome_delivery_order as d left join sdb_ome_orders as o using(order_id) where ' . $where;
        $result = $db->select($sql);
        if ($result) {
            $deliveryIdInfo = array_column($deliveryIdInfo,null,'ome_delivery_id');
            foreach ($result as $key => $val){
                $result[$key]['wms_delivery_id'] = $deliveryIdInfo[$val['delivery_id']]['wms_delivery_id'];
            }
        }
        return $result;
    }

    protected function getShop($shop_id)
    {
        static $shop_list;

        if (isset($shop_list[$shop_id])) return $shop_list[$shop_id];

        $shop_list[$shop_id] = app::get('ome')->model('shop')->db_dump($shop_id,'shop_id,node_id,node_type,addon');

        return $shop_list[$shop_id];
    }
    //获取两条订单明细
    protected function getOrderItems($order_id) {
        static $orderItems = array();
        if (!$orderItems[$order_id]) {
            $orderItems[$order_id] = app::get('ome')->model('order_items')->getList('*', array('order_id' => $order_id), 0, 100);
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
            $deliveryItems[$delivery_id] = app::get('wms')->model('delivery_items')->getList('*', array('delivery_id' => $delivery_id),0,100);
        }
        return $deliveryItems[$delivery_id];
    }

    #设置子单的请求的订单号
    /**
     * 设置ChildRqOrdNo
     * @param mixed $deliveryBn deliveryBn
     * @param mixed $billId ID
     * @return mixed 返回操作结果
     */
    public function setChildRqOrdNo($deliveryBn, $billId){
        $deliveryBn = $deliveryBn."cd".$billId;
        return $deliveryBn;
    }

    #检查是否是子单的请求的订单号
    /**
     * 检查ChildRqOrdNo
     * @param mixed $deliveryBn deliveryBn
     * @param mixed $main_order_no main_order_no
     * @param mixed $waybill_cid ID
     * @return mixed 返回验证结果
     */
    public function checkChildRqOrdNo($deliveryBn, &$main_order_no, &$waybill_cid){
        $pos = strpos($deliveryBn,'cd');
        if( $pos !== false){
            $main_order_no = substr($deliveryBn,0,$pos);
            $waybill_cid = substr($deliveryBn,$pos+2);
            return true;
        }else{
            return false;
        }
    }

    #验证物流公司是否是该渠道的
    protected function checkLogisticsChannel($logiId) {
        return true;
        // $corp = app::get('ome')->model('dly_corp')->db_dump($logiId, 'channel_id');
        // return $corp['channel_id'] == $this->channel['channel_id'] ? true : false;
    }

    /**
     * waybillExtendDealParam
     * @param mixed $arrDelivery arrDelivery
     * @param mixed $arrBill arrBill
     * @return mixed 返回值
     */
    public function waybillExtendDealParam($arrDelivery, $arrBill) {
        if(!$this->needGetWBExtend) {
            return false;
        }
        $waybill = array();
        $waybillDelivery = array();
        foreach($arrDelivery as $delivery) {
            if(!empty($delivery['logi_no'])) { //需要有运单号
                $waybill[] = $delivery['logi_no'];
                $waybillDelivery[$delivery['logi_no']] = $delivery;
            }
        }
        if(empty($waybill)) {
            return array('sdf' => false);
        }
        $objExtend = app::get("logisticsmanager")->model("waybill_extend");
        $sql = 'SELECT w.waybill_number, e.id FROM sdb_logisticsmanager_waybill w LEFT JOIN sdb_logisticsmanager_waybill_extend e ON (w.id = e.waybill_id) WHERE w.waybill_number in ("'. implode('","', $waybill) .'") AND w.channel_id = "' . $this->channel['channel_id'] . '"';
        $arrExtend = $objExtend->db->select($sql);
        $needExtendDelivery = array();
        foreach($arrExtend as $val) {
            if(empty($val['id']) && $val['waybill_number']) {//没有大头笔
                $needExtendDelivery[] = $waybillDelivery[$val['waybill_number']];
            }
        }
        if(empty($needExtendDelivery)) {
            $billExtend = true;
            if(!empty($arrBill)) {
                $billExtend = $this->dealBillExtend($arrBill, $arrExtend[0]['id']);
            }
            return array('sdf' => false, 'bill_extend_fail' => !$billExtend);
        }
        $shop = app::get('logisticsmanager')->model('channel_extend')->dump(array('channel_id'=>$this->channel['channel_id']),'province,city,area,address_detail,seller_id,default_sender,mobile,tel,zip');
        $sdf = $this->getWaybillExtendSdf($needExtendDelivery, $shop);
        return array('sdf' => $sdf);
    }

    protected function getWaybillExtendSdf($arrDelivery, $shop) {
        return false;
    }

    protected function dealBillExtend($arrBill, $extendId) {
        $billWaybill = array();
        foreach($arrBill as $bill) {
            $billWaybill[] = $bill['logi_no'];
        }
        $objWaybill = app::get("logisticsmanager")->model("waybill");
        $filter = array(
            'waybill_number' => $billWaybill,
            'channel_id' => $this->channel['channel_id']
        );
        $waybill = $objWaybill->getList('id', $filter);
        $waybillId = array();
        foreach($waybill as $val) {
            $waybillId[$val['id']] = 1;
        }
        $objExtend = app::get("logisticsmanager")->model("waybill_extend");
        $extend = $objExtend->getList('waybill_id', array('waybill_id'=>array_keys($waybillId)));
        foreach($extend as $value) {
            if($waybillId[$value['waybill_id']]) {//有大头笔不更新
                unset($waybillId[$value['waybill_id']]);
            }
        }
        $upWaybillId = array_keys($waybillId);
        if($upWaybillId) {
            $extendData = $objExtend->dump(array('id'=>$extendId));
            unset($extendData['id']);
            $insertData = array();
            foreach($upWaybillId as $wbId) {
                $tmp = $extendData;
                $tmp['waybill_id'] = $wbId;
                $insertData[] = $tmp;
            }
            $sql = ome_func::get_insert_sql($objExtend, $insertData);
            return kernel::database()->exec($sql);
        }
        return true;
    }

    /**
     * 获取DeliveryIdBywms
     * @param mixed $deliveryId ID
     * @return mixed 返回结果
     */
    public function getDeliveryIdBywms($deliveryId){
        $deliveryId = is_array($deliveryId) ? $deliveryId : [$deliveryId];
        $db = kernel::database();
        $sql = "SELECT w.delivery_id as wms_delivery_id,od.delivery_id as ome_delivery_id FROM sdb_wms_delivery AS w , sdb_ome_delivery AS od where w.outer_delivery_bn = od.delivery_bn and w.delivery_id in (".implode(',', $deliveryId).")";
        $delivery_list = $db->select($sql);
        return $delivery_list;
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
        $rows = $corpModel->getList('corp_id,type,name,protect,protect_rate,minprice',array('corp_id'=>$corp_id));
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
            $performance_type = $platform_logi_no = '';
            foreach ($deliveryOrder as $k => $v) {
                if ($v['shop_type'] == '360buy') $is_jd_order = true;
                if ($v['createway'] == 'matrix') $arrShopOrderBn[$v['shop_id']][] = $v['order_bn'];
                $orderExtend = $this->getOrderExtend($v['order_id']);
                $receivable += $orderExtend[$v['order_id']]['receivable'];
                $totalAmount += $v['total_amount'];

                $order_bns[] = $v['order_bn'];
                $performance_type = $orderExtend[$v['order_id']]['extend_field']['performance_type'];
                $platform_logi_no = $orderExtend[$v['order_id']]['platform_logi_no'];
            }

            $arrDelivery[$key]['total_amount'] = $totalAmount;

            $arrDelivery[$key]['delivery_order'] = $deliveryOrder;
            $arrDelivery[$key]['receivable'] = $receivable;
            $arrDelivery[$key]['order_bns']      = $order_bns;
            $arrDelivery[$key]['shop']           = $this->getShop($value['shop_id']);
            $arrDelivery[$key]['platform_logi_no']= $platform_logi_no;
            $arrDelivery[$key]['performance_type']= $performance_type;
        }

        return $arrDelivery;
    }

    protected function getOrderExtend($arrOrderId) {
        $extend = app::get('ome')->model('order_extend')->getList('order_id,receivable,extend_field,platform_logi_no', array('order_id'=>$arrOrderId));

        $orderExtend = array();
        foreach($extend as $val) {
            if ($val['extend_field'] && is_string($val['extend_field'])) {
                $val['extend_field'] = json_decode($val['extend_field'], 1);
            }
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
