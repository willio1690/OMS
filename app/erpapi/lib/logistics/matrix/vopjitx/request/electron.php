<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_logistics_matrix_vopjitx_request_electron extends erpapi_logistics_request_electron
{
    protected $directNum = 1;

    /**
     * bufferRequest
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function bufferRequest($sdf){
        return $this->directNum;
    }

    /**
     * directRequest
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function directRequest($sdf) {
        $this->primaryBn = $sdf['primary_bn'];
        $delivery = $sdf['delivery'];
        // $deliveryOrder = $delivery['delivery_order'] ? current($delivery['delivery_order']) : array();

        // 如果是合单，根据订单号获取物流单号的时候，用第一个订单号去获取
        if (explode('|', $delivery['order_bn'])>1) {
            $delivery['order_bn'] = array_filter(explode('|', $delivery['order_bn']))[0];
        }
        $params = array(
            'tid'     => $delivery['order_bn'],
            'limit'   => 1,
            'shop_id' => $delivery['shop_id'],
        );

        $result = $this->requestCall(STORE_JITX_WAYBILL_GET, $params);

        $returnResult = $this->backToResult($result, $delivery);

        return $returnResult;
    }

    private function backToResult($back, $delivery){
        $data = empty($back['data']) ? '' : json_decode($back['data'], true);

        if($back['rsp'] == 'fail' || !$data['msg']) {
            return $back['msg'] ? $back['msg'] : false;
        }
        $msg = json_decode($data['msg'],true);

        $result = array();
        $logi_no = current($msg['result']);
        if (!$logi_no) return false;

        $delivery['logi_no'] = $logi_no;

        $waybillExtendData = $this->waybillExtend($delivery); #获取大头笔
        $result[] = array(
            'succ'         => $logi_no? true : false,
            'msg'          => '',
            'delivery_id'  => $delivery['delivery_id'],
            'delivery_bn'  => $delivery['delivery_bn'],
            'logi_no'      => $logi_no,
            'position_no'  => $waybillExtendData['position'],#大头笔编码
            'position'     => $waybillExtendData['position_no'],#大头笔名称
            'package_wd'   => $waybillExtendData['package_wd'],#集包地编码
            'package_wdjc' => $waybillExtendData['package_wdjc'],#集包地名称
            'json_packet'  => $waybillExtendData['json_packet'],
        );

        $this->directDataProcess($result);
        
        return $result;
    }

    /**
     * waybillExtend
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function waybillExtend($sdf) {
        $this->title = 'vopjitx获取大头笔';
        $detail_list = array(
            'box_no'       => '1',
            'num'          => '1',
            'goods_info'   => array (),
            'transport_no' => $sdf['logi_no'],
            'tid'          => $sdf['order_bn'],
            'company_code' => $this->__channelObj->channel['logistics_code'],
        );

        foreach ($sdf['delivery_items'] as $item) {
            $detail_list['goods_info'][] = $item['bn'] . '*' . $item['number'];
        }
        $detail_list['goods_info'] = json_encode($detail_list['goods_info']);


        $params = array(
            'detail_list' =>json_encode(array ($detail_list)),
            'shop_id'     => $sdf['shop_id'],
        );

        $result = $this->requestCall(STORE_JITX_WAYBILL_PRINT,$params);

        $data = $result['data'] ? json_decode($result['data'], true):'';

        if($result['rsp'] =='fail' || empty($data['msg'])) {
            return false;
        }

        $msg = str_replace('\r\n', '', $data['msg']);
        $msg = @json_decode($msg,true);

        $list = @json_decode(str_replace(array("\n","\r","\r\n"),' ', $msg[0]['order_label']),true);

        $order_label = array ();
        foreach ($list as $key => $value) {
            if (is_array($value)) {
                $order_label[$value['fieldCode']] = $value['fieldValue'];
            } else {
                $order_label[$key] = $value;
            }
        }

        if (!$order_label) return false;

        $extend_data = array(
            'json_packet' => json_encode($order_label),
        );

        $extend_data['position_no'] = $order_label['pickCode'];#大头笔编码

        return $extend_data;
    }
}
