<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2016/5/6
 * @describe 发票 相关请求接口类
 */
class erpapi_shop_request_invoice extends erpapi_shop_request_abstract {

    #获取发票抬头
    /**
     * 获取OrderInvoice
     * @param mixed $order_bn order_bn
     * @return mixed 返回结果
     */

    public function getOrderInvoice($order_bn) {
        if(!$order_bn) return false;
        $param = array(
            'tid' => $order_bn,
        );
        $title = '获取订单(' . $order_bn . ')发票抬头';
        $result = $this->__caller->call(SHOP_GET_TRADE_INVOICE_RPC, $param, array(), $title, 5, $order_bn);
        if($result['data']) {
            $result['data'] = json_decode($result['data'], true);
        }
        return $result;
    }

    /**
     * 添加Group
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function addGroup($params) {
        $shopBn = $this->__channelObj->channel['shop_bn'];
        $sdf = array(
            'group_name' => 'einvoice'
        );
        $title = '店铺（' . $this->__channelObj->channel['name'] .'）电子发票添加分组';
        $result = $this->__caller->call(SHOP_TMC_GROUP_ADD, $sdf, array(), $title, 10, $shopBn);
        if($result['data']) {
            $result['data'] = json_decode($result['data'], true);
        }
        return $result;
    }

    /**
     * 获取ApplyInfo
     * @param mixed $params 参数
     * @return mixed 返回结果
     */
    public function getApplyInfo($params) {
        $sdf = $this->getApplyInfoFormat($params);

        $apiName = $this->getApplyInfoApiname();

        $title = $this->__channelObj->channel['name'] . '-获取发票信息';
        if (isset($sdf['page']) && isset($sdf['size'])) {
            $title .= '，第'. ($sdf['page'] / $sdf['size'] + 1) . '页，时间范围：'.date('Y-m-d H:i:s',$sdf['start_time']).' ~ '.date('Y-m-d H:i:s',$sdf['end_time']);
        }
        $result = $this->__caller->call($apiName, $sdf, array(), $title, 10, $sdf['platform_tid']);
        if($result['data']) {
            $tmpData = json_decode($result['data'], true);
            if (isset($tmpData['apply'])) {
                $result['data'] = $tmpData['apply'];
            }else{
                $result['data'] = $tmpData;
            }
        }
        return $result;
    }

    /**
     * 获取ApplyInfoFormat
     * @param mixed $params 参数
     * @return mixed 返回结果
     */
    public function getApplyInfoFormat($params)
    {
        $sdf = array(
            'platform_tid' => $params['order_bn']
        );
        return $sdf;
    }

    /**
     * 获取ApplyInfoApiname
     * @return mixed 返回结果
     */
    public function getApplyInfoApiname()
    {
        return SHOP_EINVOICE_APPLY_GET;
    }

    /**
     * 电子发票回传平台
     * 
     * @return void
     * @author 
     */
    public function upload($sdf, $sync = false)
    {
        $params = $this->getUploadParams($sdf);

        $callback = array();

        // 异步
        if ($sync == false) {
            $callback = array(
                'class'  => get_class($this),
                'method' => 'uploadCallback',
                'params' => array(
                    'electronic_item_id' => $sdf['electronic']['item_id'],
                )
            );

            $retry = array(
                'obj_bn'     => $sdf['electronic']['invoice_no'],
                'obj_type'   => 'upload_invoice',
                'channel'    => 'shop',
                'channel_id' => $this->__channelObj->channel['shop_id'],
                'method'     => 'invoice_upload',
                'args'       => func_get_args()
            );
            $apiFailId = app::get('erpapi')->model('api_fail')->saveRunning($retry);
            if($apiFailId) {
                $callback['params']['api_fail_id'] = $apiFailId;
            }
        }
        $apiName = $this->getUploadApiname();

        $rs = $this->__caller->call($apiName,$params,$callback,'电子发票回传',10,$sdf['invoice']['order_bn']);

        if ($sync == true) {
            $this->uploadCallback($rs, array('electronic_item_id' => $sdf['electronic']['item_id']));
        }

        return $rs;
    }


