<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 发货单推送
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_matrix_kejie_request_delivery extends erpapi_wms_request_delivery
{
    protected function _format_delivery_create_params($sdf)
    {
        // 发票信息
        if ($sdf['is_order_invoice'] == 'true'){
            $invoice       = $sdf['invoice'];
            $is_invoice    = 'true';
            $invoice_type  = 'general'; // ?什么情况
            $invoice_title = $invoice['invoice_desc'];
       
            // 发票明细
            if ($invoice['invoice_items']){
                $invoice_items = array();
                $i_money = 0;
                foreach ($invoice['invoice_items'] as $val){
                    $price = round($val['price'],2);
                    $invoice_items[] = array(
                        'name'     => $val['item_name'],
                        'spec'     => $val['spec'],
                        'quantity' => $val['nums'],
                        'price'    => $price,
                    );
                    $i_money += $price;
                }
            }
            $invoice_item  = json_encode($invoice_items);
            $invoice_money = $i_money;
        }

        $params = parent::_format_delivery_create_params($sdf);
        $params['invoice_type']=$invoice_type;
        $params['invoice_title'] =$invoice_title;
        $params['invoice_fee']=$invoice_money;

        $params['invoice_item']=$invoice_item;
        $logistics_no = $sdf['logi_no'];
        // 京东订单提供运单号
        $params['logistics_no'] = $logistics_no;
        if ($params['logistics_no'] === false) {
            return array();
        }
        
        return $params;
    }


   
}