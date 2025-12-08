<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2018/12/7
 * @describe 京东供应商平台
 */
class erpapi_shop_matrix_jd_request_delivery extends erpapi_shop_request_delivery
{
    public function confirm($sdf,$queue=false)
    {
        //工小达发货地址预决策接口
        if (kernel::single('ome_bill_label')->getBillLabelInfo($sdf['delivery_id'], 'ome_delivery', kernel::single('ome_bill_label')->isSomsGxd())) {
            $res = $this->addAddress($sdf);
            if ($res['rsp'] != 'succ') {
                return $res;
            }
        }
        
        return parent::confirm($sdf,$queue);
    }
    
    /*
    public function logistics_update($sdf)
    {
        $title = '京东供应商平台出库';
        $params = array();
        $params['tid'] = $sdf['orderinfo']['order_bn'];
        $params['memo_by_vendor'] = $sdf['memo'];
        $params['is_jdexpress'] = $sdf['logi_type'] == '360buy' ? 1 : 2; #  是否是京东配送：1是，2否
        $rsp = $this->__caller->call(SHOP_OUT_BRANCH,$params, array(),$title,10,$params['tid']);
        return $rsp;
    }
    */

    public function add($sdf)
    {
       
        $title = '京东供应商平台出库';
        $params = array();
        $params['tid'] = $sdf['orderinfo']['order_bn'];
        $params['memo_by_vendor'] = $sdf['memo'];
        $params['is_jdexpress'] = $sdf['logi_type'] == '360buy' ? 1 : 2; #  是否是京东配送：1是，2否
        $rsp = $this->__caller->call(SHOP_OUT_BRANCH,$params, array(),$title,10,$params['tid']);
        return $rsp;
    }

    protected function get_confirm_params($sdf)
    {
        $param = parent::get_confirm_params($sdf);
        $param['estimate_date'] = date('Y-m-d H:i:s', (($sdf['delivery_time'] ? $sdf['delivery_time'] : time()) + 3 * 86400));
        return $param;
    }
    
    
    /**
     * 京东工小达发货地址预决策
     * @param $sdf
     * @date 2025-02-27 3:00 下午
     */
    public function addAddress($sdf)
    {
        $title = '京东工小达发货地址预决策';
        $area  = $sdf['branch']['area'];
        if ($area) {
            kernel::single('eccommon_regions')->split_area($area);
            $receiver_state    = $area[0] ?? '';
            $receiver_city     = $area[1] ?? '';
            $receiver_district = $area[2] ?? '';
            $area              = $receiver_state . $receiver_city . $receiver_district;
        }
        
        $deliveryAddress = $area . $sdf['branch']['address'];
        $order_bn        = $sdf['orderinfo']['order_bn'];
        $params = [
            'orderId'         => $order_bn,
            'deliveryAddress' => $deliveryAddress,//发货地址详情
        ];
        
        $rsp = $this->__caller->call(SHOP_JDGXD_CHOICE_LOGISTICS, $params, [], $title, 10, $order_bn);
        
        return $rsp;
    }
    
    /**
     * 获取发货单打印数据 pdf格式连接
     * @param $sdf
     * @return string[]|void
     * @date 2025-03-11 2:56 下午
     */
    public function getPrintDelivery($sdf)
    {
        $title = sprintf('获取配送清单pdf[%s]-%s', $sdf['delivery_bn'], $sdf['order_bn']);
        
        if (!$sdf['order_bn']) {
            return array('rsp' => 'fail', 'msg' => '订单号为空!');
        }
        $params     = ['orderId' => $sdf['order_bn']];
        $result     = $this->__caller->call(STORE_THIRDPDF_FORVENDER_GET, $params, [], $title, 10, $sdf['delivery_bn']);
        
        if ($result['rsp'] == 'succ') {
            if ($result['data'] && is_string($result['data'])) {
                $data           = json_decode($result['data'], true);
                $pdfUrl         = $data['pdfUrl'] ?? '';
                $result['data'] = json_encode(['data' => $pdfUrl]);
            }
        }
        return $result;
    }


}