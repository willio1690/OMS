<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_logistics_matrix_dewu_request_electron extends erpapi_logistics_request_electron
{
    protected $directNum = 1;

    /**
     * bufferRequest
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function bufferRequest($sdf)
    {
        return $this->directNum;
    }

    /**
     * 得物急速现货（后续扩展品牌直发可通过类型区分）
     * @param $sdf
     * @return array|bool
     * @author db
     * @date 2023-05-05 10:53 上午
     */
    public function directRequest($sdf)
    {
        $this->primaryBn = $sdf['primary_bn'];
        $delivery        = $sdf['delivery'];
        $deliveryOrder   = $delivery['delivery_order'] ? current($delivery['delivery_order']) : array();

        $shop_type        = $deliveryOrder['shop_type'];
        $performance_type = $delivery['performance_type'];
        $order_bool_type  = $deliveryOrder['order_bool_type'];

        if (strtolower($shop_type) != 'dewu') {
            return [];
        }

        if (kernel::single('ome_order_bool_type')->isDWJISU($order_bool_type)) {
            // 急速现货
            $this->title = '得物急速现货-' . $this->__channelObj->channel['logistics_code'] . '获取电子面单';
            $params      = array(
                'order_bn' => $deliveryOrder['order_bn'],
            );

            $result       = $this->requestCall(STORE_GET_DISPATCH_NUMBER_BY_ORDERNO, $params);
            $returnResult = $this->backToResult($result, $delivery);

        } elseif (kernel::single('ome_order_bool_type')->isDWBrand($order_bool_type)) {
            // 品牌直发 多仓和普通履约
            if (!in_array($performance_type, ['1', '3'])) {
                return [];
            }
            $this->title = '得物品牌直发-' . $this->__channelObj->channel['logistics_code'] . '获取电子面单';
            $params      = array(
                'order_bn' => $deliveryOrder['order_bn'],
                'type'     => 2, // 1:获取运单号并发货(默认) 2:只获取运单号不发货
                // 'order_no_list' => [], // 最多支持8个订单，如果多个订单，此时为合并发货，注意商家是否有合并发货权限
            );

            $result = $this->requestCall(STORE_ORDER_BRAND_DELIVER_LOGISTIC_NO, $params);

            $returnResult = $this->backToResult2($result, $delivery);

        } else {
            return [];
        }

        return $returnResult;
    }

    private function backToResult($back, $delivery)
    {
        $data = empty($back['data']) ? '' : json_decode($back['data'], true);

        if ($back['rsp'] != 'succ') {
            return $back['res'] ? $back['res'] : false;
        }
        $result = array();

        $logi_no = $data['dispatch_num'];

        if (!$logi_no) {
            return false;
        }

        // 物流单号获取成功以后，去获取电子面单信息，保存到sdb_logisticsmanager_waybill_extend表
        $reswaybill  = $this->getEncryptPrintData($delivery);
        $json_packet = [];

        if ($reswaybill['rsp'] == 'succ') {
            $json_packet = [
                'dewu_express' => $reswaybill['data'],
            ];
        }

        $result[] = array(
            'succ'        => $logi_no ? true : false,
            'msg'         => '',
            'delivery_id' => $delivery['delivery_id'],
            'delivery_bn' => $delivery['delivery_bn'],
            'logi_no'     => $logi_no,
        );
        $this->directDataProcess($result);

        return $result;
    }

    private function backToResult2($back, $delivery)
    {
        $data = empty($back['data']) ? '' : json_decode($back['data'], true);

        if ($back['rsp'] != 'succ') {
            return $back['res'] ? $back['res'] : false;
        }
        $result = array();

        $logi_no   = $data['logistics_no'];
        $logi_code = $data['logistics_code'];

        if (!$logi_no || !$logi_code) {
            return false;
        }

        // 物流单号获取成功以后，去获取电子面单信息，保存到sdb_logisticsmanager_waybill_extend表
        $reswaybill  = $this->getEncryptPrintData($delivery);
        $json_packet = [];

        if ($reswaybill['rsp'] == 'succ') {
            $json_packet = [
                'dewu_express' => $reswaybill['data'],
            ];
        }

        $result[] = array(
            'succ'        => $logi_no ? true : false,
            'msg'         => '',
            'delivery_id' => $delivery['delivery_id'],
            'delivery_bn' => $delivery['delivery_bn'],
            'logi_no'     => $logi_no,
            'logi_code'   => $logi_code,
            'json_packet' => json_encode($json_packet),
        );
        $this->directDataProcess($result);

        return $result;
    }

    /**
     * 获取EncryptPrintData
     * @param mixed $sdf sdf
     * @return mixed 返回结果
     */
    public function getEncryptPrintData($sdf)
    {
        // ['logi_no'=>$logiNo,'batch_logi_no'=>$batchLogiNo,'delivery_id'=>$deliveryId]
        $orderMdl         = app::get('ome')->model('orders');
        $deliveryOrderMdl = app::get('ome')->model('delivery_order');

        $deliveryOrder = $deliveryOrderMdl->db_dump(['delivery_id' => $sdf['delivery_id']]);
        $orderInfo     = $orderMdl->db_dump(['order_id' => $deliveryOrder['order_id']]);
        if (!$orderInfo) {
            $error_msg = '订单信息不存在';
            return ['rsp' => 'fail', 'msg' => $error_msg];
        }
        $order_bn = $orderInfo['order_bn'];

        if (strtolower($orderInfo['shop_type']) != 'dewu' || !kernel::single('ome_order_bool_type')->isDWBrand($orderInfo['order_bool_type'])) {
            $error_msg = $order_bn . '仅支持品牌直发订单';
            return ['rsp' => 'fail', 'msg' => $error_msg];
        }
        $dlyCorp = app::get('ome')->model('dly_corp')->db_dump(array('corp_id'=>$sdf['logi_id']), 'channel_id,prt_tmpl_id');
        if($dlyCorp['channel_id'] != $this->__channelObj->channel['channel_id']) {
            $prtTmpl = app::get('ome')->model('dly_corp_channel')->db_dump(
                array('channel_id'=>$this->__channelObj->channel['channel_id'], 'corp_id'=>$sdf['logi_id']), 'prt_tmpl_id');
            if($prtTmpl) {
                $dlyCorp['prt_tmpl_id'] = $prtTmpl['prt_tmpl_id'];
            }
        }

        $templateObj = app::get("logisticsmanager")->model('express_template');
        $printTpl = $templateObj->db_dump(array('template_id'=>$dlyCorp['prt_tmpl_id']), 'template_type');

        $title      = '获取打印面单';
        $timeout    = 30;
        $primary_bn = $order_bn;
        $print_type = 1;
        $version    = 2; // 如果用老样式，打印纸头部蓝色logo区域也会被打印上字
        if($printTpl['template_type'] == 'dewu_ppzf_zy') {
            $param = [
                'order_bn'   => $order_bn, // 订单号
            ];
        } else {
            //请求参数
            $param = [
                'order_bn'   => $order_bn, // 订单号
                'print_type' => $print_type, // 是否返回绘制好的面单pdf文件,0:不返回(默认),1:返回
                'version'    => $version, // 打印面单样式版本,1:老样式,2:新样式，贴纸自带品牌直发字样。不传默认老样式
            ];
        }

        $result = $this->__caller->call(STORE_ORDER_BRAND_DELIVER_EXPRESS_SHEET, $param, [], $title, $timeout, $primary_bn);

        if ($result['data']) {
            $result['data'] = json_decode($result['data'], true);

            foreach ($result['data'] as $k => $v) {
                // 过滤特殊字符，解决页面解json失败的问题
                if ($v['dest_address']['detailed_address']) {
                    $result['data'][$k]['dest_address']['detailed_address'] = str_replace(["\r", "\n", '"', "'", '“', '”', '‘', '’',"\t"], ' ', $v['dest_address']['detailed_address']);
                }
                if ($v['specification']) {
                    $result['data'][$k]['specification'] = str_replace(["\r", "\n", '"', "'", '“', '”', '‘', '’', "\t"], ' ', $v['specification']);
                }
            }

        }

        return $result;
    }

}
