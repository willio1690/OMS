<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 发票状态检查类
 */
class invoice_check
{
    //检查是否可以新建发票
    //老创建方法使用的check 已作废
    public function checkCreate($order_id)
    {
        if(!$order_id){
            return false;
        }
        
        //取最新发票记录一条，如存在未作废状态(即未开票或者已开票)的记录 则不能新增发票记录
        $mdlInOrder = app::get('invoice')->model('order');
        $arr_filter = array(
            'order_id' => $order_id,
            'is_status|in' => array(0,1),
        );
        $rs_invoice = $mdlInOrder->getList('*', $arr_filter, 0, 1, 'id DESC');
        if($rs_invoice){
            return false;
        }else{
            return true;
        }
    }
    
    /**
     * 校验当前订单是否可以新建发票 ------作废
     * @Author: xueding
     * @Vsersion: 2023/3/4 下午3:02
     * @param $order_id
     * @return bool
     */
    public function checkOrderCreate($order_id)
    {
        if(!$order_id){
            return false;
        }
        $ordersMdl = app::get('ome')->model('orders');
        $arr_filter = array(
            'order_id' => $order_id,
        );
        $rs_order = $ordersMdl->db_dump($arr_filter, 'pay_status,ship_status,payed');
        if (empty($rs_order)) {
            $ordersMdl = app::get('archive')->model('orders');
            $rs_order = $ordersMdl->db_dump($arr_filter, 'pay_status,ship_status,payed');
        }
        if($rs_order && in_array($rs_order['pay_status'],['1','4']) && $rs_order['ship_status'] != '4' && $rs_order['payed'] > 0){
            return true;
        }else{
            return false;
        }
    }
    
    //检查是否可以作废/冲红发票,并获取发票信息 ----------方法作废
    public function checkCancel($order_id,$id=0)
    {
        if(!$order_id){
            return false;
        }
        //取相关订单所有发票记录，有且只有一条是未作废状态
        $mdlInOrder = app::get('invoice')->model('order');
        $arr_filter = array(
            'order_id'     => $order_id,
            'is_status|in' => array(0,1),
        );
        if ($id) $arr_filter['id'] = $id;

        $rs_invoice = $mdlInOrder->getList('*', $arr_filter);
        if(count($rs_invoice) == 1){
            #如果是电子发票，还需要再去获取一些扩展信息，打开票接口的时候要用这些扩展信息
            if($rs_invoice[0]['mode'] == '1'){
                $get_channel_info = app::get('invoice')->model('channel')->get_channel_info($rs_invoice[0]['shop_id']);
                // #当店铺没有电子发票渠道配置，直接false;
                // if(empty($get_channel_info))return false;

                // 冗余
                $rs_invoice[0]['channel_node_id']     = $get_channel_info['node_id'];
                $rs_invoice[0]['channel_node_type']   = $get_channel_info['node_type'];
                $rs_invoice[0]['channel_golden_tax_version'] = $get_channel_info['golden_tax_version'];
                $rs_invoice[0]['channel_id']          = $get_channel_info['channel_id'];
                $rs_invoice[0]['channel_type']        = $get_channel_info['channel_type'];
                $rs_invoice[0]['channel_extend_data'] = isset($get_channel_info['channel_extend_data']) ? $get_channel_info['channel_extend_data'] : '';
                $rs_invoice[0]['skpdata']             = isset($get_channel_info['skpdata']) ? $get_channel_info['skpdata'] : '';#税控盘信息
                $rs_invoice[0]['kpddm']               = isset($get_channel_info['kpddm']) ? $get_channel_info['kpddm'] : '';#开票点编码
                $rs_invoice[0]['eqpttype']            = isset($get_channel_info['eqpttype']) ? $get_channel_info['eqpttype'] : '';#设备类型
            }
            return $rs_invoice[0];
        }else{
            return false;
        }
    }
    
