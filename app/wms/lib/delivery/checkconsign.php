<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 此类处理发货单校验及发货
 *
 * @category
 * @package
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class wms_delivery_checkconsign
{
    /**
     * 需要处理的发货单
     * 
     * @var array
     * */

    private  $__delivery = array();

    /**
     * 操作人
     * 
     * @var array
     * */
    private $__user = array();

    /**
     * 发货单对应的订单
     * 
     * @var array
     * */
    private $__delivery_order = array();

        /**
     * 设置_delivery
     * @param mixed $delivery_id ID
     * @return mixed 返回操作结果
     */
    public function set_delivery($delivery_id)
    {
        $this->__delivery = array();
        $rows = app::get('wms')->model('delivery')->getList('*',array('delivery_id'=>$delivery_id));
        foreach ($rows as $row) {
            $this->__delivery[$row['delivery_id']] = $row;
        }
        
        return $this;
    }

    #通过运单号来获取发货单
    /**
     * 设置_delivery_by_waybill
     * @param mixed $waybill waybill
     * @return mixed 返回操作结果
     */
    public function set_delivery_by_waybill($waybill) {
        $this->__delivery = array();
        $rows = app::get('wms')->model('delivery')->getList('*',array('logi_no'=>$waybill));
        foreach ($rows as $row) {
            $this->__delivery[$row['delivery_id']] = $row;
        }
        $billData = app::get('wms')->model('delivery_bill')->getList('delivery_id',array('logi_no'=>$waybill));
        if($billData) {
            $arrDeliveryId = array();
            foreach($billData as $bill) {
                if(!$this->__delivery[$bill['delivery_id']]) {
                    $arrDeliveryId[$bill['delivery_id']] = $bill['delivery_id'];
                }
            }
            if($arrDeliveryId) {
                $rows = app::get('wms')->model('delivery')->getList('*', array('delivery_id' => $arrDeliveryId));
                foreach ($rows as $row) {
                    $this->__delivery[$row['delivery_id']] = $row;
                }
            }
        }
        return $this;
    }

    #通过运单号来获取发货单
    /**
     * 设置_delivery_by_logi
     * @param mixed $logi_no logi_no
     * @return mixed 返回操作结果
     */
    public function set_delivery_by_logi($logi_no) {
        $this->__delivery = array();
        $deliModel = app::get('wms')->model('delivery');
        $dlyBillLib = kernel::single('wms_delivery_bill');
        $delivery_id = $dlyBillLib->getDeliveryIdByPrimaryLogi($logi_no);
        if(!is_null($delivery_id)){
            $primary = true;
            $this->__delivery[$delivery_id] = $deliModel->dump(array('delivery_id' => $delivery_id),'*',array('delivery_items'=>array('*')));
        }else{
            $delivery_id = $dlyBillLib->getDeliveryIdBySecondaryLogi($logi_no);
            if(!is_null($delivery_id)){
                $secondary = true;
                $this->__delivery[$delivery_id] = $deliModel->dump(array('delivery_id' => $delivery_id),'*',array('delivery_items'=>array('*')));
            }
        }
        $this->__delivery[$delivery_id]['logi_no'] = $logi_no;
        return $this;
    }

    /**
     * 设置_user
     * @param mixed $user_id ID
     * @return mixed 返回操作结果
     */
    public function set_user($user_id)
    {
        $sysUser = kernel::single('ome_func')->get_system();
        if($sysUser['op_id'] == $user_id) {
            $this->__user['super'] = 1;
        }else{
            $this->__user = kernel::single('ome_func')->setUser($user_id);
        }
        return $this;
    }


    private function _get_delivery_items($delivery_id)
    {
        static $delivery_items;

        if (isset($delivery_items[$delivery_id])) return $delivery_items[$delivery_id];

        foreach ($this->__delivery as $delivery) {
            $delivery_items[$delivery['delivery_id']] = array();
        }
        
        $deliveryItemModel = app::get('wms')->model('delivery_items');
        $rows = $deliveryItemModel->getList('*',array('delivery_id'=>array_keys($delivery_items)));
        foreach ($rows as $row) {
            $delivery_items[$row['delivery_id']][$row['item_id']] = $row;
        }

        return $delivery_items[$delivery_id];
    }

    private function _get_delivery_items_detail($delivery_id)
    {
        static $delivery_items_detail;

        if (isset($delivery_items_detail[$delivery_id])) return $delivery_items_detail[$delivery_id];

        foreach ($this->__delivery as $delivery) {
            $delivery_items_detail[$delivery['delivery_id']] = array();
        }

        $rows = app::get('ome')->model('delivery_items_detail')->getList('*',array('delivery_id'=>array_keys($delivery_items_detail)));
        foreach ($rows as $row) {
            $delivery_items_detail[$row['delivery_id']][$row['item_detail_id']] = $row;
        }

        return $delivery_items_detail[$delivery_id];
    }

    private function _get_children_delivery($delivery_id)
    {
        static $children_delivery;

        if (isset($children_delivery[$delivery_id])) return $children_delivery[$delivery_id];

        foreach ($this->__delivery as $delivery) {
            $children_delivery[$delivery['delivery_id']] = array();
        }

        $rows = app::get('wms')->model('delivery')->getList('*',array('parent_id'=>array_keys($children_delivery)));
        foreach ($rows as $row) {
            $children_delivery[$row['parent_id']][$row['delivery_id']] = $row;
        }

        return $children_delivery[$delivery_id];
    }

    private function _get_delivery_order($delivery_id)
    {
        static $delivery_order;

        if (isset($delivery_order[$delivery_id])) return $delivery_order[$delivery_id];

        foreach ($this->__delivery as $delivery) {
            $delivery_order[$delivery['delivery_id']] = array();
        }

        $rows = app::get('ome')->model('delivery_order')->getList('*',array('delivery_id'=>array_keys($delivery_order)));
        foreach ($rows as $row) {
            $delivery_order[$row['delivery_id']][$row['order_id']] = &$orders[$row['order_id']];
        }

        $rows = app::get('ome')->model('orders')->getList('*',array('order_id'=>array_keys($orders)));
        foreach ($rows as $row) {
            $orders[$row['order_id']] = $row;
        }

        return $delivery_order[$delivery_id];
    }

    private function _get_owner_branch()
    {
        static $branches;

        if (isset($branches)) return $branches;

        $rows = app::get('ome')->model('branch_ops')->getList('branch_id',array('op_id'=>intval($this->__user['user_id']) ));

        $branches = array();
        foreach ($rows as $row) {
            $branches[] = $row['branch_id'];
        }

        return $branches;
    }

    /**
     * 校验数据，验证数据是否可用
     * @param $deliveryId
     * @param $errMsg
     * @return bool
     */
    public function check_data($deliveryId,&$errMsg){
        $delivery = $this->__delivery[$deliveryId];

        if ($this->_verify_check($delivery['delivery_id'],$errMsg)) {
            return true;
        }else{
            return false;
        }
    }

    #校验 (开启校验即发货 会自动发货)
    public function check($delivery_id,$checkFrom,&$errmsg)
    {
        $operationModel = app::get('ome')->model('operation_log');
        $deliveryModel  = app::get('wms')->model('delivery');

        $delivery = $this->__delivery[$delivery_id];

        if (!$this->_verify_check($delivery['delivery_id'],$errmsg)) {
            return false;
        }
    
        // 相应的发货明细置检验
        $filter_delivery_id = array($delivery['delivery_id']);
        if ($delivery['is_bind'] == 'true') {
            $children_delivery = $this->_get_children_delivery($delivery['delivery_id']);
            foreach ($children_delivery as $cd) {
                $filter_delivery_id[] = $cd['delivery_id'];
            }
        }

        $sql = 'UPDATE  `sdb_wms_delivery_items` set `verify`="true",`verify_num`=`number` WHERE delivery_id IN(%s) AND `verify`="false"';
        kernel::database()->exec(sprintf($sql,implode(',',$filter_delivery_id)));
        $affect_row = kernel::database()->affect_row();
        if (!is_numeric($affect_row) || $affect_row <=0) {
            $errmsg = sprintf('发货单【%s】明细检验失败',$delivery['delivery_bn']); return false;
        }
        $boolStatus = $delivery['bool_status'] | wms_delivery_bool_status::__CHECK_CODE;
        $filterSql = '!(bool_status & ' . wms_delivery_bool_status::__CHECK_CODE . ')';
        $upData = array('verify'=>'true','bool_status'=>$boolStatus);
        if(preg_match('/^VLN\d+F$/', $delivery['logi_no'])) { //虚拟发货
            $upData['expre_status'] = 'true';
            $this->__delivery[$delivery_id]['expre_status'] = 'true';
        }
        // 校验完成
        $affect_row = $deliveryModel->update($upData,array('delivery_id'=>$filter_delivery_id, 'verify'=>'false','filter_sql'=>$filterSql, 'status' => '0'));

        if (!is_numeric($affect_row)) {
            $errmsg = sprintf('发货单【%s】检验失败',$delivery['delivery_bn']); return false;
        }

        $this->__delivery[$delivery_id]['verify'] = 'true';

        // 绩效
        foreach(kernel::servicelist('tgkpi.pick') as $object){
            if(method_exists($object,'finish_pick')){
                $object->finish_pick($delivery['delivery_id']);
            }
        }

        $logmsg = '发货单' . $checkFrom . '校验完成';
        $operationModel->write_log('delivery_check@ome', $delivery['delivery_id'], $logmsg);
        // $rb = app::get('ome')->getConf('ome.delivery.back_node');
        // if($rb == 'check') {
        //     kernel::single('ome_event_trigger_shop_delivery')->delivery_confirm_send($delivery['delivery_id']);
        // }
        $group_consign   = app::get('ome')->getConf('ome.delivery.check_delivery');
        if( in_array($checkFrom,array('整单','逐个','PDA','单品')) ) {
            $delivery_weight =  app::get('ome')->getConf('ome.delivery.weight');
            if($delivery_weight == 'on' || $delivery['expre_status'] == 'false') {
                $group_consign = 'off';
            }
        }
        $isWmsBranch = kernel::single('ome_branch_type')->isWmsBranch($delivery['branch_id']);
        if ((!$isWmsBranch && $group_consign == 'on') || preg_match('/^VLN\d+F$/', $delivery['logi_no'])) {

            // 压测站点不自动发货
            if ($_SERVER['SERVER_NAME'] != constant("_STRESSTEST_DOMAIN")) {
                kernel::single('ome_delivery_consign')->saveBatchConsign(array($delivery['logi_no']));
            }
        }

        return true;
    }

    #发货
    /**
     * consign
     * @param mixed $logi_no logi_no
     * @param mixed $errmsg errmsg
     * @param mixed $deliFrom deliFrom
     * @param mixed $actualWeight actualWeight
     * @return mixed 返回值
     */
    public function consign($logi_no,&$errmsg,$deliFrom='', $actualWeight=0)
    {
        if(empty($logi_no)) {
            $errmsg = '运单号不能为空';return false;
        }
        $logi_no = strtoupper($logi_no);
        $deliveryModel  = app::get('wms')->model('delivery');
        $billModel = app::get('wms')->model('delivery_bill');
        $operationModel = app::get('ome')->model('operation_log');

        foreach ($this->__delivery as $d) {
            if (strtoupper($d['logi_no']) == $logi_no) {
                $delivery = $d;break;
            }
        }

        $patch = false;
        if (!$delivery) {
            // 判断是不是子包裹
            $deliBill = $billModel->dump(array('logi_no'=>$logi_no));
            if (!$deliBill) {
                $errmsg = sprintf('运单号【%s】不存在',$logi_no);return false;
            }

            if ($deliBill['status'] == '1') {
                $errmsg = sprintf('运单号【%s】已发货',$logi_no);return false;
            }

            $delivery = $this->__delivery[$deliBill['delivery_id']];
            $patch = true;
        }

        if (!$this->_verify_consign($delivery['delivery_id'],$errmsg)) {
            return false;
        }

        // 如果实际发送包裹和预发包裹相同，直接发货
        if ($delivery['logi_number'] > 1 && $patch == false && $delivery['logi_number'] != $delivery['delivery_logi_number']) {
            $billModel = app::get('wms')->model('delivery_bill');
            $bill = $billModel->count(array('delivery_id'=>$delivery['delivery_id'],'status'=>'1'));

            if ($delivery['delivery_logi_number'] > $bill) {
                $errmsg = sprintf('发货单主单【%s】已发货',$delivery['delivery_bn']);
                return false;
            }
        }
        $deliUpdate = array();
        if ($patch) {
            list($mainload,$ship_area,$area_id) = explode(':',$delivery['ship_area']);

            $weight = $actualWeight ? $actualWeight : app::get('ome')->getConf('ome.delivery.minWeight');
            $delivery_cost_actual = $deliveryModel->getDeliveryFreight($area_id,$delivery['logi_id'],floatval($weight));
            $data = array(
                'status'               => '1',
                'weight'               => floatval($weight),
                'delivery_cost_actual' => $delivery_cost_actual,
                'delivery_time'        => time(),
            );
            $rs = $billModel->update($data,array('logi_no'=>$logi_no));
            if(is_bool($rs)) {
                $errmsg = sprintf('运单号【%s】已发货',$logi_no);
                return false;
            }
            $logstr = $deliFrom . '发货,单号:'.$logi_no;
            $operationModel->write_log('delivery_bill_express@ome', $delivery['delivery_id'], $logstr,time());

        } else {
            $this->__delivery[$delivery['delivery_id']]['weight'] = $actualWeight ? $actualWeight : $this->_get_delivery_weight($delivery['delivery_id']);
            $weight = $this->__delivery[$delivery['delivery_id']]['weight'];
            $deliUpdate['weight'] = $weight;
        }
        //-- 更新发货包裹数
        $this->__delivery[$delivery['delivery_id']]['delivery_logi_number'] ++;
        $delivery_logi_number = $this->__delivery[$delivery['delivery_id']]['delivery_logi_number'];
        $deliUpdate['delivery_logi_number'] = $delivery_logi_number;
        $deliveryModel->update($deliUpdate,array('delivery_id'=>$delivery['delivery_id'],'delivery_logi_number|lthan'=>$delivery['logi_number']));
        #报警发货处理
        if($_POST['warn_status']=='1'){
            $operationModel->write_log('delivery_weightwarn@ome', $delivery['delivery_id'],'物流单号:'.$logi_no.',仍然发货（称重为：'.$weight.'g）');
        }
        if ($delivery_logi_number == $delivery['logi_number'] || $delivery['delivery_logi_number']==$delivery['logi_number']) {
            $consignRs = $this->_order_to_consign($delivery['delivery_id'], $errmsg);
            if($consignRs) {
                $consignRs = kernel::single('wms_delivery_process')->consignDelivery($delivery['delivery_id'], $this->__delivery[$delivery['delivery_id']]['weight'], $errmsg, false, $deliFrom);
                $this->_delete_order_in_consign($delivery['delivery_id']);
            }
            if (!$consignRs) {
                $this->__delivery[$delivery['delivery_id']]['delivery_logi_number']--;
                $deliUpdate = array(
                    'delivery_logi_number'=>$this->__delivery[$delivery['delivery_id']]['delivery_logi_number'],
                );
                $deliveryModel->update($deliUpdate,array('delivery_id'=>$delivery['delivery_id'],'delivery_logi_number|sthan'=>$delivery['logi_number']));
                return false;
            }
        }
        
        return true;
    }

    /**
     * 获取发货单重量
     * 生成发货单也调用该方法
     * 
     * @return void
     * @author 
     * */
    public function _get_delivery_weight($delivery_id)
    {
        $weight = 0;

        $delivery_items_detail = $this->_get_delivery_items_detail($delivery_id);

        foreach ($delivery_items_detail as $detail) {
            if ($detail['item_type'] != 'pkg') {
                $unit_weight = $this->_get_product_weight($delivery_id,$detail['product_id']);

                if (1!=bccomp((float)$unit_weight,0,3)) {
                    $weight = 0; break ;
                }

                $weight += $unit_weight * $detail['number'];

            } else {

                $pkgweight = $this->_get_pkg_weight($delivery_id,$detail['order_obj_id'],$detail['product_id']);

                if (1!=bccomp((float)$pkgweight,0,3)) {
                    $unit_weight = $this->_get_product_weight($delivery_id,$detail['product_id']);
                
                    if (1!=bccomp((float)$unit_weight,0,3)) {
                        $weight = 0; break ;
                    }

                    $weight += $unit_weight * $detail['number'];
                } else {
                    $weight += $pkgweight;
                }

            }
        }

        $minWeight = app::get('ome')->getConf('ome.delivery.minWeight');
        
        // 如果多包裹，扣掉子包裹重量
        if ($this->__delivery[$delivery_id]['logi_number'] > 1) {
            $weight = bcsub($weight,bcmul($minWeight,($this->__delivery[$delivery_id]['logi_number']-1),3),3);
        }

        $weight = 1!=bccomp((float)$weight,0,3) ? $minWeight : round((float)$weight,3);
        return $weight;
    }

    /**
     * 获取发货单重量明细
     * 
     * @return void
     * @author 
     * */
    public function get_delivery_weight_detail($delivery_id)
    {
        $delivery_items_detail = $this->_get_delivery_items_detail($delivery_id);
        $delivery_items = $this->_get_delivery_items($delivery_id);
        
        foreach ($delivery_items_detail as $key => $detail) {
            $delivery_items_detail[$key]['name'] = $delivery_items[$detail['delivery_item_id']]['product_name'];

            if ($detail['item_type'] != 'pkg') {
                $unit_weight = $this->_get_product_weight($delivery_id,$detail['product_id']);

                $delivery_items_detail[$key]['weight'] = $unit_weight * $detail['number'];

            } else {
                $pkgweight = $this->_get_pkg_weight($delivery_id,$detail['order_obj_id'],$detail['product_id']);

                $delivery_items_detail[$key]['weight'] = $pkgweight;
            }
        }

        return $delivery_items_detail;
    }

    private function _get_product_weight($delivery_id,$product_id)
    {
        static $products;

        if (isset($products[$product_id])) return $products[$product_id]['weight'];

        $delivery_items_detail = $this->_get_delivery_items_detail($delivery_id);

        foreach ($delivery_items_detail as $detail) {
            $products[$detail['product_id']] =  array();
        }

        if (!$products) return 0;

        $rows = app::get('ome')->model('products')->getList('product_id,weight',array('product_id'=>array_keys($products)));
        foreach ($rows as $row) {
            $products[$row['product_id']] = $row;
        }

        return $products[$product_id]['weight'];
    }

    private function _get_pkg_weight($delivery_id,$order_obj_id,$product_id)
    {
        static $products;

        if (isset($products[$delivery_id][$order_obj_id][$product_id])) return $products[$delivery_id][$order_obj_id][$product_id]['weight'];

        foreach ($this->_get_delivery_items_detail($delivery_id) as $detail) {
            if ($detail['item_type'] == 'pkg') $products[$delivery_id][$detail['order_obj_id']][$detail['product_id']] = array();
        }

        if (!$products[$delivery_id]) return 0;

        $rows = app::get('ome')->model('order_objects')->getList('order_id,obj_id,bn',array('obj_id'=>array_keys($products[$delivery_id]),'obj_type'=>'pkg'));
        if (!$rows) return 0;
        
        $order_objects = array();
        foreach ($rows as $row) {
            $order_objects[$row['obj_id']] = &$format_pkg[$row['bn']];
        }

        $rows = array();
        $pkg_goods = array();
        foreach ($rows as $row) {
            $pkg_goods[$row['goods_id']] = $row;
        }

        $rows = array();
        if (!$rows) return 0;

        foreach ($rows as $row) {
            $pkg_goods[$row['goods_id']]['products'][$row['product_id']] = $row;

            $pkg_goods[$row['goods_id']]['itemnum'] += $row['pkgnum'];
        }

        // 计算/绑定不能单独拆，以组为单位
        foreach ($pkg_goods as $goods_id => $g) {

            $agv = $g['itemnum']>0? sprintf('%.3f',$g['weight']/$g['itemnum']):0;
            $c = count($g['products']); $i=1; $left_weight = $g['weight'];
            foreach ($g['products'] as $pid=>$p) {
        
                $format_pkg[$g['pkg_bn']][$pid]['weight'] = $c==$i ? $left_weight : $agv * $p['pkgnum'];
                $format_pkg[$g['pkg_bn']][$pid]['pkgnum'] = $p['pkgnum'];

                $left_weight -= $agv * $p['pkgnum'];

                $i++;
            }
        }

        foreach ($this->_get_delivery_items_detail($delivery_id) as $detail) {
            if ($detail['item_type'] == 'pkg') {
                $weight = $order_objects[$detail['order_obj_id']][$detail['product_id']]['weight'];
                $pkgnum = $order_objects[$detail['order_obj_id']][$detail['product_id']]['pkgnum'];

                $products[$delivery_id][$detail['order_obj_id']][$detail['product_id']]['weight'] = $pkgnum>0 ? $weight * $detail['number']/$pkgnum : 0;
            } 
        }

        return $products[$delivery_id][$order_obj_id][$product_id]['weight'];
    }

    # 发货单发货验证
        /**
     * _verify_consign
     * @param mixed $delivery_id ID
     * @param mixed $errmsg errmsg
     * @return mixed 返回值
     */
    public function _verify_consign($delivery_id,&$errmsg)
    {
        $delivery = $this->__delivery[$delivery_id];

        if (!$delivery) {
            $errmsg = '发货单不存在';return false;
        }

        if (!$delivery['logi_no']) {
            $errmsg = sprintf('发货单【%s】缺少运单号',$delivery['delivery_bn']);return false;
        }

        if ($delivery['status'] == 'succ') {
            $errmsg = sprintf('发货单【%s】已发货',$delivery['delivery_bn']);
            return false;
        }

        if ($delivery['expre_status'] == 'false') {
            $errmsg = sprintf('发货单【%s】未打印快递单', $delivery['delivery_bn']);
            return false;
        }

        // 判断是否超管
        if ($this->__user && !$this->__user['super'] && !in_array($delivery['branch_id'], $this->_get_owner_branch())) {
            $errmsg = '没有仓库权限，无权发货';
            return false;
        }

        $rs = $this->existDeliveryStatus($delivery_id,$errmsg);
        if (!$rs) {
            return false;
        }

        $rs = $this->existOrderPause($delivery_id,$errmsg);
        if (!$rs) {
            return false;
        }

        if ($delivery['status'] == 'back'){
            $errmsg = sprintf('发货单【%s】已打回',$delivery['delivery_bn']);
            return false;
        }
        if ($delivery['verify'] == 'false'){
            $errmsg = sprintf('发货单【%s】未校验',$delivery['delivery_bn']);
            return false;
        }

        if ($delivery['process'] == 'true'){
            $errmsg = sprintf('发货单【%s】已发货',$delivery['delivery_bn']);
            return false;
        }
        
        // 检查校验状态
        $deliveryItemModel = app::get('wms')->model('delivery_items');
        $delivery_items = $deliveryItemModel->getList('*',array('delivery_id'=>$delivery_id));

        if (!$delivery_items) {
            $errmsg = sprintf('发货单【%s】发货明细不存在',$delivery['delivery_bn']);
            return false;
        }

        $product_id = array();
        foreach ($delivery_items as $item) {
            if ($item['verify'] == 'false') {
                $errmsg = sprintf('发货单【%s】未全部校验',$delivery['delivery_bn']);
                return false;
            }

            $product_id[] = $item['product_id'];
        }

        // 检查库存
        $arrDlyType = kernel::single('ome_delivery_cfg')->getNormalCheckConsign();
        $arrDlyType[] = 'wms';
        if (in_array($delivery['type'], $arrDlyType)) {
            $branch_product = app::get('ome')->model('branch_product')->getList('branch_id,product_id,store',array('branch_id'=>$delivery['branch_id'],'product_id'=>$product_id));
            foreach ((array) $branch_product as $value) {
                $store[$value['branch_id']][$value['product_id']] = $value['store'];
            }

            foreach ($delivery_items as $item) {
                if ($item['number'] > intval($store[$delivery['branch_id']][$item['product_id']])) {
                    $errmsg = sprintf('%s 库存不足',$item['bn']);
                    return false;
                }
            }
        }

        // 盘点
        if (app::get('taoguaninventory')->is_installed()) {
            $sql = 'SELECT i.bn
                    FROM sdb_taoguaninventory_inventory_items as i 
                    left join sdb_taoguaninventory_inventory as inv on i.inventory_id=inv.inventory_id
                    WHERE i.product_id in(%s) AND inv.branch_id=%s AND inv.confirm_status=1 ';
            $row = kernel::database()->selectrow(sprintf($sql,implode(',',$product_id),$delivery['branch_id']));

            if ($row) {
                $errmsg = sprintf('%s 正在盘点,请将该货物放回指定区域',$row['bn']);
                return false;
            }
        }

        return true;
    }

    private function _order_to_consign($delivery_id, &$errmsg){
        $orders = $this->_get_delivery_order($delivery_id);
        $key = 'ome_judge_order_in_consign-';
        $orderConsign = array();
        foreach ($orders as $order) {
            $tmpDlyId = cachecore::fetch($key . $order['order_id']);
            if($tmpDlyId && $tmpDlyId != $delivery_id){
                $errmsg = '对应订单有其他发货单正在发货'; #ome_autotask_consign->exec_consign中使用
                return false;
            }
            $orderConsign[$order['order_id']] = $delivery_id;
        }
        foreach ($orderConsign as $orderId => $deliveryId) {
            cachecore::store($key . $orderId, $deliveryId, 60);
        }
        return true;
    }

    private function _delete_order_in_consign($delivery_id) {
        $key = 'ome_judge_order_in_consign-';
        $orders = $this->_get_delivery_order($delivery_id);
        foreach($orders as $order) {
            if(cachecore::fetch($key . $order['order_id'])) {
                cachecore::store($key . $order['order_id'], 0, 60);
            }
        }
    }

    # 发货单校验
    /**
     * _verify_check
     * @param mixed $delivery_id ID
     * @param mixed $errmsg errmsg
     * @return mixed 返回值
     */
    public function _verify_check($delivery_id,&$errmsg)
    {
        $delivery = $this->__delivery[$delivery_id];

        if (!$delivery) {
            $errmsg = '发货单不存在';return false;
        }

        if ($delivery['verify'] == 'true') {
            $errmsg = sprintf('发货单【%s】已校验',$delivery['delivery_bn']);return false;
        }

        $rs = $this->existDeliveryStatus($delivery_id,$errmsg);
        if (!$rs) return false;

        $rs = $this->existOrderPause($delivery_id,$errmsg);
        if (!$rs) return false;

        return true;
    }

    /**
     * 验证发货单状态
     *
     * @return void
     * @author 
     **/
    private function existDeliveryStatus($delivery_id,&$errmsg)
    {   
        $delivery = $this->__delivery[$delivery_id];

        if($delivery['parent_id'] > 0) {
            $errmsg = sprintf('发货单【%s】为子发货单不可操作',$delivery['delivery_bn']); return false;
        }

        $deliverylist[] = $delivery;
        // 如果是合单,一并检验
        if ($delivery['is_bind'] == 'true') {
            $deliverylist = array_merge($deliverylist,(array) $this->_get_children_delivery($delivery_id));
        }

        foreach ($deliverylist as $d) {

            if (!in_array($d['status'], array('0'))) {
                $errmsg = sprintf('发货单【%s】状态不可操作',$delivery['delivery_bn']); return false;
            }

            if ($d['disabled'] != 'false') {
                $errmsg = sprintf('发货单【%s】已删除',$delivery['delivery_bn']); return false;
            }

            if ($d['status'] == '2') {
                $errmsg = sprintf('发货单【%s】已暂停',$delivery['delivery_bn']);return false;
            }
        }

        return true;
    }

    private function existOrderPause($delivery_id,&$errmsg){

        static $isDeliveryOnShop = array();
        $delivery = $this->__delivery[$delivery_id];
        $orders = $this->_get_delivery_order($delivery_id);
        if (!$orders) {
            $errmsg = sprintf('发货单【%s】无相应订单',$delivery['delivery_bn']);return false;
        }
        $arrShopOrderBn = array();
        foreach ($orders as $order) {
            if($order['source'] == 'matrix' &&
                ($order['shop_type'] != 'vop')) {
                $arrShopOrderBn[$order['shop_id']][] = $order['order_bn'];
            }
            if ($order['process_status'] == 'cancel') {
                $errmsg = sprintf('发货单【%s】对应订单【%s】已取消',$delivery['delivery_bn'],$order['order_bn']);
                return false;
            }

            if ($order['abnormal'] == 'true') {
                $errmsg = sprintf('发货单【%s】对应订单【%s】异常',$delivery['delivery_bn'],$order['order_bn']);
                return false;
            }

            if ($order['disabled'] == 'true') {
                $errmsg = sprintf('发货单【%s】对应订单【%s】已删除',$delivery['delivery_bn'],$order['order_bn']);
                return false;
            }

            if ($order['pause'] == 'true') {
                $errmsg = sprintf('发货单【%s】对应订单【%s】已暂停',$delivery['delivery_bn'],$order['order_bn']);
                return false;
            }

            if ($order['pay_status'] == '6') {
                $errmsg = sprintf('发货单【%s】对应订单【%s】申请退款',$delivery['delivery_bn'],$order['order_bn']);
                return false;
            }

            if ($order['pay_status'] == '7') {
                $errmsg = sprintf('发货单【%s】对应订单【%s】退款中',$delivery['delivery_bn'],$order['order_bn']);
                return false;
            }

            if ($order['pay_status'] == '5') {
                $errmsg = sprintf('发货单【%s】对应订单【%s】全额退款',$delivery['delivery_bn'],$order['order_bn']);
                return false;
            }
        }
        if(!$isDeliveryOnShop[$delivery_id] && $arrShopOrderBn) {
            $isDeliveryOnShop[$delivery_id] = kernel::single('ome_service_order')->orderIsDeliveryOnShop($arrShopOrderBn);
        }
        if ($isDeliveryOnShop[$delivery_id]['rsp'] == 'fail') {
            $errmsg = sprintf('发货单【%s】对应前端订单不能发货：%s', $delivery['delivery_bn'], $isDeliveryOnShop[$delivery_id]['msg']);
            return false;
        }
        return true;
    }
}