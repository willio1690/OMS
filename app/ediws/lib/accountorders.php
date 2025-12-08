<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ediws_accountorders{


     static $bill_type = array(
        '1002'  => 'JDZY_STOCKOUT',
        '12'    => 'JDZY_STOCKIN',
        
    );
    /**
     * 创建Sale
     * @param mixed $data 数据
     * @return mixed 返回值
     */
    public function createSale($data)
    {

        if(!$data) return false;
        $salesMdl = app::get('billcenter')->model('sales');

        $rs = $salesMdl->create_sales($data);

        return $rs;
        
    }


    /**
     * 创建Aftersale
     * @param mixed $data 数据
     * @return mixed 返回值
     */
    public function createAftersale($data){

        if(!$data) return false;
        $aftersalesMdl = app::get('billcenter')->model('aftersales');
       
        
        $rs = $aftersalesMdl->create_aftersales($data);

        return $rs;

    }



    /**
     * 创建Bill
     * @param mixed $ord_id ID
     * @return mixed 返回值
     */
    public function createBill($ord_id){

        $ordersMdl = app::get('ediws')->model('account_orders');

        $orders = $ordersMdl->dump(array('ord_id'=>$ord_id,'refType'=>array('1002'),'sync_status'=>array('0','2')),'*');

        if(!$orders){
            return false;
        }

        $refType = $orders['refType'];


        $data = $this->formatData($orders);

        if(!$data) return false;

        if($data){
            $refType = $orders['refType'];
            if(in_array($refType,array('1002'))){

                $rs = $this->createSale($data);
            }elseif(in_array($refType,array('12'))){
                $rs = $this->createAftersale($data);
            }

            $sync_status = '1';
            if(!$rs){
                $sync_status = '2';
            }

            $updata = array('sync_status'=>$sync_status,'error_msg'=>$msg);

            $ordersMdl->update($updata,array('ord_id'=>$ord_id));
        }

        return $rs;

    }


    /**
     * formatData
     * @param mixed $orders orders
     * @return mixed 返回值
     */
    public function formatData($orders){
        $shop = app::get('ome')->model('shop')->db_dump(['shop_id' => $orders['shop_id']], 'shop_id,shop_bn,name');
        $data = [
            'bill_bn'       => $orders['purchaseOrderNo'],//采购单号
            'bill_type'     => self::$bill_type[$orders['refType']],
            'bill_id'       => $orders['ord_id'],
            'shop_id'       => $orders['shop_id'],
            'shop_bn'       => $shop['shop_bn'],
            'shop_name'     => $shop['name'],
            'sale_time'     => $orders['orderCompleteDate'],
            'ship_time'     => $orders['storeOutDate'],
            'original_bn'   => $orders['lastId'],
            'original_id'   => $orders['ord_id'],
            'order_bn'      => $orders['orderNo'],//订单号
            'po_bn'         => $orders['orderNo'], 
        ];

        $item = [];

        $bmExMdl = app::get('material')->model('basic_material_ext');
        
        if($orders['bm_id']<=0 || empty($orders['material_bn'])){
            $materials = kernel::single('ediws_jdlvmi')->get_sku($orders['shop_id'],$orders['sku']);

            if($materials){
                $orders['material_bn'] = $materials['material_bn'];
                $orders['bm_id'] = $materials['bm_id'];

            }

        }

        if(!$orders['material_bn']) return false;

        $bm_exts = $bmExMdl->db_dump(array('bm_id' => $orders['bm_id']),'bm_id,retail_price');
        $retail_price = $bm_exts['retail_price'];
        $item['material_bn']        = $orders['material_bn'];
        $item['material_name']      = $orders['goodsName'];
        $item['bm_id']              = $orders['bm_id'];
        $item['nums']               = abs($orders['quantity']);
        $amount = $retail_price*$item['nums'];
        $item['price']              = $retail_price;
        $item['amount']             = $amount;
        $item['settlement_amount']  = abs($orders['amount']);
        $item['sale_price']  = abs($orders['amount']);

        $data['items'][] = $item;
        //
        $data['total_amount'] = $amount;
        $data['settlement_amount'] = $item['settlement_amount'];
        $data['total_sale_price'] = $item['sale_price'];

        return $data;
    }


    /**
     * 获取PiriceByOrderId
     * @param mixed $shop_product_bn shop_product_bn
     * @param mixed $saleordid ID
     * @return mixed 返回结果
     */
    public function getPiriceByOrderId($shop_product_bn,$saleordid){

        $ordersMdl = app::get('ediws')->model('account_orders');

        $orders = $ordersMdl->db_dump(array('sku'=>$shop_product_bn,'orderNo'=>$saleordid),'price');

        return $orders;
    }

}


?>