    //检查是否可以开票,并获取开票信息
    public function checkBilling($arrBilling, &$error_msg=null)
    {
        $mdlInOrder = app::get('invoice')->model('order');
        
        $id = $arrBilling["id"];
        $order_id = $arrBilling["order_id"];
        if(!$id || !$order_id){
            $error_msg = '无效的操作';
            return false;
        }
        
        //取本张发票记录外的此订单是否存在未开票/已开票（即未作废）的发票记录 存在则不能开票，一个订单可以新建相似
        $arr_filter = array(
            "order_id"     => $order_id,
            "is_status|in" => array("0","1"), #0=>'未开票',1=>'已开票'
            "id|noequal"   => $id,#本次开票记录以外，该订单其他开票记录
        );
        $rs_info = $mdlInOrder->getList('*', $arr_filter, 0, 1);
        
        $_self_info = $mdlInOrder->getList('*',array("id"=>$id,'is_status'=>'0'));#状态必须为未开票
        if($rs_info || empty($_self_info)){
            $error_msg = '没有可开票的订单';
            return false;
        }
        
        $rs_invoice = $_self_info[0];
        
        #如果是电子发票，还需要再去获取一些扩展信息，打开票接口的时候要用这些扩展信息
        if($rs_invoice['mode'] == '1'){
            $get_channel_info = app::get('invoice')->model('channel')->get_channel_info($rs_invoice['shop_id']);
            
            //当店铺没有电子发票渠道配置，直接false;
            if(empty($get_channel_info)){
                $error_msg = '没有电子发票渠道配置';
                return false;
            }
            // 渠道没有节点id,，直接false;
            if(!$get_channel_info['node_id']){
                $error_msg = '开票渠道未绑定';
                return false;
            }

            $rs_invoice['channel_node_id']     = $get_channel_info['node_id'];
            $rs_invoice['channel_node_type']   = $get_channel_info['node_type'];
            $rs_invoice['channel_golden_tax_version'] = $get_channel_info['golden_tax_version'];
            $rs_invoice['channel_id']          = $get_channel_info['channel_id'];
            $rs_invoice['channel_type']        = $get_channel_info['channel_type'];
            $rs_invoice['channel_extend_data'] = isset($get_channel_info['channel_extend_data'])?$get_channel_info['channel_extend_data']:'';
            $rs_invoice['skpdata']             = isset($get_channel_info['skpdata'])?$get_channel_info['skpdata']:'';#税控盘信息
            $rs_invoice['kpddm']               = isset($get_channel_info['kpddm'])?$get_channel_info['kpddm']:'';#开票点编码
            $rs_invoice['eqpttype']            = isset($get_channel_info['eqpttype'])?$get_channel_info['eqpttype']:'';#设备类型
            $rs_invoice['einvoice_operating_conditions'] = $get_channel_info['einvoice_operating_conditions'];
        }

        return $rs_invoice;
    }
    
    //从明细如手 检查是否可以开电子发票（开蓝/冲红）
    public function checkEinvoiceCreate($rs_invoice, $billing_type=1, &$error_msg=null)
    {
        if(!$rs_invoice["id"]){
            $error_msg = '无效操作。';
            return false;
        }
        
        $mdlInOrderElIt = app::get('invoice')->model('order_electronic_items');
        $rs_item = $mdlInOrderElIt->dump(array("id"=>$rs_invoice["id"],"billing_type"=>$billing_type));
        if(empty($rs_item)){
            //没有相关明细记录 可以开票
            return true;
        }
        
        if(!$rs_item["invoice_code"] && $rs_item["serial_no"]){
            //之前点击开票没有成功或者开票中的状态 之前已生产开票流水号 可以开票
            return true;
        }
        
        //有相关的明细记录 并且已经callback开票成功 不可以开票
        $error_msg = '不能重复开票';
        return false;
    }
    
    //检查是否可以编辑
    public function checkEdit($id){
        if(!$id){
            return false;
        }
        //此发票记录 开票状态 必须是未开票
        $mdlInOrder = app::get('invoice')->model('order');
        $rs_info = $mdlInOrder->dump(array("id"=>$id));
        if(intval($rs_info["is_status"]) == 0 && ($rs_info['sync'] == '0' || $rs_info['sync'] == '2')){
            return true;
        }else{
            return false;
        }
    }
    
    //检查开电子发票打接口的必要参数是否缺失
    public function checkDoEinvoice($rs_invoice){
        $arr_hint = array();
        if(!floatval($rs_invoice["amount"])){
            $arr_hint[] = "开票金额必须大于0";
        }
        
        if(!$rs_invoice["channel_type"]){
            $arr_hint[] = "店铺对应的开票渠道不存在";
        }
        $title = $rs_invoice["tax_company"]?$rs_invoice["tax_company"]:$rs_invoice["title"];
        if(!$title){
            $arr_hint[] = "发票抬头不能为空";
        }
        return array(
            "arr_hint" => $arr_hint,
            "mode" => $rs_invoice["mode"],
        );
    }
    
    
    /**
     * 校验是否可以进行合并发票创建
     * @Author: xueding
     * @Vsersion: 2023/6/2 下午5:30
     * @param $id 1,2,3
     * @return bool[]
     */
    public function checkMergeInvoice($id)
    {
        //check
        if(empty($id)){
            return [false,'无效的id字段值'];
        }
        
        $sql = "SELECT o.id,o.order_bn FROM sdb_invoice_order AS o LEFT JOIN sdb_invoice_order_items AS oi on oi.id=o.id WHERE oi.original_id in (".$id.") AND o.is_status != '2' group by o.id";
        $res = kernel::database()->select($sql);
        if (count($res) > 0) {
            return [false,'创建失败已存在合并开票信息并且未开票或已开票'];
        }
        return [true];
    }
    
