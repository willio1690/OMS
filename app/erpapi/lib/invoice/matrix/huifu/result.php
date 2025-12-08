<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_invoice_matrix_huifu_result extends erpapi_result
{
    // 是否为公共接口返回开关
    public $is_command = false;
    // 公共格式返回解析字段名
    public $response_method_keys = null;
    // code标记字段
    public $code_key = null;
    // msg标记字段
    public $message_key = null;
    // mock,调试用
    public $is_mock = false;
    // mock 方法
    public $mock_method = null;

    /**
     * 设置_response
     * @param mixed $response response
     * @param mixed $format format
     * @return mixed 返回操作结果
     */
    public function set_response($response, $format)
    {

        $response = $this->__mock($response);
        $rs       = parent::set_response($response, $format);

        return $rs;
    }

    private function __mock($response)
    {

        if (!$this->is_mock || !$this->mock_method) {
            return $response;
        }

        // 开票回传
        if ($this->mock_method == STORE_INVOICE_ISSUE) {
            //   $str = '{"res":"","msg_id":"662217E07F0000013E12199A89B97492","err_msg":"","data":{"method":"issue","response":{"success":[{"mulPurchaserMark":"N","invoiceTotalPrice":"167.92","invoiceDate":"2024-04-19 11:20:35","invoiceTotalPriceTax":"178.0","invoiceTypeCode":"02","invoiceDetailsList":[{"goodsPrice":"167.924528302","goodsSpecification":"规格型号","discountIndex":null,"goodsTotalPrice":"167.924528302","goodsLineNo":1,"goodsTaxRate":"6.0","ext":{},"goodsQuantity":"1.0","goodsUnit":"份","goodsName":"餐饮服务","invoiceLineNature":"0","goodsCode":"3070401000000000000","goodsTotalTax":"10.0754716981"}],"eInvoiceUrl":"https://mobl-test.chinaums.com/fapiao-portal/d/PeRH6ZeA152F864","invoiceNo":"65019758","serialNo":"28c73016c9a994f80280","invoiceTotalTax":"10.08","invoiceQrCode":"20240419d351c931c6a54e2a981460436aa7e97f"}]},"success":true,"requestId":"662217E07F0000013E12199A89B97492"},"rsp":"succ"}';

            // 蓝字待开票
            //$str = '{"res":"","msg_id":"662217E07F0000013E12199A89B97492","err_msg":"","data":{"method":"issue","response":{"success":[{"mulPurchaserMark":"N","invoiceTotalPrice":"167.92","invoiceDate":"2024-04-19 11:20:35","invoiceTotalPriceTax":"178.0","invoiceTypeCode":"02","invoiceStatus":"99","invoiceDetailsList":[{"goodsPrice":"167.924528302","goodsSpecification":"规格型号","discountIndex":null,"goodsTotalPrice":"167.924528302","goodsLineNo":1,"goodsTaxRate":"6.0","ext":{},"goodsQuantity":"1.0","goodsUnit":"份","goodsName":"餐饮服务","invoiceLineNature":"0","goodsCode":"3070401000000000000","goodsTotalTax":"10.0754716981"}],"eInvoiceUrl":"https://mobl-test.chinaums.com/fapiao-portal/d/PeRH6ZeA152F864","invoiceNo":"","invoiceType":"0","serialNo":"INV202405040000002","invoiceTotalTax":"10.08","invoiceQrCode":"20240419d351c931c6a54e2a981460436aa7e97f"}]},"success":true,"requestId":"662217E07F0000013E12199A89B97492"},"rsp":"succ"}';

            // 红冲中
            $str = '{"res":"","msg_id":"662DFB1DAC10001447D4CDD21293D12C","err_msg":"","data":{"method":"reverse","response":{"success":[{"mulPurchaserMark":"N","invoiceTotalPrice":"106.57","invoiceDate":"2024-04-26 19:22:58","invoiceTotalPriceTax":"120.42","invoiceTypeCode":"02","invoiceStatus":"20","invoiceDetailsList":[{"goodsPrice":"67.0973451327","goodsSpecification":"7109.30","discountIndex":null,"goodsTotalPrice":"67.0973451327","goodsLineNo":1,"goodsTaxRate":"13.0","ext":{},"goodsQuantity":"1.0","goodsUnit":"条","goodsName":"咖啡胶囊","invoiceLineNature":"0","goodsCode":"1030307060000000000","goodsTotalTax":"8.72265486726"},{"goodsPrice":"39.4690265487","goodsSpecification":"7381.30","discountIndex":null,"goodsTotalPrice":"39.4690265487","goodsLineNo":2,"goodsTaxRate":"13.0","ext":{},"goodsQuantity":"1.0","goodsUnit":"条","goodsName":"咖啡胶囊","invoiceLineNature":"0","goodsCode":"1030307060000000000","goodsTotalTax":"5.13097345133"}],"eInvoiceUrl":"https://fapiao.chinaums.com/d/PnwIkirBFA28394","invoiceNo":"24312000000118505557","invoiceType":"0","serialNo":"INV202405040000002","invoiceTotalTax":"13.85","invoiceQrCode":"2024042649ece0ef86c14cca8eb186572d218db5"}]},"success":true,"requestId":"662DFB1DAC10001447D4CDD21293D12C"},"rsp":"succ"}';
            return $str;
        }


        if ($this->mock_method == INVOICE_REVERSE_APPLICATION_CREATE) {
            $str = '{"rsp":"fail","msg_id":"66305C25AC100014D6132A5F222BFEFD","data":{"resultMsg":"发票渠道异常：137****5071","msgId":"66305C25AC100014D6132A5F222BFEFD","subList":[],"sign":"9292AC039F5921BFE0A35A2BFB01368B0AA1FA791E4AFF451316142526ADAFF5","srcReserve":"","msgSrc":"NESTLE","msgType":"reverse.confirmedInfoApply","resultCode":"API_ERROR","responseTimestamp":"2024-04-30 10:49:43"},"err_msg":"发票渠道异常：137****5071","res":"API_ERROR","response":{"res":"API_ERROR","msg_id":"66305C25AC100014D6132A5F222BFEFD","err_msg":"发票渠道异常：137****5071","data":"{\"resultMsg\": \"\\u53d1\\u7968\\u6e20\\u9053\\u5f02\\u5e38\\uff1a137****5071\", \"msgId\": \"66305C25AC100014D6132A5F222BFEFD\", \"subList\": [], \"sign\": \"9292AC039F5921BFE0A35A2BFB01368B0AA1FA791E4AFF451316142526ADAFF5\", \"srcReserve\": \"\", \"msgSrc\": \"NESTLE\", \"msgType\": \"reverse.confirmedInfoApply\", \"resultCode\": \"API_ERROR\", \"responseTimestamp\": \"2024-04-30 10:49:43\"}","rsp":"fail"},"request_url":"http://rpc.ex-sandbox.com/sync","params":{"entryIdentity":"01","redInvoiceLabel":"01","invoiceDate":"20240426","serialNo":"6f4b3315bae5f96e1a73","app_id":"ecos.ome","method":"store.einvoice.red.add","date":"2024-04-30 10:49:42","format":"json","certi_id":"1598511536","v":"1","from_node_id":"1072110139","to_node_id":"1792150333","node_type":"chinaums","sign":"171EF7603340803BD1519F4BB1D62BAA"},"msg":"发票渠道异常：137****5071"}';
            return $str;
        }

        // 开票查询
        if ($this->mock_method == EINVOICE_INVOICE_GET) {
            // 开蓝成功
            //$str = '{"data":{"method":"query","response":{"success":[{"invoiceCode":"79197498","invoiceDate":"2024-04-29 16:59:26","invoiceTotalPriceTax":"136.0","invoiceTypeCode":"02","invoiceStatus":"00","invoiceDetailsList":[{"goodsPrice":"60.1769911504","goodsSpecification":"FLAT/ZNCS100-3M","discountIndex":null,"goodsTotalPrice":"60.1769911504","goodsLineNo":1,"goodsTaxRate":"13.0","ext":{},"goodsQuantity":"1.0","goodsUnit":"","goodsName":"Barista Creations Sweet Vanilla R60","invoiceLineNature":"0","goodsCode":"1030307060000000000","goodsTotalTax":"7.82300884956"},{"goodsPrice":"1.20353982301","goodsSpecification":"8901.83","discountIndex":null,"goodsTotalPrice":"60.1769911504","goodsLineNo":2,"goodsTaxRate":"13.0","ext":{},"goodsQuantity":"50.0","goodsUnit":"颗","goodsName":"Barista Creations Golden Caramel R60","invoiceLineNature":"0","goodsCode":"1030307060000000000","goodsTotalTax":"7.82300884956"}],"eInvoiceUrl":"https://mobl-test.chinaums.com/fapiao-portal/d/PoimZsWDCAFB5D3","invoiceNo":"244006587706","invoiceType":"0","serialNo":"INV202405040000002","invoiceTotalTax":"15.64","invoiceQrCode":"2024042947dbde12bea54f2d9417d5aa17817170","invoiceTotalPrice":"120.36"}]},"success":true,"requestId":"6630B437AC100014B4C58B411B8691F9"},"err_msg":"","msg_id":"6630B437AC100014B4C58B411B8691F9","res":"","rsp":"succ"}';

            // 开红成功
            $str = '{"res":"","msg_id":"662DFB1DAC10001447D4CDD21293D12C","err_msg":"","data":{"method":"reverse","response":{"success":[{"mulPurchaserMark":"N","invoiceTotalPrice":"106.57","invoiceDate":"2024-04-26 19:22:58","invoiceTotalPriceTax":"120.42","invoiceTypeCode":"02","invoiceStatus":"05","invoiceDetailsList":[{"goodsPrice":"67.0973451327","goodsSpecification":"7109.30","discountIndex":null,"goodsTotalPrice":"67.0973451327","goodsLineNo":1,"goodsTaxRate":"13.0","ext":{},"goodsQuantity":"1.0","goodsUnit":"条","goodsName":"咖啡胶囊","invoiceLineNature":"0","goodsCode":"1030307060000000000","goodsTotalTax":"8.72265486726"},{"goodsPrice":"39.4690265487","goodsSpecification":"7381.30","discountIndex":null,"goodsTotalPrice":"39.4690265487","goodsLineNo":2,"goodsTaxRate":"13.0","ext":{},"goodsQuantity":"1.0","goodsUnit":"条","goodsName":"咖啡胶囊","invoiceLineNature":"0","goodsCode":"1030307060000000000","goodsTotalTax":"5.13097345133"}],"eInvoiceUrl":"https://fapiao.chinaums.com/d/PnwIkirBFA28394","invoiceNo":"24312000000118505557","invoiceType":"1","serialNo":"INV202405040000002","invoiceTotalTax":"13.85","invoiceQrCode":"2024042649ece0ef86c14cca8eb186572d218db5"}]},"success":true,"requestId":"662DFB1DAC10001447D4CDD21293D12C"},"rsp":"succ"}';
            return $str;
        }

    }

}
