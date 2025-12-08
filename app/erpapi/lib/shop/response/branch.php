<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2019/4/17
 * @describe 待寻仓订单
 */
class erpapi_shop_response_branch extends erpapi_shop_response_abstract
{
    protected function _format_wait_items_sdf($sdf) {
        $details = $sdf['delivery_order_details'] ? json_decode($sdf['delivery_order_details'], 1) : array();
        if(!$details) {
            return array();
        }
      
        $arrBnList = array();
        foreach ($details as $val) {

            $bn = kernel::single('material_codebase')->getBnBybarcode($val['barcode']);
            
            $arrBnList[$bn] = $val['barcode'];
        }
        $arrBarcodeProduct = array();
        $rows = app::get('material')->model('basic_material')->getList('bm_id,material_bn', array('material_bn'=>array_keys($arrBnList)));
        foreach ($rows as $val) {
            $barcode = $arrBnList[$val['material_bn']];
            $arrBarcodeProduct[$barcode] = $val;
        }
        $items = array();
        foreach ($details as $val) {
            $tmp = array(
                'barcode' => $val['barcode'],
                'product_name' => $val['product_name'],
                'brand_name' => $val['brand_name'],
                'size' => $val['size'],
                'quantity' => $val['quantity'],
                'po_no' => $val['po_no'],
                'cooperation_no' => $val['cooperation_no'],

            );
            if($arrBarcodeProduct[$val['barcode']]){
                $product = $arrBarcodeProduct[$val['barcode']];
                $tmp['product_id'] = $product['bm_id'];
                $tmp['bn'] = $product['material_bn'];
            }
            $items[] = $tmp;
        }
        return $items;
    }

    protected function _format_wait_sdf($sdf) {
        $params = array(
            'order_bn' => $sdf['order_sn'],
            'available_warehouses' => implode(',', json_decode($sdf['available_warehouses'], 1)),
            'buyer_address' => $sdf['buyer_address'],
            'vendor_id' => $sdf['vendor_id'],
            'vendor_name' => $sdf['vendor_name'],
            'status' => $sdf['status'],
            'status_remark' => $sdf['status_remark'],
            'last_modified' => strtotime($sdf['update_time']),
        );
        $params['items'] = $this->_format_wait_items_sdf($sdf);
        return $params;
    }

    /**
     * wait
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */

    public function wait($sdf)
    {
        $params = $this->_format_wait_sdf($sdf);
        if(!$params['order_bn']) {
            $this->__apilog['result']['msg'] = '没有此项业务';
            return false;
        }
        $params['shop_id'] = $this->__channelObj->channel['shop_id'];
        $params['shop_type'] = $this->__channelObj->channel['shop_type'];
        $this->__apilog['original_bn']    = $params['order_bn'];
        $this->__apilog['title']          = '待寻仓订单['.$params['order_bn'].']';
        $orderWait = app::get('purchase')->model('order_wait')->db_dump(array('order_bn'=>$params['order_bn'], 'shop_id'=>$params['shop_id']));
        if($orderWait) {
            if(in_array($orderWait['status'], array('JIT','JITX'))) {
                $this->__apilog['result']['msg'] = '寻仓已完毕';
                return false;
            }
            $params['order_wait'] = $orderWait;
        }
        return $params;
    }

}