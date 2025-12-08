<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2015-12-23
 * @describe 处理快递单打印订单相关数据
 */
class logisticsmanager_print_data_order {
    private $mField = array(
        'is_cod',
        'order_id',
        'order_bn',
        'order_source',
        'shop_type',
        'cost_freight',
        'total_amount',
        'process_status',
        'ship_status',
        'mark_text',
        'custom_mark',
    );
    private $type;
    private $cfgPrintPkg;//开启打印捆绑商品的配置
    private $corp;
    private $arrDeliveryId;
    public $orders = array();
    
    /**
     * order
     * @param mixed $oriData ID
     * @param mixed $corp corp
     * @param mixed $field field
     * @param mixed $type type
     * @return mixed 返回值
     */

    public function order(&$oriData, $corp, $field, $type) {
        $pre = __FUNCTION__ . '.';
        $this->type = $type;
        $this->corp = $corp;
        $delivery_cfg = app::get('ome')->getConf('ome.delivery.status.cfg');
        $this->cfgPrintPkg = $delivery_cfg['set']['print_pkg_goods'];
        $deliveryOrder = app::get($type)->model('delivery_order')->getList('*', array('delivery_id' => array_keys($oriData)));
        $middle = array();
        $orderIds = array();
        $this->arrDeliveryId = array();
        foreach($deliveryOrder as $k => $val) {
            $this->arrDeliveryId[] = $val['delivery_id'];
            $middle[$val['delivery_id']][] = $val['order_id'];
            $orderIds[] = $val['order_id'];
        }
        $orderModel = app::get('ome')->model('orders');
        $strField = kernel::single('logisticsmanager_print_data')->getSelectField($this->mField, $field, $orderModel);
        $orderData = $orderModel->getList($strField, array('order_id'=>$orderIds));
        if(array_intersect($field, array('tax_no', 'tax_company'))) {
            $invoiceData = $this->__getInvoice($orderIds);
        }
        foreach($orderData as $row) {
            if($invoiceData && $invoiceData[$row['order_id']]) {
                $row = array_merge($row, $invoiceData[$row['order_id']]);
            }
            $this->orders[$row['order_id']] = $row;
        }
        foreach($oriData as $key => &$value) {
            foreach($field as $f) {
                if(isset($this->orders[$middle[$key][0]][$f])) {
                    $tmp = '';
                    foreach($middle[$key] as $orderId) {
                        $tmp .= $this->orders[$orderId][$f] . ',';
                    }
                    $value[$pre . $f] = trim($tmp, ',');
                } elseif(method_exists($this, $f)) {
                    $value[$pre . $f] = $this->$f($middle[$key], $key);
                } else {
                    $value[$pre . $f] = '';
                }
            }
        }
    }

    private function delivery_order_amount($orderIds, $deliveryId) {
        return $this->getDeliveryOrderAmount($orderIds);
    }

    private function delivery_order_amount_d($orderIds, $deliveryId) {
        return $this->getDeliveryOrderAmount($orderIds);
    }

    private function delivery_receivable($orderIds, $deliveryId) {
        return $this->getDeliveryReceivable($orderIds, $deliveryId);
    }

    private function delivery_receivable_d($orderIds, $deliveryId) {
        return $this->getDeliveryReceivable($orderIds);
    }

    private function cost_protect_sum($orderIds, $deliveryId) {
        if($this->corp['protect'] == 'false') {
            return '';
        }
        $orderAmount = $this->getDeliveryOrderAmount($orderIds);
        $costProtect = $orderAmount * $this->corp['protect_rate'];
        return max($costProtect, $this->corp['minprice']);
    }

    private function order_memo($orderIds, $deliveryId) {
        $markText = array();
        $markShowMethod = app::get('ome')->getConf('ome.order.mark');
        foreach($orderIds as $orderId) {
            $order = $this->orders[$orderId];
            if ($order['mark_text']) {
                $mark = unserialize($order['mark_text']);
                if (is_array($mark) || !empty($mark)){
                    if($markShowMethod == 'all'){
                        foreach ($mark as $im) {
                            $markText[] = $im['op_content'];
                        }
                    }else{
                        $mark = array_pop($mark);
                        $markText[] = $mark['op_content'];
                    }
                }
            }
        }
        return implode(',', $markText);
    }

