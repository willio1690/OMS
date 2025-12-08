<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2015-12-15
 * @describe 淘宝请求电子面单类
 */
class erpapi_logistics_matrix_aikucun_request_electron extends erpapi_logistics_request_electron
{
    protected $directNum = 10;

    /**
     * bufferRequest
     * @return mixed 返回值
     */

    public function bufferRequest(){
        return $this->directNum;
    }

    /**
     * 御城河
     *
     * @return void
     * @author
     **/
    private function __hchsafe($sdf)
    {
        // 御城河-运单直连
        if ($sdf['order_bns']) {
            $hchsafe = array(
                'to_node_id' => $this->__configObj->get_to_node_id(),
                'tradeIds'   => $sdf['order_bns'],
            );

            kernel::single('base_hchsafe')->order_push_log($hchsafe);
        }
    }


    public function directRequest($sdf){
        //接口升级，平台已弃用该接口。2022-04-18
        $params = array('tid' => $sdf['order_bn']);

        $back = $this->requestCall(STORE_LOGISTICS_JDALPHA_WAYBILL_RECEIVE, $params, array(), $sdf);
        return $this->backToResult($back, $sdf['delivery']);
    }

    private function backToResult($ret, $delivery){
        $data = empty($ret['data']) ? array() : json_decode($ret['data'], true);
        //$data = '{"status": "error", "message": "\u65b0\u589e\u8fd0\u5355\u6b21\u6570\u8d85\u8fc7\u9650\u5236", "code": -1, "data": {}}';
        if(is_array($data['msg'])){
            $waybill = $data['msg'];
        }else{
            return $data;
        }
        $result = array();
        $result[] = array(
            'succ' => $waybill['deliverNo'] ? true : false,
            'msg' => '',
            'delivery_id' => $delivery['delivery_id'],
            'delivery_bn' => $delivery['delivery_bn'],
            'logi_no' => $waybill['deliverNo'],
            'mailno_barcode' => '',
            'qrcode' => '',
            'position' => $waybill['receiverArea'],
            'position_no' => $waybill['sectionCode'],
            'package_wdjc' => '',
            'package_wd' => '',
            'print_config' => '',
            'json_packet' => '',
        );

        return $result;
    }





}