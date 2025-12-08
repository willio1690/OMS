<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_shop_matrix_youzan_request_finance extends erpapi_shop_request_finance
{
    protected function _updateRefundApplyStatusApi($status, $refundInfo=null)
    {
        $api_method = '';
        switch($status){
            // case '2':
            //     $api_method = SHOP_AGREE_REFUND;
            //     break;
            case '3':
                $api_method = SHOP_REFUSE_REFUND;
                break;
        }

        return $api_method;
    }

    protected function _updateRefundApplyStatusParam($refund,$status){
        $shop_id = $this->__channelObj->channel['shop_id'];

        $ref = app::get('ome')->model('refund_apply_youzan')->db_dump(array ('shop_id'=>$shop_id, 'refund_apply_bn'=>$refund['refund_apply_bn']),'refund_version');

    	$params = array ();
        $params['refund_id'] = $refund['refund_apply_bn'];
        $params['version']   = $ref['refund_version'];

        switch($status){
            case '2':
                $params['remark'] = '商家同意退款'; // 同意会自动触发退款
                break;
            case '3':
                $params['remark'] = '商家拒绝退款';
                break;
        }

        return $params;
    }

    /**
     * _getAddRefundParams
     * @param mixed $refund refund
     * @return mixed 返回值
     */
    public function _getAddRefundParams($refund){
        $addon = unserialize($refund['addon']);
        if (!$refund || ($addon['reship_id'] && !$refund['return_id'])) {
            return array();
        }

        $api_name = SHOP_AGREE_REFUND;

        $params = array (
            'refund_id' => $refund['refund_bn'],
            'remark'    => '商家同意退款',
        );

        $title = '商家退款(订单号:'.$refund['order_bn'].'退款单号:'.$refund['refund_bn'].')';

        $shop_id = $this->__channelObj->channel['shop_id'];
        if($refund['return_id']){
            $ref = app::get('ome')->model('return_product_youzan')->dump(array('shop_id'=>$shop_id,'return_id'=>$refund['return_id']));

            $params['version']   = $ref['refund_version'];
            $params['refund_id'] = $ref['return_bn'];

            // $params = array(
            //     'tid'        => $refund['order_bn'],
            //     'refund_fee' => $ref['refund_fee'],
            //     'oid'        => $ref['oid'],
            //     'version'    => $ref['refund_version'],
            //     'desc'       => '商家退款',
            // );
        }else{
            $ref = app::get('ome')->model('refund_apply_youzan')->db_dump(array ('shop_id'=>$shop_id, 'refund_apply_bn'=>$refund['refund_bn']));

            $params['version']   = $ref['refund_version'];

            // $params = array(
            //     'tid'        => $refund['order_bn'],
            //     'refund_fee' => $ref['refund_fee'],
            //     'oid'        => $ref['oid'],
            //     'version'    => $ref['refund_version'],
            //     'desc'       => '商家退款',
            // );
        }

        return array($api_name, $title, $params);
    }

    /**
     * 获取RefundMessage
     * @param mixed $refundinfo refundinfo
     * @return mixed 返回结果
     */
    public function getRefundMessage($refundinfo){
        if (!$refundinfo['refund_bn']) return false;
        $params = array(
            'refund_id'=>  $refundinfo['refund_bn'],
        );

        $title = '获取店铺退款凭证';
        $result = $this->__caller->call(SHOP_REFUND_MESSAGES_GET, $params, array(), $title, 10, $refundinfo['refund_bn']);

        if($result['data']) {
            $result['data']  = array (

                'refund_messages' => array (
                    'refund_message' => json_decode($result['data'], true),
                ),
            );
        }

        return $result;
    }

    /**
     * 添加RefundCallback
     * @param mixed $response response
     * @param mixed $callback_params 参数
     * @return mixed 返回值
     */
    public function addRefundCallback($response, $callback_params)
    {
        if ($response['rsp'] == 'succ') {
            $applyMdl = app::get('ome')->model('refund_apply');
            $apply = $applyMdl->dump($callback_params['refund_apply_id']);
            if ($apply['status'] == '0') {
                $applyMdl->update(array ('status' => '2'),array ('apply_id' => $apply['apply_id'],'status' => '0'));
            }
            // 判断是否是售后生成的
            if (in_array($apply['status'], array ('0','1','2','5','6')) && $apply['refund_refer'] == '1' && $apply['source'] == 'local' && $apply['return_id']) {
                kernel::single('ome_refund_apply')->refund_apply_accept($apply['apply_id'],array ('call_from' => 'erpapi'));
            }
        }


        return parent::addRefundCallback($response, $callback_params);
    }
}