    private function order_custom($orderIds, $deliveryId) {
        $customMark = array();
        $markShowMethod = app::get('ome')->getConf('ome.order.mark');
        foreach($orderIds as $orderId) {
            $order = $this->orders[$orderId];
            if ($order['custom_mark']) {
                $tmpCustomMark = unserialize($order['custom_mark']);
                if (is_array($tmpCustomMark) || !empty($tmpCustomMark)){
                    if($markShowMethod == 'all'){
                        foreach ($tmpCustomMark as $im) {
                            if($order['order_source'] == 'tbdx'){
                                $im['op_content']= $this->dealTBFXMemo($im['op_content']);
                                $customMark[] = $im['op_content'];
                            }else{
                                $customMark[] = $im['op_content'];
                            }
                        }
                    }else{
                        if($order['order_source'] == 'tbdx'){
                            $mark = array_pop($tmpCustomMark);
                            $memo['op_content']= $this->dealTBFXMemo($mark['op_content']);
                            $customMark[] = $memo['op_content'];
                        }else{
                            $mark = array_pop($tmpCustomMark);
                            $customMark[] = $mark['op_content'];
                        }
                    }
                }
            }
        }
        return implode(',', $customMark);
    }

    private function pkgbn_num($orderIds, $deliveryId) {
        $arrPkgBn = $this->dealObjectsData($orderIds, $deliveryId);
        if(empty($arrPkgBn)) {
            return '';
        }
        $ret = array();
        foreach($arrPkgBn as $k => $val) {
            $ret[$k] = $val['bn'] . '  x  ' . $val['quantity'];
        }
        return $ret;
    }

    private function pkg_productname_num($orderIds, $deliveryId) {
        $arrPkgBn = $this->dealObjectsData($orderIds, $deliveryId);
        if(empty($arrPkgBn)) {
            return '';
        }
        $ret = array();
        foreach($arrPkgBn as $k => $val) {
            $ret[$k] = $val['name'] . '  x  ' . $val['quantity'];
        }
        return $ret;
    }

    private function pkg_productname_bn_num($orderIds, $deliveryId) {
        $arrPkgBn = $this->dealObjectsData($orderIds, $deliveryId);
        if(empty($arrPkgBn)) {
            return '';
        }
        $ret = array();
        foreach($arrPkgBn as $k => $val) {
            $ret[$k] = $val['name'] . '  ' . $val['bn'] . '  x  ' . $val['quantity'];
        }
        return $ret;
    }
    
    private function pkgname_bn_spec_num($orderIds, $deliveryId) {
        if(!$this->cfgPrintPkg) {
            return '';
        }
        $arrPkgItem = array();
        $objData = $this->getObjects();
        $itemData = $this->getItems();
        foreach($orderIds as $orderId) {
            $this->dealSpiltQuantity($objData[$orderId], $itemData[$orderId], $this->orders[$orderId], $deliveryId);
            foreach($objData[$orderId] as $k => $obj) {
                $lastItem = end($itemData[$orderId][$k]);
                if($obj['obj_type'] != 'pkg' || $obj['quantity'] == 0 || $lastItem['delete'] != 'false') {
                    continue;
                }
                foreach ($itemData[$orderId][$k] as $iKey => $iValue) {
                    if($arrPkgItem[$obj['bn']][$iValue['bn']]) {
                        $arrPkgItem[$obj['bn']][$iValue['bn']]['nums'] += $iValue['nums'];
                    } else {
                        $arrPkgItem[$obj['bn']][$iValue['bn']] = array('pkgName' => $obj['name'], 'pName' => $iValue['name'], 'addon' => $iValue['addon'], 'nums' => $iValue['nums']);
                    }
                }
            }
        }
        $ret = array();
        foreach($arrPkgItem as $item) {
            foreach($item as $val) {
                $ret[] = $val['pkgName'] . '  ' . $val['pName'] . '  '. $val['addon'] . ' x ' . $val['nums'];
            }
        }
        return $ret;
    }

    private function normal_good($orderIds, $deliveryId) {
        $arrGoodBn = $this->dealItemsData($orderIds, $deliveryId);
        if(empty($arrGoodBn)) {
            return '';
        }
        $ret = array();
        foreach($arrGoodBn as $k => $val) {
            $ret[$k] = $val['bn'] . '  x  ' . $val['nums'];
        }
        return $ret;
    }
    #应收金额
    private function receivable_amount($orderIds, $deliveryId){
        $receivable_amount = 0;
        foreach($orderIds as $orderId) {
            $order = $this->orders[$orderId];
            if (!empty($order['is_cod']) && ($order['is_cod']!='false') ) {
                $receivable_amount += $order['total_amount'];
            }
        }
        return $receivable_amount;
    }