    /**
     * 获取UploadApiname
     * @return mixed 返回结果
     */
    public function getUploadApiname()
    {
        return EINVOICE_DETAIL_UPLOAD;
    }

    /**
     * summary
     * 
     * @return void
     * @author 
     */
    public function uploadCallback($ret, $callback_params)
    {
        $status          = $ret['rsp'];

        if ($status == 'succ' && $callback_params['electronic_item_id']){
            app::get('invoice')->model('order_electronic_items')->update(array('upload_tmall_status'=>'2'),array('item_id'=>$callback_params['electronic_item_id']));
        }

        return $this->callback($ret, $callback_params);
    }

    /**
     * summary
     * 
     * @return void
     * @author 
     */
    protected function getUploadParams($sdf)
    {
        $invoice    = $sdf['invoice'];
        $electronic = $sdf['electronic'];

        $params = array(
            'invoice_type'      => $electronic['billing_type'], # 发票类型 1-蓝票 2-红票                 必须
            'tid'               => $invoice['order_bn'], # 订单编号                    必须
            'order_type'        => $this->__channelObj->channel['node_type'],  # 订单类型                    必须
            'payee_register_no' => $invoice['tax_no'], # 销货方识别号（税号）        必须
            'payee_name'        => $invoice['payee_name'],  # 销货方公司名称              必须
            'payee_address'     => $invoice['address'],   # 销货方公司地址   
            'payee_phone'       => $invoice['telephone'], # 销货方电话
            'payee_bankname'    => $invoice['bank'],  # 销货方公司开户行
            'payee_bankaccount' => $invoice['bank_no'],   # 销货方公司银行账户
            'payee_operator'    => $invoice['payee_operator'],  # 开票人
            'payee_receiver'    => '',  # 收款人
            'invoice_title'     => $invoice['title'],   # 发票抬头                    必须 
            'taxfree_amount'    => round($invoice['amount'],2),  # 开票金额 两位小数w
            'invoice_time'      => date('Y-m-d',$electronic['create_time']),    # 开票时间 yyyy-MM-dd         必须
            'ivc_content_type'  => '',    # 开票内容编号
            'ivc_content_name'  => '',    # 开票内容名称
            'invoice_code'      => $electronic['invoice_code'],    # 发票代码                    必须
            'invoice_no'        => $electronic['invoice_no'],  # 发票号码                    必须
            'invoice_memo'      => '',    # 发票备注
            'blue_invoice_code' => (string)$electronic['normal_invoice_code'],    # 原蓝票发票代码                开红票的时候必须传
            'blue_invoice_no'   => (string)$electronic['normal_invoice_no'],  # 原蓝票发票号码                开红票的时候必须传
            'pdf_info'          => $electronic['url'], # 发票PDF文件二进制流base64   必须
        );

        if ($sdf['items']) {
            $items = array();
            foreach ($sdf['items'] as $value) {
                $items[] = array(
                    'item_no'              => '', # 货号
                    'item_name'            => $value['spmc'], # SKU商品名称
                    'num'                  => $value['spsl'], # 数量 
                    'price'                => round($value['spdj'],2), # 单价 
                    'spec'                 => '', # 规格 
                    'unit'                 => $value['dw'], # 单位 
                    'tax_rate'             => $value['sl'], # 税率 两位小数
                    'tax_categroy_code'    => $value['spbm'], # 税收分类编码
                    'is_tax_discount'      => $value['yhzcbs'], # 优惠政策标识 0-不使用 1-使用
                    'tax_discount_content' => $value['zzstsgl'], # 增值税特殊管理 当优惠政策标识为1时填写 
                    'zero_tax'             => $value['lslbs'], # 零税率标识 空-非零税率 0-出口退税 1-免税 2-不征收 3-普通零税率
                    'deductions'           => '', # 扣除额 两位小数
                    'imei'                 => '', # 商品IMEI码
                    'discount'             => 0, # 折扣
                    'freight'              => 0, # 运费
                );
            }
            $params['invoice_items'] = json_encode($items);
        }

        return $params;
    }

}
