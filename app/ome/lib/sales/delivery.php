<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_sales_delivery {

    /**
     * __construct
     * @return mixed 返回值
     */
    public function __construct(){
		$this->db = kernel::database();
    }

    /**
     * 处理
     * @param mixed $delivery_id ID
     * @return mixed 返回值
     */
    public function process($delivery_id){

        
        $sales_data = $this->get_sales_data($delivery_id);

        $sales_delivery_data = $this->get_sales_delivery_data($delivery_id, $sales_data);

        $saledeliverysObj = app::get('sales')->model('delivery_order');
        $sales_delivery = $saledeliverysObj->db_dump(array('delivery_id'=>$delivery_id),'delivery_id');
        if ($sales_delivery) return true;

        $this->db->beginTransaction();
        $rs = $this->insertRow($delivery_id);
        if (!$rs) {
                    
            $this->db->rollBack();
            return false;
        }
               
                
        $itemsObj = app::get('sales')->model('delivery_order_item');

        $sql = ome_func::get_insert_sql($itemsObj, $sales_delivery_data);
        $rs = $this->db->exec($sql);
        if (!$rs) {
                 
            $this->db->rollBack();
            return false;
        }

        $this->db->commit();

      
    }


	
    public function get_sales_delivery_data($deliveryId, $salesData = array()) {
        $deliveryObj = app::get('ome')->model('delivery');
        $deliveryDetail = $deliveryObj->db_dump(array('delivery_id'=>$deliveryId),'branch_id,delivery_time,delivery_bn,shop_id,org_id');
        $delivery_items_detailObj = app::get('ome')->model('delivery_items_detail');
        $delivery_items_detail = $delivery_items_detailObj->getList('*', array('delivery_id'=>$deliveryId), 0, -1);

        $order_bns = array();


        foreach($salesData as $k=>$sales){
            $order_detail = $this->get_order_detail($k);

            $order_bns[$k] = $order_detail;

        }
        $objMath    = kernel::single('eccommon_math');
        $tmpsaleprice = $this->get_avg_delivery_data($salesData);


        $productId = array();
        foreach ($delivery_items_detail as $item) {
            $productId[] = $item['product_id'];
        }

      
        $shop_detail = $this->get_shop_detail($deliveryDetail['shop_id']);

        $branch_detail = $this->get_branch_detail($deliveryDetail['branch_id']);

        $salesDeliveryData = array();
        foreach ($delivery_items_detail as $val) {
            $tmpSaleDeliveryData = array();
            $tmpSaleDeliveryData['delivery_id'] = $val['delivery_id'];
            $tmpSaleDeliveryData['shop_id'] = $deliveryDetail['shop_id'];
            $tmpSaleDeliveryData['shop_type'] = $shop_detail['shop_type'];
            $tmpSaleDeliveryData['order_type'] = $order_bns[$val['order_id']]['order_type'];
            $tmpSaleDeliveryData['createway'] = $order_bns[$val['order_id']]['createway'];
            $tmpSaleDeliveryData['shop_bn'] = $shop_detail['shop_bn'];
            $tmpSaleDeliveryData['sale_type'] = $order_bns[$val['order_id']]['sale_type'];
            $tmpSaleDeliveryData['branch_id'] = $deliveryDetail['branch_id'];
            $tmpSaleDeliveryData['branch_bn'] = $branch_detail['branch_bn'];
            $tmpSaleDeliveryData['delivery_bn'] = $deliveryDetail['delivery_bn'];
            $tmpSaleDeliveryData['delivery_item_id'] = $val['delivery_item_id'];
            $tmpSaleDeliveryData['order_id'] = $val['order_id'];
            $tmpSaleDeliveryData['order_bn'] = $order_bns[$val['order_id']]['order_bn'];
            $tmpSaleDeliveryData['order_obj_id'] = $val['order_obj_id'];
            $tmpSaleDeliveryData['order_item_id'] = $val['order_item_id'];
            $tmpSaleDeliveryData['oid']         = $val['oid'] ?: '0';
            
            $tmpSaleDeliveryData['obj_type'] = $val['obj_type']; //商品类型
            $tmpSaleDeliveryData['item_type'] = $val['item_type'];
            $tmpSaleDeliveryData['s_type']    = $val['s_type'];
          
            $tmpSaleDeliveryData['product_id'] = $val['product_id'];
            $tmpSaleDeliveryData['sale_time'] = time();
            $tmpSaleDeliveryData['bn'] = $val['bn'];
            $tmpSaleDeliveryData['delivery_time'] = $deliveryDetail['delivery_time'];
            $tmpSaleDeliveryData['order_create_time'] = $order_bns[$val['order_id']]['createtime'];
            $tmpSaleDeliveryData['order_pay_time'] = $order_bns[$val['order_id']]['paytime'];
            $tmpSaleDeliveryData['nums'] = $val['number'];
            $tmpItemSalePrice = $tmpsaleprice[$val['order_item_id']];
            $tmpSaleDeliveryData['name'] = $tmpItemSalePrice['name'];
            $tmpSaleDeliveryData['sales_material_bn'] = $tmpItemSalePrice['sales_material_bn'];
            if($val['number'] == $tmpItemSalePrice['nums'] || $tmpItemSalePrice['nums'] == 0){
                $tmpSaleDeliveryData['price'] = sprintf('%.2f',$tmpItemSalePrice['price']);
                $tmpSaleDeliveryData['pmt_price'] = $tmpItemSalePrice['pmt_price'];
                $tmpSaleDeliveryData['sale_price'] = $tmpItemSalePrice['sale_price'];
                $tmpSaleDeliveryData['apportion_pmt'] = $tmpItemSalePrice['apportion_pmt'];
                $tmpSaleDeliveryData['sales_amount'] = $tmpItemSalePrice['sales_amount'];
                $tmpSaleDeliveryData['platform_amount'] = $tmpItemSalePrice['platform_amount'];
                $tmpSaleDeliveryData['settlement_amount'] = $tmpItemSalePrice['settlement_amount'];
                $tmpSaleDeliveryData['platform_pay_amount'] = $tmpItemSalePrice['platform_pay_amount'];
                $tmpSaleDeliveryData['actually_amount'] = $tmpItemSalePrice['actually_amount'];
               
            } else {
            
                //判断是否是最后一条,如果是,剩余的赋上
                
                $tmpSaleDeliveryData['pmt_price'] = sprintf('%.2f', $tmpItemSalePrice['pmt_price']*$val['number']/$tmpItemSalePrice['nums']);
                $tmpSaleDeliveryData['sale_price'] = sprintf('%.2f',$tmpItemSalePrice['sale_price']*$val['number']/$tmpItemSalePrice['nums']);
                $tmpSaleDeliveryData['apportion_pmt'] = sprintf('%.2f', $tmpItemSalePrice['apportion_pmt']*$val['number']/$tmpItemSalePrice['nums']);
                $tmpSaleDeliveryData['price'] = sprintf('%.2f',$tmpItemSalePrice['price']);
                $tmpSaleDeliveryData['sales_amount'] = sprintf('%.2f', $tmpItemSalePrice['sales_amount']*$val['number']/$tmpItemSalePrice['nums']);
                $tmpSaleDeliveryData['platform_amount'] = sprintf('%.2f', $tmpItemSalePrice['platform_amount']*$val['number']/$tmpItemSalePrice['nums']);
                $tmpSaleDeliveryData['settlement_amount'] = sprintf('%.2f', $tmpItemSalePrice['settlement_amount']*$val['number']/$tmpItemSalePrice['nums']);
                $tmpSaleDeliveryData['platform_pay_amount'] = sprintf('%.2f', $tmpItemSalePrice['platform_pay_amount']*$val['number']/$tmpItemSalePrice['nums']);
                $tmpSaleDeliveryData['actually_amount'] = sprintf('%.2f', $tmpItemSalePrice['actually_amount']*$val['number']/$tmpItemSalePrice['nums']);
               
            }
          
            $zkjg = $objMath->number_minus(array($tmpSaleDeliveryData['price']*$tmpSaleDeliveryData['nums'],$tmpSaleDeliveryData['pmt_price'],$tmpSaleDeliveryData['sale_price']));

            $tmpSaleDeliveryData['zkjg'] = $zkjg;
            $zk_amount =$objMath->number_plus(array($tmpSaleDeliveryData['pmt_price'],$tmpSaleDeliveryData['apportion_pmt'],$zkjg));

            
            $tmpSaleDeliveryData['zk_amount'] =$zk_amount;
            $tmpSaleDeliveryData['org_id'] =$deliveryDetail['org_id'];
        
            $salesDeliveryData[] = $tmpSaleDeliveryData;
        }


        return $salesDeliveryData;
    }

    function get_sales_data($delivery_id){
    	$deliveryObj = app::get('ome')->model('delivery');
        $orderIds = $deliveryObj->getOrderIdsByDeliveryIds(array($delivery_id));

        $ome_original_dataLib = kernel::single('ome_sales_original_data');
        $ome_sales_dataLib = kernel::single('ome_sales_data');
        foreach ($orderIds as $key => $orderId){
           
            $order_original_data = $ome_original_dataLib->init($orderId);

            if($order_original_data){
                $sales_data[$orderId] = $ome_sales_dataLib->generate($order_original_data,$delivery_id);
                if(!$sales_data[$orderId]){
                    return false;
                }
            }else{
                return false;
            }
            unset($order_original_data);
        }


        //平摊预估物流运费，主要处理订单合并发货以及多包裹单的运费问题
        $ome_sales_logistics_feeLib = kernel::single('ome_sales_logistics_fee');
        $ome_sales_logistics_feeLib->calculate($orderIds,$sales_data);

        return $sales_data;
    }

    
   


    function get_shop_detail($shop_id){
        $shopObj = app::get('ome')->model('shop');
        $shop_detail = $shopObj->dump($shop_id,'shop_bn,shop_type');
        return $shop_detail;
    }

    function get_branch_detail($branch_id){
        $branchObj = app::get('ome')->model('branch');
        $branch_detail = $branchObj->db->selectrow("SELECT branch_bn FROM sdb_ome_branch WHERE branch_id=".$branch_id);
        return $branch_detail;
    }

    function get_order_detail($order_id){
        $orderObj = app::get('ome')->model('orders');
        $order_detail = $orderObj->dump(array('order_id'=>$order_id),'order_bn,createtime,order_type,createway,paytime');
        return $order_detail;
    }


    /**
     * insertRow
     * @param mixed $delivery_id ID
     * @return mixed 返回值
     */
    public function insertRow($delivery_id) {

        $deliveryObj = app::get('ome')->model('delivery');
        $dly = $deliveryObj->dump($delivery_id, '*');
        $sdf = array(
            'delivery_id'   => $dly['delivery_id'],
            'delivery_bn'   => $dly['delivery_bn'],
            'member_id'     => $dly['member_id'],
            'logi_id'       => $dly['logi_id'],
            'logi_name'     => $dly['logi_name'],
            'logi_no'       => $dly['logi_no'],
            'ship_name'     => $dly['consignee']['name'],
            'ship_area'     => $dly['consignee']['area'],
            'ship_province' => $dly['consignee']['province'],
            'ship_city'     => $dly['consignee']['city'],
            'ship_district' => $dly['consignee']['district'],
            'ship_addr'     => $dly['consignee']['addr'],
            'ship_zip'      => $dly['consignee']['zip'],
            'ship_tel'      => $dly['consignee']['telephone'],
            'ship_mobile'   => $dly['consignee']['mobile'],
            'ship_email'    => $dly['consignee']['email'],
            'branch_id'     => $dly['branch_id'],
            'net_weight'    => $dly['net_weight'],
            'weight'        => $dly['weight'],
            'delivery_time' => $dly['delivery_time'],
            'delivery_cost_expect' => $dly['delivery_cost_expect'],
            'delivery_cost_actual' => $dly['delivery_cost_actual'],
            'shop_id'       => $dly['shop_id'],
            'shop_type'     => $dly['shop_type'],
            'sale_time'     =>  time(),
            'org_id'        =>  $dly['org_id'],
        );
        $saledeliverysObj = app::get('sales')->model('delivery_order');
        return $saledeliverysObj->insert($sdf);
    }


    /**
     * 获取_avg_delivery_data
     * @param mixed $salesData 数据
     * @return mixed 返回结果
     */
    public function get_avg_delivery_data($salesData){
        
        $delivery_items = array();

        foreach($salesData as $sales){
            foreach($sales['sales_items'] as $item){
                $delivery_items[$item['order_item_id']] = $item;
            }

        }
          
        return $delivery_items;
        
    }

}




?>