    private function normal_productname_num($orderIds, $deliveryId) {
        $arrGoodBn = $this->dealItemsData($orderIds, $deliveryId);
        if(empty($arrGoodBn)) {
            return '';
        }
        $ret = array();
        foreach($arrGoodBn as $k => $val) {
            $ret[$k] = $val['name'] . '  x  ' . $val['nums'];
        }
        return $ret;
    }

    private function normal_productname_bn_num($orderIds, $deliveryId) {
        $arrGoodBn = $this->dealItemsData($orderIds, $deliveryId);
        if(empty($arrGoodBn)) {
            return '';
        }
        $ret = array();
        foreach($arrGoodBn as $k => $val) {
            $ret[$k] = $val['name'] . '  ' . $val['bn'] . '  x  ' . $val['nums'];
        }
        return $ret;
    }

    private function normal_productname_spec_num($orderIds, $deliveryId) {
        $arrGoodBn = $this->dealItemsData($orderIds, $deliveryId);
        if(empty($arrGoodBn)) {
            return '';
        }
        $ret = array();
        foreach($arrGoodBn as $k => $val) {
            $ret[$k] = $val['name'] . '  ' . $val['addon'] . '  x  ' . $val['nums'];
        }
        return $ret;
    }

    private function total_product_weight_g($orderIds, $deliveryId) {
        return $this->getTotalProductWeight($orderIds, $deliveryId);
    }

    private function total_product_weight_kg($orderIds, $deliveryId) {
        return $this->getTotalProductWeight($orderIds, $deliveryId);
    }

    private function order_oid($orderIds, $deliveryId) {
        $objData = $this->getObjects();

        $oid = array ();
        foreach($orderIds as $orderId) {
            foreach ($objData[$orderId] as $value) {
                $oid[] = $value['oid'];
            }
        }

        return implode('|', array_unique($oid));
    }
    
    private function dealObjectsData($orderIds, $deliveryId) {
        if(!$this->cfgPrintPkg) {
            return '';
        }
        $arrPkgBn = array();
        $objData = $this->getObjects();
        $itemData = $this->getItems();
        foreach($orderIds as $orderId) {
            $this->dealSpiltQuantity($objData[$orderId], $itemData[$orderId], $this->orders[$orderId], $deliveryId);
            foreach($objData[$orderId] as $k => $obj) {
                $lastItem = end($itemData[$orderId][$k]);
                if($obj['obj_type'] != 'pkg' || $obj['quantity'] == 0 || $lastItem['delete'] != 'false') {
                    continue;
                }
                if($arrPkgBn[$obj['bn']]) {
                    $arrPkgBn[$obj['bn']]['quantity'] += $obj['quantity'];
                } else {
                    $arrPkgBn[$obj['bn']] = array('bn'=>$obj['bn'], 'name'=>$obj['name'], 'quantity'=>$obj['quantity']);
                }
            }
        }
        return $arrPkgBn;
    }

    private function dealItemsData($orderIds, $deliveryId) {
        $arrBn = array();
        $objData = $this->getObjects();
        $itemData = $this->getItems();
        foreach($orderIds as $orderId) {
            $this->dealSpiltQuantity($objData[$orderId], $itemData[$orderId], $this->orders[$orderId], $deliveryId);
            foreach($objData[$orderId] as $k => $obj) {
                foreach ($itemData[$orderId][$k] as $iKey => $item) {
                    if ($obj['obj_type'] == 'pkg' || $item['nums'] == 0 || $item['delete'] != 'false') {
                        continue;
                    }
                    if ($arrBn[$item['bn']]) {
                        $arrBn[$item['bn']]['nums'] += $item['nums'];
                    } else {
                        $arrBn[$item['bn']] = array('bn' => $item['bn'], 'name' => $item['name'], 'nums' => $item['nums'], 'addon' => $item['addon']);
                    }
                }
            }
        }
        return $arrBn;
    }

    private function dealSpiltQuantity(&$objOrderData, &$itemOrderData, $order, $deliveryId) {
        if($this->type == 'brush') {//刷单没有拆单，不需要拆单数据转换
            return true;
        }
        $is_split = $this->isSplitOrder($order);
        if($is_split) {
            $deliveryItemDetail = $this->getDeliveryItemDetail($deliveryId);
            $delivery_detail = array();
            foreach ($deliveryItemDetail[$order['order_id']] as $key => $val) {
                $delivery_detail[$val['order_obj_id']][$val['order_item_id']] = $val['number'];
            }
            foreach ($objOrderData as $objK => $objV) {
                foreach($itemOrderData[$objK] as $itemK => $itemV) {
                    $get_dly_number      = intval($delivery_detail[$objK][$itemK]);#发货单_商品数量
                    if($objV['obj_type'] == 'pkg') { //捆绑商品不能拆开发货
                        $get_obj_quantity    = intval($objV['quantity']);
                        $get_item_nums       = intval($itemV['nums']);
                        $objOrderData[$objK]['quantity'] = intval($get_dly_number / ($get_item_nums / $get_obj_quantity));
                        $itemOrderData[$objK][$itemK]['nums'] = $get_dly_number;
                    } else {
                        $objOrderData[$objK]['quantity'] = $get_dly_number;
                        $itemOrderData[$objK][$itemK]['nums'] = $get_dly_number;
                    }
                }
            }
        }
    }