    /**
     * 检查是否可以新建发票
     * @Author: xueding
     * @Vsersion: 2023/6/5 下午3:25
     * @param $of_id
     * @return array|bool|bool[]
     */
    public function checkInvoiceCreate($of_id,$type = '')
    {
        if(!$of_id){
            return false;
        }
        
        if ($type == 'add_merge_invoice') {
            return [true];
        }
        $sql = "SELECT o.id,o.order_bn FROM sdb_invoice_order AS o LEFT JOIN sdb_invoice_order_items AS oi on oi.id=o.id WHERE oi.of_id in (".implode(',',array_unique($of_id)).") AND o.is_status != '2' group by o.id";

        $res = kernel::database()->select($sql);
        if (count($res) > 0) {
            return [false,'有未开票或者已开票状态的发票'];
        }
        return [true];
    }
    
    public function checkAddSame($of_id)
    {
        if(!$of_id){
            return false;
        }

        $sql = "SELECT id FROM sdb_invoice_order_front WHERE id in (".implode(',',array_unique($of_id)).") AND status != 'close' ";
        
        $res = kernel::database()->select($sql);
        if (count($res) == 0) {
            return [false,'没有可开票信息'];
        }
        return [true];
    }
    
    /**
     * 开票校验
     * @Author: xueding
     * @Vsersion: 2023/6/7 下午4:17
     * @param $arrBilling
     * @param null $error_msg
     * @return bool|mixed
     */
    public function checkMakeInvoice($arrBilling, &$error_msg=null)
    {
        $invoiceMdl = app::get('invoice')->model('order');
        $invoiceItemsMdl = app::get('invoice')->model('order_items');
        $frontMdl = app::get('invoice')->model('order_front');
        $frontItemsMdl = app::get('invoice')->model('order_front_items');
        
        $id = $arrBilling["id"];
        if(!$id){
            $error_msg = '无效的操作';
            return false;
        }
        
        //取本张发票记录外的此订单是否存在未开票/已开票（即未作废）的发票记录 存在则不能开票，一个订单可以新建相似
        $rs_invoice = $invoiceMdl->db_dump(['id' => $id, 'is_status' => '0']);
        if (empty($rs_invoice)) {
            $error_msg = '当前发票状态不可开票';
            return false;
        }
        
        //明细取of_id查询是否有其他未开的票
        $items     = $invoiceItemsMdl->getList('of_id,quantity,of_item_id', ['id' => $rs_invoice['id'],'is_delete'=>'false']);
        $of_id     = array_unique(array_column($items, 'of_id'));
        $invoiceItems = array_column($items,null,'of_item_id');
        $itemsList = $invoiceItemsMdl->getList('id', ['id|noequal' => $rs_invoice['id'], 'of_id' => $of_id]);
        $otherIds  = array_unique(array_column($itemsList, 'id'));
        if ($invoiceMdl->getList('id', ['id' => $otherIds, 'is_status' => ['0', '1']])) {
            $error_msg = '该订单存在多张未开票或已开票信息不能开票';
            return false;
        }
        //开票校验源数据
        $mainList = $frontMdl->getList('id,amount,status',['id'=>$of_id]);
        $frontItemsList = $frontItemsMdl->getList('of_id,id as of_item_id,quantity',['of_id'=>$of_id,'is_delete'=>'false','filter_sql'=>' (quantity - reship_num) > 0 ']);
        $ofItems = array_column($frontItemsList,null,'of_item_id');
        if (!$mainList || !$frontItemsList) {
            $error_msg = '开票失败源数据为空不能开票';
            return false;
        }
//        foreach ($mainList as $key => $val) {
//            if ($val['status'] != 'finish') {
//                $error_msg = '开票失败源单据未签收不能开票';
//                return false;
//            }
//        }
        
        if (count($invoiceItems) != count($ofItems)) {
            $error_msg = '开票明细与源数据明细不一致';
            return false;
        }
        
        foreach ($items as $key => $val) {
            if (isset($ofItems[$val['of_item_id']]) && $val['quantity'] !=$ofItems[$val['of_item_id']]['quantity']) {
                $error_msg = '开票明细数量与源数据明细数量不一致';
                return false;
            }
        }

        $amount = array_sum(array_column($mainList,'amount'));
        if ($rs_invoice['amount'] > $amount) {
            $error_msg = '开票失败金额异常，开票金额：'.$rs_invoice['amount'] .'，原始金额：'.$amount;
            return false;
        }
        
        #如果是电子发票，还需要再去获取一些扩展信息，打开票接口的时候要用这些扩展信息
        if($rs_invoice['mode'] == '1'){
            $get_channel_info = app::get('invoice')->model('channel')->get_channel_info($rs_invoice['shop_id']);
            
            //当店铺没有电子发票渠道配置，直接false;
            if(empty($get_channel_info)){
                $error_msg = '没有电子发票渠道配置';
                return false;
            }
            $rs_invoice['channel_node_id'] = $get_channel_info['node_id'];
            $rs_invoice['channel_node_type'] = $get_channel_info['node_type'];
            $rs_invoice['channel_golden_tax_version'] = $get_channel_info['golden_tax_version'];
            $rs_invoice['channel_id']          = $get_channel_info['channel_id'];
            $rs_invoice['channel_type']        = $get_channel_info['channel_type'];
            $rs_invoice['channel_extend_data'] = isset($get_channel_info['channel_extend_data'])?$get_channel_info['channel_extend_data']:'';
            $rs_invoice['skpdata']             = isset($get_channel_info['skpdata'])?$get_channel_info['skpdata']:'';#税控盘信息
            $rs_invoice['kpddm']               = isset($get_channel_info['kpddm'])?$get_channel_info['kpddm']:'';#开票点编码
            $rs_invoice['eqpttype']            = isset($get_channel_info['eqpttype'])?$get_channel_info['eqpttype']:'';#设备类型
            $rs_invoice['einvoice_operating_conditions'] = $get_channel_info['einvoice_operating_conditions'];
        }
        unset($rs_invoice['itemsdf']);
        return $rs_invoice;
    }
    
