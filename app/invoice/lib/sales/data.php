<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class invoice_sales_data
{
    private $appName = 'ome';
    
    public function setAppName($appName) {
        $this->appName = $appName;
        return $this;
    }
    
    /**
     * 开票明细，使用销售单均摊方案
     * @param  [type] $invoice [description]
     * @return [type]          [description]
     */
    public function generate($invoice)
    {
        //方法弃用
        return false;
        if (!$invoice['order_id']) {
            return false;
        }

        if (in_array($invoice['sync'], array('3','4','5','7','8','9')) && $invoice['itemsdf']) {
            #冲红使用
            $sales_data = json_decode($invoice['itemsdf'], 1);
            if($sales_data) {
                return $sales_data;
            }
        }

        $rows = app::get('ome')->model('orders')->getList('order_id',array('order_id'=>$invoice['order_id']));
        if(empty($rows)) {
            $this->appName = 'archive';
        }
        $orderinfo = kernel::single('ome_sales_original_data')->setAppName($this->appName)->init($invoice['order_id']);

        if (in_array($invoice['sync'], array('4', '5')) && $orderinfo['shipping']['cost_shipping'] > 0 && '1' == app::get('ome')->getConf('ome.invoice.amount.infreight') && $invoice['amount'] < $orderinfo['total_amount']) {

            $total_amount = $invoice['amount'];

            $orderinfo['pmt_order'] += $orderinfo['total_amount'] - $total_amount;
            $orderinfo['total_amount'] = $total_amount;

        } else if ($invoice['amount'] && $invoice['amount'] < $orderinfo['total_amount']) {
            // 如果开票金额小于订单金额
            $pmt_tmp = $orderinfo['total_amount'] - $invoice['amount'];
            if ($pmt_tmp > $orderinfo['shipping']['cost_shipping']) {
                $pmt_tmp -= $orderinfo['shipping']['cost_shipping'];
            }

            $orderinfo['pmt_order'] += $pmt_tmp;

            $orderinfo['total_amount'] = $invoice['amount'];
        }


        $sales_data = kernel::single('ome_sales_data')->generate($orderinfo, -1);

        // 保存开票内容
        if ($invoice['id'] && $invoice['order_id']) {
            $invMdl = app::get('invoice')->model('order');

            $invMdl->update(['itemsdf' => json_encode($sales_data)],['id' => $invoice['id'],'order_id'=>$invoice['order_id'],'is_status'=>'0']);
        }

        return $sales_data;
    }
    
    
    /**
     * 获取开票数据
     * @Author: xueding
     * @Vsersion: 2023/6/5 下午6:24
     * @param $params
     * @return bool|mixed|null
     */
    public function getInvoiceData($params)
    {
        $id = $params['id'];
        if (!$id) {
            return false;
        }
        $invoiceMdl = app::get('invoice')->model('order');
        $invoiceItemMdl = app::get('invoice')->model('order_items');
        $invoice = $invoiceMdl->db_dump($id);
    
        if (in_array($invoice['sync'], array('3','4', '5')) && $invoice['itemsdf']) {
            #冲红使用
            $sales_data = json_decode($invoice['itemsdf'], 1);
            if($sales_data) {
                return $sales_data;
            }
        }
        
        $itemList = $invoiceItemMdl->getList('*,bm_id as product_id,bn as sales_material_bn,amount as sales_amount,quantity as nums',['id'=>$id,'is_delete'=>'false']);
        foreach ($itemList as $key => $val) {
            //开票明细税率为空使用渠道配置税率进行开票
            if ($val['tax_rate'] == 0 && $params['tax_rate'] > 0) {
                $invoiceItemMdl->update(['tax_rate'=>$params['tax_rate']],['item_id'=>$val['item_id']]);
            }
        }
        if ($invoice['invoice_type'] == 'merge') {
            $itemList = kernel::single('invoice_order')->showMergeInvoiceItems($itemList);
        }
        $invoice['sales_items'] = $itemList;
    
        if ($invoice['id']) {
            unset($invoice['itemsdf']);
            $invoiceMdl->update(['itemsdf' => json_encode($invoice)],['id' => $invoice['id'],'is_status'=>'0']);
        }
        
        return $invoice;
    }
}
