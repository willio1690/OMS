<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2016/5/6
 * @describe 发票 相关请求接口类
 */
class erpapi_shop_matrix_taobao_request_invoice extends erpapi_shop_request_invoice 
{
    /**
     * 发票上传组织数据
     * 
     * @return void
     * @author 
     */

    protected function getUploadParams($sdf)
    {
        $params = parent::getUploadParams($sdf);

        $plat = array(
            '360buy' => '10001',
            'taobao' => '10002',
            'suning' => '10003',
            'amazon' => '10004',
            'other'  => '30001',
        );

        $order_type = $plat[$this->__channelObj->channel['node_type']];
        $params['order_type'] = $order_type ? $order_type : $plat['other'];

        return $params;
    }

    /**
     * 电子发票回传平台
     * 
     * @return void
     * @author 
     */
    public function upload($sdf, $sync = false)
    {
        // 回传前的准备工作
        $prepare = $this->upprepare($sdf);

        if ($prepare['rsp'] != 'succ') {
            return $prepare;
        }
        //汇付上传天猫发票
        if ($sdf['invoice']['channel_type'] == 'huifu') {
            app::get('invoice')->model('order_electronic_items')->update(array('upload_tmall_status'=>'2'),array('item_id'=>$sdf['electronic']['item_id']));
            return $prepare;
        }

        return parent::upload($sdf, $sync);
    }

    /**
     * 回传前的准备工作
     * 
     * @return void
     * @author 
     */
    public function upprepare($sdf)
    {
        $invoice    = $sdf['invoice'];
        $electronic = $sdf['electronic'];

        $params = array(
          "tid"                 => $invoice['order_bn'],
          "invoice_action_type" => $electronic['invoice_action_type'],
          "invoice_type"        => $electronic['billing_type'] ? 1 : 2, # 发票类型 1-蓝票 2-红票
          "serial_no"           => $electronic['serial_no'],
          "invoice_title"       => $invoice['title'],
        );

        if ($params['invoice_type'] == 1) {
            $params['invoice_action_type'] = 1;
        } else {
            $params['invoice_action_type'] = $electronic['invoice_action_type'] && $electronic['invoice_action_type'] != 1 ? $electronic['invoice_action_type'] : 2;
        }
        //汇付发票上传
        if (isset($invoice['channel_type']) && $invoice['channel_type'] == 'huifu') {
            $pdf_url = '';
            if ($electronic['file_path']) {
                $filePath = json_decode($electronic['file_path'], true);
                $pdf_url = $filePath['pdf_url'];
            }
            $params  = [
                'tid'               => $invoice['order_bn'],
                'taxfree_amount'    => round($invoice['amount'],2),
                'upload_file'       => $pdf_url,
                'invoice_title'     => $invoice['title'],
                'invoice_memo'      => $invoice['remarks'],
                'business_type'     => $invoice['ship_tax'] ? 1 : 0,//发票类型：0-个人，1-企业
                'invoice_code'      => $electronic['invoice_code'],
                'invoice_no'        => $electronic['invoice_no'],
                'created'           => date('Ymd', $invoice['dateline']),//"开票日期：20241023",
                'payee_register_no' => $invoice['ship_tax'],
            ];
            return kernel::single('erpapi_router_request')->set('invoice', $invoice['shop_id'])->invoice_upload($params);
        }

        $title = "天猫电子发票状态更新";

        return $this->__caller->call(EINVOICE_INVOICE_PREPARE,$params,null,$title,10,$sdf['invoice']['order_bn']);
    }
}
