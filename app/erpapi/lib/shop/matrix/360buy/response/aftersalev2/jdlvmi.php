<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @desc    京东售后数据转换
 * @author: sunjing
 * @since: 2019-9-19
 */
class erpapi_shop_matrix_360buy_response_aftersalev2_jdlvmi extends erpapi_shop_response_aftersalev2
{
    

    protected function _formatAddParams($params) {

        $sdf = parent::_formatAddParams($params);
        $sdf['status'] ='WAIT_BUYER_RETURN_GOODS';
        $extend_field = json_decode($params['extend_field'],true);

        if($extend_field){
            $sdf['platform_order_bn'] = $extend_field['originOutOrderCode'];
        }
        return $sdf;
    }
    
    protected function _getAddType($sdf) {
        return 'returnProduct';
    }


    protected function _formatAddItemList($sdf, $convert = array()) {
        
        $convert = array(
            'sdf_field'     =>'outer_id',
            'order_field'   =>'shop_goods_id',
            'default_field' =>'outer_id'
        );


        return parent::_formatAddItemList($sdf, $convert);
    }

    protected function _returnProductAddSdf($sdf) {
        $sdf = parent::_returnProductAddSdf($sdf);
        
        if(!$sdf) return false;
        
        $sdf['choose_type_flag'] = 1;
        
        return $sdf;
    }

    protected function _calculateAddPrice($refundItems, $sdf) {
       

        if ($sdf['order']['tran_type'] == 'archive'){
            $db = kernel::database();
            foreach($refundItems as $k=>$v){
                $order_item = $db->selectrow("SELECT sendnum,item_id,price FROM sdb_archive_order_items WHERE order_id=".$sdf['order']['order_id']." AND bn='".$v['bn']."'");
                $refundItems[$k]['sendNum'] = $order_item['sendnum'];
                

            }
            return $refundItems;
        }
        if(empty($refundItems)) {
            return array();
        }

        $objectMdl = app::get('ome')->model('order_objects');
        $itemMdl   = app::get('ome')->model('order_items');


        $return_items = array ();

        // 如果是捆绑
        foreach ($refundItems as $key => $value) {
            $object = $objectMdl->db_dump(array ('order_id' => $sdf['order']['order_id'], 'bn' => $value['bn'], 'delete' => 'false'));

            if (!$object) continue;

            if ($object['obj_type'] == 'pkg') {
                $items = $itemMdl->getList('*',array ('order_id' => $sdf['order']['order_id'], 'obj_id' => $object['obj_id'], 'delete' => 'false'));

                $i = 0; $c = count($items); $price = $value['price']; $priceAvg = bcdiv($price, $c, 2);
                foreach ($items as $v) {
                    if ($i == $c) {
                        $return_items[] = array (
                            'product_id' => $v['product_id'],
                            'bn'         => $v['bn'],
                            'name'       => $v['name'],
                            'price'      => $price,
                            'num'        => $v['nums']/$object['quantity']*$value['num'],
                            'sendNum'    => $v['sendnum'],
                            'shop_goods_bn'=>$object['shop_goods_id'],
                            'obj_type'  =>'pkg',
                            'quantity'  =>$value['num'],
                            'order_item_id'=>$v['item_id'],
                            
                        );
                    } else {
                        $return_items[] = array (
                            'product_id' => $v['product_id'],
                            'bn'         => $v['bn'],
                            'name'       => $v['name'],
                            'price'      => $priceAvg,
                            'num'        => $v['nums']/$object['quantity']*$value['num'],
                            'sendNum'    => $v['sendnum'],
                            'shop_goods_bn'=>$object['shop_goods_id'],
                            'obj_type'  =>'pkg',
                            'quantity'  =>$value['num'],
                            'order_item_id'=>$v['item_id'],
                           
                        );

                        $price = bcsub($price, $priceAvg, 2);
                    }
                }

                if (!$item) continue;

                $value['name']  = $item['name'];
                $value['price'] = $item['price'];

                $return_items[] = $value;

            } else {
                $item = $itemMdl->db_dump(array ('order_id' => $sdf['order']['order_id'], 'obj_id' => $object['obj_id'], 'delete' => 'false'));

                if (!$item) continue;

                $return_items[] = array (
                    'product_id' => $item['product_id'],
                    'bn'         => $item['bn'],
                    'name'       => $item['name'],
                    'sendNum'    => $item['sendnum'],
                    'price'      => $value['price'],
                    'num'        => $value['num'],
                    'shop_goods_bn'=>$object['shop_goods_id'],
                    'obj_type'  =>'product',
                    'quantity'  =>$value['num'],
                    'order_item_id'=>$item['item_id'],
                );
            }
        }

        return $return_items;
    }
}