    /**
     * 校验作废发票
     * @Author: xueding
     * @Vsersion: 2023/6/8 上午11:12
     * @param int $id
     * @param null $error_msg
     * @return bool|mixed
     */
    public function checkInvoiceCancel($id=0, &$error_msg=null)
    {
        //check
        if(empty($id)){
            $error_msg = 'id字段为空,无法查询';
            return false;
        }
        
        $sql = "SELECT o.* FROM sdb_invoice_order AS o LEFT JOIN sdb_invoice_order_items AS oi on oi.id=o.id WHERE o.id = '" . $id . "' AND o.is_status != '2' GROUP BY o.id ";
        $rs_invoice = kernel::database()->select($sql);
    
        if (empty($rs_invoice)) {
            $error_msg = '当前发票状态不可作废';
            return false;
        }

        if(count($rs_invoice) == 1){
            #如果是电子发票，还需要再去获取一些扩展信息，打开票接口的时候要用这些扩展信息
            if($rs_invoice[0]['mode'] == '1'){
                $get_channel_info = app::get('invoice')->model('channel')->get_channel_info($rs_invoice[0]['shop_id']);

                $rs_invoice[0]['channel_node_id'] = $get_channel_info['node_id'];
                $rs_invoice[0]['channel_node_type'] = $get_channel_info['node_type'];
                $rs_invoice[0]['channel_golden_tax_version'] = $get_channel_info['golden_tax_version'];
                $rs_invoice[0]['channel_id']          = $get_channel_info['channel_id'];
                $rs_invoice[0]['channel_type']        = $get_channel_info['channel_type'];
                $rs_invoice[0]['channel_extend_data'] = isset($get_channel_info['channel_extend_data'])?$get_channel_info['channel_extend_data']:'';
                $rs_invoice[0]['skpdata']             = isset($get_channel_info['skpdata'])?$get_channel_info['skpdata']:'';#税控盘信息
                $rs_invoice[0]['kpddm']               = isset($get_channel_info['kpddm'])?$get_channel_info['kpddm']:'';#开票点编码
                $rs_invoice[0]['eqpttype']            = isset($get_channel_info['eqpttype'])?$get_channel_info['eqpttype']:'';#设备类型
            }
            return $rs_invoice[0];
        }else{
            $error_msg = '当前发票状态不可作废';
            return false;
        }
    }
    
}
