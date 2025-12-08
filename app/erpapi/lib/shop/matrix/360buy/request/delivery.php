<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 发货单处理
 *
 * @category
 * @package
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_shop_matrix_360buy_request_delivery extends erpapi_shop_request_delivery
{

    protected $_delivery_errcode = array(
        'w06000'=>'成功',
        'w06001'=>'其他',
        'w06101'=>'已经出库',
        'w06102'=>'出库订单不存在或已被删除',
        'w06104'=>'订单状态不为等待发货',
        'w06105'=>'订单已经发货',
        'w06106'=>'正在出库中', 
    );

    /**
     * confirm
     * @param mixed $sdf sdf
     * @param mixed $queue queue
     * @return mixed 返回值
     */

    public function confirm($sdf,$queue=false)
    {
        // 检测是否为国补订单，如果是国补并且有sn码，回传sn码再通知发货
        if ($sdf['serial_number']) {

            $order_id = $sdf['orderinfo']['order_id'];
            $isGuobu  = kernel::single('ome_bill_label')->getBillLabelInfo($order_id, 'order', 'SOMS_GB');

            $serial_number_arr = $imei_number_arr = [];
            $shop_product_id = '';
            if ($isGuobu) {
                $delivery_bm_id = [];
                foreach ($sdf['delivery_items'] as $_delivery_items) {
                    if ($_delivery_items['bm_id']) {
                        $delivery_bm_id[$_delivery_items['bm_id']] = $_delivery_items['shop_product_id'];
                    }
                }
                foreach ($sdf['serial_number'] as $_bm_id => $_v) {
                    if ($delivery_bm_id[$_bm_id]) {
                        $serial_number_arr = $_v['sn'];
                        $imei_number_arr   = $_v['imei'];
                        $shop_product_id   = $delivery_bm_id[$_bm_id];
                        break;
                    }
                }
            }

            if ($serial_number_arr && $shop_product_id) {
                $serial_params = [
                    'order_bn'          =>  $sdf['orderinfo']['order_bn'],
                    'delivery_id'       =>  $sdf['delivery_id'],
                    'delivery_bn'       =>  $sdf['delivery_bn'],
                    'shop_product_id'   =>  $shop_product_id,
                    'serial_number_arr' =>  $serial_number_arr,
                    'imei_number_arr'   =>  $imei_number_arr,
                    'order_source'      =>  $sdf['orderinfo']['order_source'],
                ];
                $res = kernel::single('erpapi_router_request')->set('shop',$this->__channelObj->channel['shop_id'])->order_serial_sync($serial_params);
                // if ($res['rsp'] != 'succ') {
                //     return $this->error('唯一码上传失败');
                // }
            }
        }

        return parent::confirm($sdf,$queue);
    }

    /**
     * 发货请求参数
     *
     * @return void
     * @author 
     **/
    protected function get_confirm_params($sdf)
    {
        $param = parent::get_confirm_params($sdf);

        $param['360buy_business_type'] = $this->__channelObj->channel['addon']['type'];

        if ('SOPL' == $this->__channelObj->channel['addon']['type']) {
            $param['package_num'] = $sdf['itemNum'];
        }

        // 拆单回写
        $logi_no = array ();
        foreach ($sdf['delivery_items'] as $key => $value) {
            $logi_no[$value['logi_type']][$value['logi_no']] = $value['logi_no'];
        }

        foreach ($sdf['delivery_bill_list'] as $key => $value) {
            if(strpos($value['logi_no'], '-')) {
                continue;
            }
            $logi_no[$value['logi_type']][$value['logi_no']] = $value['logi_no'];
        }

        foreach ($logi_no as $key => $value) {
            $logi_no[$key] = implode(',',(array)$value);
        }

        if ($logi_no) {
            $param['company_code'] = implode('|',array_keys($logi_no));
            $param['logistics_no'] = implode('|',array_values($logi_no));
        }

        $order_id = $sdf['orderinfo']['order_id'];
        $fenxiao_order = kernel::single('ome_bill_label')->getBillLabelInfo($order_id, 'order', 'SOMS_FENXIAO');
        if ($fenxiao_order) {
            $param['360buy_is_dx'] = 'true';
        }

        return $param;
    }

   /**
     * 发货回调
     *
     * @return void
     * @author
     **/
    public function confirm_callback($response, $callback_params)
    {

        $failApiModel = app::get('erpapi')->model('api_fail');
        $order_id        = $callback_params['order_id'];
        $err_msg = $response['err_msg'];
        $rsp             = $response['rsp'];
        $rsp=='success' ? 'succ' : $rsp;
        if($callback_params['company_code'] == 'JDCOD'){

            if($rsp == 'fail' && ($err_msg == '运单没有在青龙系统生成' || $err_msg == '平台连接后端服务不可用')){
                $response['msg_code'] = 'G40012';
            }
        }
        $callback_params['obj_type'] = 'JDDELIVERY';
        $rs = parent::confirm_callback($response,$callback_params);
        return $rs;
    }

    protected function get_delivery_apiname($sdf)
    {
        
        if($sdf['is_jdzd']){
            return SHOP_LOGISTICS_CONSIGN_RESEND;
        }else{
            return SHOP_LOGISTICS_OFFLINE_SEND;
            
        }
        
    }
}