    private function isSplitOrder($order) {
        static $isSplit = array();
        if(isset($isSplit[$order['order_id']])) {
            return $isSplit[$order['order_id']];
        }
        if($order['process_status'] == 'splitting' || $order['ship_status'] == '2') {
            //[拆单]订单是否为部分拆分OR部分发货
            $isSplit[$order['order_id']] = true;
        } else {
            //[拆单]订单是否有多个发货单
            $oDelivery = app::get($this->type)->model('delivery');
            $order_delivery_count    = $oDelivery->validDeiveryByOrderId($order['order_id'], true);
            $isSplit[$order['order_id']]                = ($order_delivery_count > 1 ? true : false);
        }
        return $isSplit[$order['order_id']];
    }

    //通过发货单ID获取delivery_items_detail表的数据
    private function getDeliveryItemDetail($deliveryId) {
        static $did = array();
        if(!empty($did)) {
            return $did[$deliveryId];
        }
        $deliItemDetailModel = app::get($this->type)->model('delivery_items_detail');
        $deliItemDetailList = $deliItemDetailModel->getList('delivery_id, order_id, order_obj_id, order_item_id, number', array('delivery_id' => $this->arrDeliveryId));
        foreach($deliItemDetailList as $val) {
            $did[$val['delivery_id']][$val['order_id']][] = $val;
        }
        return $did[$deliveryId];
    }

    //获取数据表 order_objects 的数据
    private function getObjects() {
        static $objData = array();
        if(!empty($objData)) {
            return $objData;
        }
        $orderIds = array_keys($this->orders);
        $orderObjects = app::get('ome')->model('order_objects');
        $data = $orderObjects->getList('order_id,obj_id,obj_type,bn,name,quantity,oid',array('order_id'=>$orderIds));
        $cfgShip = kernel::single('ome_delivery_cfg')->getValue('ome_delivery_is_printship');
        $pBn = array();
        foreach ($data as $val) {
            if (!in_array($val['bn'], $pBn)) {
                $pBn[] = $val['bn'];
            }
        }
        $pkgInfo = $this->getPkgGoods($pBn);
        $productInfo = $this->getProductName($pBn);
        foreach($data as $row) {
            if($row['obj_type'] == 'pkg') {
                $row['weight'] = $pkgInfo[$row['bn']]['weight'];
                if($cfgShip == '2') {
                    $row['name'] = $pkgInfo[$row['bn']]['name'];
                }
            } else {
                $row['weight'] = $productInfo[strtoupper($row['bn'])]['weight'];
                if($cfgShip == '2') {
                    $row['name'] = $productInfo[strtoupper($row['bn'])]['name'];
                }
            }
            $objData[$row['order_id']][$row['obj_id']] = $row;
        }
        return $objData;
    }

    private function getPkgGoods($pkgBn) {
        $pkgInfo = array();
        
        return $pkgInfo;
    }

    private function getProductName($pBn) {
        $pInfo = array();
        $pModel = app::get('ome')->model('products');
        $pData = $pModel->getList('bn, name, weight', array('bn'=>$pBn));
        foreach($pData as $val) {
            $pInfo[strtoupper($val['bn'])] = $val;
        }
        return $pInfo;
    }

