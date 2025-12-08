<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @desc
 * @author: jintao
 * @since: 2016/8/1
 */
class erpapi_shop_matrix_taobao_request_logistics extends erpapi_shop_request_logistics {

    /**
     * 更新ReturnLogistics
     * @param mixed $reshipinfo reshipinfo
     * @return mixed 返回值
     */

    public function updateReturnLogistics($reshipinfo) {
        $orderModel = app::get('ome')->model('orders');
        $order = $orderModel->dump($reshipinfo['order_id'], 'order_bn');
        $confirm_result = '1';
        if ($reshipinfo['is_check'] == '9') {
            $confirm_result = '2';
        }
        $reship_bn = $reshipinfo['reship_bn'];
        #取退货单上的
        $oReturn_tmall = app::get('ome')->model('return_product_tmall');
        $return_tmall = $oReturn_tmall->dump(array('return_bn'=>$reship_bn));
        $params['refund_id']        = $reshipinfo['reship_bn'];
        $params['refund_phase '] = $return_tmall['refund_phase'];
        $params['confirm_result '] = $confirm_result;
        $params['company_code']=$reshipinfo['return_logi_name'];
        $params['sid'] = $reshipinfo['return_logi_no'];
        $params['operator']=kernel::single('desktop_user')->get_name();;
        $params['confirm_time']=date('Y-m-d H:i:s',$reshipinfo['t_end']);
        $callback = array(
            'class' => get_class($this),
            'method' => 'callback',
        );
        $title = '店铺('.$this->__channelObj->channel['name'].')回填退回物流单号物流公司'.'(订单号:'.$order['order_bn'].'退货单号:'.$reshipinfo['reship_bn'].')';;
        $rs = $this->__caller->call(SHOP_REFUND_GOOD_RETURN_CHECK,$params,$callback,$title,10,$order['order_bn']);
        return $rs;
    }

    /**
     * 获取CorpServiceCode
     * @param mixed $sdf sdf
     * @return mixed 返回结果
     */
    public function getCorpServiceCode($sdf) {
        $params = array(
            'cp_code' => $sdf['cp_code']
        );
        $title = '获取物流商服务类型';
        $result = $this->__caller->call(STORE_CN_WAYBILL_II_SEARCH,$params,array(),$title, 10, $params['cp_code']);
        return $result;
    }

    /**
     * timerule
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function timerule($sdf)
    {
        $params = [
            'api' => 'taobao.open.seller.biz.logistic.time.rule',
            'data' => json_encode([
                'last_pay_time' => date('H:i', $sdf['cutoff_time']),
                'last_delivery_time' => date('H:i', $sdf['latest_delivery_time']),
            ]),
        ];

        $title = '商家自定义发货时效';
        $result = $this->__caller->call(TAOBAO_COMMON_TOP_SEND,$params,array(),$title, 10, $this->__channelObj->channel['shop_bn']);
        return $result;
    }
}