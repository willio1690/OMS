<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class siso_receipt_iostock_sold extends siso_receipt_iostock_abstract implements siso_receipt_iostock_interface{

    /**
     * 
     * 出入库类型id
     * @var int
     */
    protected $_typeId = 3;

    /**
     * 
     * 出库/入库动作
     * @var int
     */
    protected $_io_type = 0;

    /**
     * 创建
     * @param mixed $params 参数
     * @param mixed $data 数据
     * @param mixed $msg msg
     * @return mixed 返回值
     */
    public function create($params, &$data, &$msg = null)
    {
        $delivery_id = $params['delivery_id'];
        $dly = app::get('ome')->model('delivery')->db_dump(['delivery_id'=>$delivery_id], 'shop_id');
        $shop = app::get('ome')->model('shop')->db_dump(['shop_id'=>$dly['shop_id']], 'delivery_mode,shop_bn,name');

        $params['shop_bn'] = $shop['shop_bn'];
        $params['shop_name'] = $shop['name'];

        if($shop['delivery_mode'] == 'jingxiao') {
            $data = $this->get_io_data($params);
            return true;
        }
        return parent::create($params, $data, $msg);
    }

    /**
     * 
     * 根据发货单组织销售明细内容
     * @param array $params
     */
    public function get_io_data($params){

        $delivery_items_detailObj = app::get('ome')->model('delivery_items_detail');
        $delivery_id = $params['delivery_id'];

        //发货单信息
        $sql = 'SELECT `branch_id`,`delivery_bn`,`op_name`,`delivery_time`,`is_cod`,`delivery` FROM `sdb_ome_delivery` WHERE `delivery_id`=\''.$delivery_id.'\' AND `status`=\'succ\'';
        $delivery_detail = $delivery_items_detailObj->db->selectrow($sql);
        //判断是否已发货 已发货状态下才生成
        if (!$delivery_detail) return false;
        //判断出入库明细里是否已有记录如果已有不生成
        $db = kernel::database();
        $iostock_sql = "SELECT iostock_id FROM sdb_ome_iostock WHERE type_id='3' AND original_id=".$delivery_id." AND original_bn='".$delivery_detail['delivery_bn']."'";
        $iostock_detail = $db->selectrow($iostock_sql);
        if ($params['process_status'] == 'remain_cancel') {
            //余单撤销不判断明细
        }else{
            if ($iostock_detail) return false;
        }

        $delivery_items_detail = $delivery_items_detailObj->getList('*', array('delivery_id'=>$delivery_id), 0, -1);

        $order_ids = array_column($delivery_items_detail,'order_id');

        $orderMdl = app::get('ome')->model('orders');

        $orderList = $orderMdl->getlist('order_id,order_bn,order_type',array('order_id'=>$order_ids));

        $orderList = array_column($orderList,null,'order_id');
    
        $branchInfo = app::get('ome')->model('branch')->db_dump(['branch_id'=>$delivery_detail['branch_id']],'branch_id,branch_bn,is_negative_store');
        $bill_type = 'salenormal';
        $iostock_data = array();
        if ($delivery_items_detail){
            foreach ($delivery_items_detail as $k=>$v){
                $order_bn = $orderList[$v['order_id']]['order_bn'];
                $order_type = $orderList[$v['order_id']]['order_type'];
                //开始 SHIPED && 线下订单 order_type  && 仓允许负库存
                $negative_store = false;
                if ('SHIPED' == $delivery_detail['delivery'] && $branchInfo['is_negative_store'] == 1 && $order_type == 'offline') {
                    $negative_store = true;
                }
                $iostock_data[$v['item_detail_id']] = array(
                    'order_id' => $v['order_id'],
                    'branch_id' => $delivery_detail['branch_id'],
                    'original_bn' => $delivery_detail['delivery_bn'],
                    'original_id' => $delivery_id,
                    'original_item_id' => $v['item_detail_id'],
                    'supplier_id' => null,
                    'bn' => $v['bn'],
                    'iostock_price' => $v['price'],
                    'nums' => $v['number'],
                    'cost_tax' => null,
                    'oper' => $delivery_detail['op_name'],
                    'create_time' => $delivery_detail['delivery_time'],
                    'operator' => $delivery_detail['op_name'],
                    'settle_method' => '',
                    'settle_status' => '0',
                    'settle_operator' => '',
                    'settle_time' => null,
                    'settle_num' => null,
                    'settlement_bn' => null,
                    'settlement_money' => '0',
                    'memo' => '',
                    'type_id'=>3,
                    'bill_type' => $bill_type,
                    'business_bn'=>$order_bn,
                    'shop_bn'   =>$params['shop_bn'],
                    'shop_name'=>$params['shop_name'],
                    'negative_stock'=>$negative_store,
                );
            }
            $this->dealBatch($iostock_data, ['delivery'], '-');
        }
        unset($delivery_detail,$delivery_items_detail);
        return $iostock_data;
    }
}