    //获取数据表 order_items 的数据, 以objects主键为键值
    private function getItems() {
        static $itemData = array();
        if(!empty($itemData)) {
            return $itemData;
        }
        $orderIds = array_keys($this->orders);
        $orderItems = app::get('ome')->model('order_items');
        $data = $orderItems->getList('item_id,order_id,obj_id,bn,`name`,addon,nums,`delete`',array('order_id'=>$orderIds));
        $arrProductBn = array();
        foreach($data as $val) {
            if(!in_array($val['bn'], $arrProductBn)) {
                $arrProductBn[] = $val['bn'];
            }
        }
        $arrProduct = $this->getProductName($arrProductBn);
        $cfgShip = kernel::single('ome_delivery_cfg')->getValue('ome_delivery_is_printship');
        foreach($data as $row) {
            $faddon = array ();
            $item_addon = @unserialize($row['addon']);
            if ($item_addon['product_attr'] && is_array($item_addon['product_attr'])) {
                foreach ($item_addon['product_attr'] as $attrs) {
                    $faddon[] = $attrs['value'];
                }
            }
            $row['addon'] = implode('、',$faddon);
            $row['weight'] = $arrProduct[strtoupper($row['bn'])]['weight'];
            if($cfgShip == '2') {
                $row['name'] = $arrProduct[strtoupper($row['bn'])]['name'];
            }
            $itemData[$row['order_id']][$row['obj_id']][$row['item_id']] = $row;
        }
        return $itemData;
    }
    #处理淘宝分销类型订单备注
    private function dealTBFXMemo($memo = null){
        $reg = '/(买家|分销商|系统).*\(\d{4}-\d{1,2}-\d{1,2}\s{0,}\d{1,2}:\d{1,2}:\d{1,2}\)\s{0,}\(.*\)\s{0,}[:|：]/isU';
        $arrMemo = preg_split($reg, $memo);
        $index = count($arrMemo) - 1;
        return '留言：' . $arrMemo[$index];
    }

    private function getDeliveryReceivable($orderIds) {
        $deliveryReceivable = 0;
        $extendData = $this->getExtend();
        foreach($orderIds as $orderId) {
            $deliveryReceivable += $extendData[$orderId]['receivable'];
        }
        return $deliveryReceivable;
    }

    //获取表 order_extend 的数据
    private function getExtend() {
        static $extendData = array();
        if(!empty($extendData)) {
            return $extendData;
        }
        $orderIds = array_keys($this->orders);
        $orderExtendObj = app::get('ome')->model('order_extend');
        $orderExtends = $orderExtendObj->getList('order_id, receivable',array('order_id'=>$orderIds));
        foreach($orderExtends as $row) {
            $extendData[$row['order_id']] = $row;
        }
        return $extendData;
    }
    
    private function getDeliveryOrderAmount($orderIds) {
        $deliveryAmount = 0;
        foreach($orderIds as $orderId) {
            $order = $this->orders[$orderId];
            if($order['order_source'] == 'tbdx' && $order['shop_type'] == 'taobao'){
                $tbfxData = $this->getTBFXData();
                $deliveryAmount += ($order['cost_freight']+$tbfxData[$orderId]['total_buyer_payment']);
            }else{
                $deliveryAmount += $order['total_amount'];
            }
        }
        return $deliveryAmount;
    }

    //获取表 tbfx_order_items 的累加数据
    private function getTBFXData() {
        static $tbfxData = array();
        if(!empty($tbfxData)) {
            return $tbfxData;
        }
        $orderIds = array_keys($this->orders);
        $tbfxitemObj = app::get('ome')->model('tbfx_order_items');
        $sql = 'select order_id,SUM(buyer_payment) AS total_buyer_payment from ' . $tbfxitemObj->table_name(true) . ' where ' . $tbfxitemObj->_filter(array('order_id'=>$orderIds)) . ' group by order_id';
        $data = $tbfxitemObj->db->select($sql);
        foreach($data as $row) {
            $tbfxData[$row['order_id']] = $row;
        }
        return $tbfxData;
    }

    private function getTotalProductWeight($orderIds, $deliveryId) {
        $totalPW = 0;
        $objData = $this->getObjects();
        $itemData = $this->getItems();
        foreach($orderIds as $orderId) {
            $this->dealSpiltQuantity($objData[$orderId], $itemData[$orderId], $this->orders[$orderId], $deliveryId);
            foreach($objData[$orderId] as $k => $obj) {
                if($obj['obj_type'] == 'pkg') {
                    $lastItem = end($itemData[$orderId][$k]);
                    if ($obj['quantity'] == 0 || $lastItem['delete'] != 'false') {
                        continue;
                    }
                    $totalPW += $obj['quantity'] * $obj['weight'];
                } else {
                    foreach ($itemData[$orderId][$k] as $iKey => $item) {
                        if ($item['nums'] == 0 || $item['delete'] != 'false') {
                            continue;
                        }
                        $totalPW += $item['nums'] * $item['weight'];
                    }
                }
            }
        }
        return $totalPW;
    }

    private function __getInvoice($orderIds) {
        $invoice = app::get('ome')->model('order_invoice')->getList('order_id, tax_title as tax_company, tax_no', array('order_id'=>$orderIds));
        $invoiceData = array();
        foreach($invoice as $val) {
            $invoiceData[$val['order_id']] = $val;
        }
        return $invoiceData;
    }
}