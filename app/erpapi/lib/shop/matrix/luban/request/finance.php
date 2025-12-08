<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 抖音店铺退款业务请求Lib类
 */
class erpapi_shop_matrix_luban_request_finance extends erpapi_shop_request_finance
{
    protected function _updateRefundApplyStatusApi($status, $refund=NULL)
    {
        $api_method = '';
        switch ($status) {
            case '3':
                if ($refund['refund_refer'] == '1') {
                    $api_method = SHOP_REFUSE_AFTERSALE_REFUND;
                }else{
                    $api_method = SHOP_REFUSE_REFUND;
                }
                
                break;
        }

        return $api_method;
    }

    protected function _updateRefundApplyStatusParam($refund, $status)
    {
        $params = array(
            'oid' => $refund['oid'], //子订单号
            'aftersale_id' => $refund['refund_apply_bn'], //退款申请单号
        );
        
        //拒绝退款单(抖音需要传物流公司ID、物流单号)
        if ($status == '3' && $refund['order_id']) {
            // 查询包裹
            $dlyItemDetailMdl = app::get('ome')->model('delivery_items_detail');
            $dlyPackageMdl    = app::get('ome')->model('delivery_package');

            $items_detail = $dlyItemDetailMdl->getList('delivery_id,bn', ['order_id' => $refund['order_id'],'oid' => $refund['oid']]);
            foreach ($items_detail as $key => $value) {
                $package = $dlyPackageMdl->db_dump(['delivery_id' => $value['delivery_id'], 'bn' => $value['bn'], 'status' => 'delivery']);

                if ($package) {
                    $params['company_code'] = $package['logi_bn'];
                    $params['logistics_no'] = $package['logi_no'];

                    break;
                }
            }
            
            $file_url = "https://top.shopex.cn/ecos/statics/images/logo_taog.png"; //假图片地址
            
            $params['reason'] = ($refund['refuse_message'] ? $refund['refuse_message'] : '拒绝退款'); //拒绝原因
            $params['url'] = ($refund['refuse_proof'] ? $refund['refuse_proof'] : $file_url); //拒绝凭证图片url
            $params['desc'] = ($refund['refuse_message'] ? $refund['refuse_message'] : '拒绝退款申请'); //凭证描述
            
            //临时兼容
            $params['reason'] = '商品已发出，如买家不再需要请拒收后申请仅退款或收到后申请退货退款';
            $params['reject_reason_code'] = '1';
        }

        return $params;
    }

    /**
     * 添加交易退款单
     * 
     * @param array $refund
     * @return array
     */

    public function addRefund($refund)
    {
        $reshipObj = app::get('ome')->model('reship');
        $operLogObj = app::get('ome')->model('operation_log');
        $refundApplyMdl = app::get('ome')->model('refund_apply');
        $addressObj = app::get('ome')->model('return_address');

        $result = array('rsp' => 'fail', 'msg' => '', 'data' => '');
        if (empty($refund)) {
            $result['msg'] = 'no refund';
            
            return $result;
        }
        
        //退款申请单信息
        $filter = array();
        if($refund['apply_id']){
            $filter['apply_id'] = $refund['apply_id'];
        }else{
            $filter['refund_apply_bn'] = $refund['refund_bn'];
        }
        $refundInfo = $refundApplyMdl->dump($filter, '*');
        $shop_id = $refundInfo['shop_id'];
        $apply_id = $refundInfo['apply_id'];
        
        //退款来源
        $refund['refund_refer'] = $refundInfo['refund_refer'];
        
        //售后申请单ID
        $refund['return_id'] = $refundInfo['return_id'];
        
        //[兼容]oid没有值
        if(empty($refund['oid'])){
            $returnLubanObj = app::get('ome')->model('return_product_luban');
            $return_luban = $returnLubanObj->dump(array('shop_id'=>$shop_id, 'return_id'=>$refundInfo['return_id']), '*');
            
            $refund['oid'] = $return_luban['oid'];
        }
        
        //退款申请时间
        $op_time = ($refund['op_time'] ? $refund['op_time'] : $refundInfo['last_modified']);
        $op_time = ($op_time ? $op_time : time());
        
        //params
        $params = array(
            'oid'          => $refund['oid'], //退款申请单号
            'tid'          => $refund['order_bn'], //订单号
            'aftersale_id' => $refund['refund_bn'], //退款申请单号
            'op_time'      => $op_time,
            'company_code'      => $refund['company_code'],
            'logistics_no'      => $refund['logistics_no'],
            'version' => '2.0', //版本号
        );
        
        $title = sprintf('%s退款订单号[%s]退款单号[%s]',$refund['cancel_dly_status'] == 'SUCCESS'?'同意':'拒绝', $refund['order_bn'], $refund['refund_bn']);
        
        //售后退货退款
        if($refund['refund_refer'] == '1' && $refund['return_id']) {
            //售后申请单信息
            $returnObj = app::get('ome')->model('return_product');
            $returnInfo = $returnObj->dump(array('return_id'=>$refund['return_id']), 'return_id,return_bn,address_id');
            
            //售后申请单号
            $params['aftersale_id'] = $returnInfo['return_bn'];
            
            //平台退货地址ID
            $addressInfo = array();
            if($returnInfo['address_id']){
                $addressInfo = $addressObj->dump(array('contact_id'=>$returnInfo['address_id']), 'address_id,contact_id');
            }
            
            if(empty($addressInfo)){
                $addressInfo = $addressObj->dump(array('shop_id'=>$shop_id, 'cancel_def'=>'true'), 'address_id,contact_id');
            }
            $params['receiver_address_id'] = $addressInfo['contact_id'];
            
            //获取京东云交易返回的退货回寄地址
            $reshipInfo = $reshipObj->dump(array('return_id'=>$refund['return_id']), 'reship_id');
            if($reshipInfo){
                $jdAddressInfo = array();
                $tempInfo = $addressObj->dump(array('reship_id'=>$reshipInfo['reship_id']), '*');
                if($tempInfo){
                    $jdAddressInfo = array(
                            'province_name' => $tempInfo['province'], //省
                            'city_name' => $tempInfo['city'], //市
                            'town_name' => $tempInfo['country'], //区
                            //'street_name' => $tempInfo['street'], //街道名称
                            'detail' => $tempInfo['addr'], //地址详情
                            'user_name' => $tempInfo['contact_name'], //收件人
                            'mobile' => ($tempInfo['mobile_phone'] ? $tempInfo['mobile_phone'] : $tempInfo['phone']), //联系电话
                            //'province_id' => $tempInfo['aaaa'], //省id
                            //'city_id' => $tempInfo['aaaa'], //市id
                            //'town_id' => $tempInfo['aaaa'], //区id
                            //'street_id' => $tempInfo['aaaa'], //街道id
                    );
                    $params['after_sale_address_detail'] = json_encode($jdAddressInfo);
                }
                unset($tempInfo, $jdAddressInfo);
            }
            
            //[二次审核标识]退款审单
            $params['parse'] = 'second';
            
            //退货退款
            $refund['cancel_dly_status'] = 'SUCCESS';
            $refund['trigger_event'] = '售后退货退款';
            
            //title
            $title = sprintf('%s退款订单号[%s]退款单号[%s]',$refund['cancel_dly_status'] == 'SUCCESS'?'同意':'拒绝', $refund['order_bn'], $refund['refund_bn']);
            
            //退货退款二次审核确认接口
            $apiname = STORE_AG_LOGISTICS_WAREHOUSE_UPDATE;
            
        // 售后仅退款
        }elseif ($refund['refund_refer'] == '1') {
            if ($refund['cancel_dly_status'] == 'SUCCESS') {
                $apiname = SHOP_AGREE_AFTERSALE_REFUND;
            } else {
                $apiname = SHOP_REFUSE_AFTERSALE_REFUND;
                $file_url = "https://top.shopex.cn/ecos/statics/images/logo_taog.png"; //假图片地址
                
                $params['reason'] = '订单已发货,不允许售后仅退款'; //拒绝原因
                $params['url'] = ($refund['refuse_proof'] ? $refund['refuse_proof'] : $file_url); //拒绝凭证图片url
                $params['desc'] = '拒绝售后仅退款'; //凭证描述
                
                //临时兼容
                $params['reason'] = '商品已发出，如买家不再需要请拒收后申请仅退款或收到后申请退货退款';
                $params['reject_reason_code'] = '1';
                
                //flag
                if(empty($refund['trigger_event'])){
                    $refund['trigger_event'] = '订单已发货,不允许售后仅退款';
                }
            }
        
        //售前退款
        } else {
            $platform_refund = $this->searchRefund(['refund_apply_bn' => $refund['refund_bn'], 'order_bn' => $refund['order_bn']]);
            if ($platform_refund['rsp'] == 'succ' && $platform_refund['data']) {
                $oids = explode(',', $refund['oid']);
                foreach ($platform_refund['data']['aftersale_items'] as $value) {
                    if (!in_array($value['order_id'], $oids)) {
                        // 可能是整单退款

                        $params['oid'] = $params['tid'];

                        break;
                    }
                }
            }

            if ($refund['cancel_dly_status'] == 'SUCCESS') {
                $aliag_status = app::get('ome')->getConf('shop.refund.aliag.config.'.$refund['shop_id']);
                if ($aliag_status) {
                    //小助手退款
                    $apiname = STORE_AG_SENDGOODS_CANCEL;
                }else{
                    $apiname = SHOP_AGREE_REFUND;
                }
            } else {
                $apiname = SHOP_REFUSE_REFUND;

                if (!$refund['company_code'] || !$refund['logistics_no']) {
                    // 标记为异常
                    $refundApplyMdl->set_abnormal_status($refund['apply_id'],ome_constants_refundapply_abnormal::__NOPARTREFUND_CODE);

                    //$operLogObj->write_log('refund_apply@ome', $apply_id, sprintf('[%s]触发拒绝退款,失败：缺少运单信息', $refund['trigger_event']));

                    return $this->error('缺少运单信息', '');
                }

                $params['company_code'] = $refund['company_code'];
                $params['logistics_no'] = $refund['logistics_no'];
                $params['reason'] = $refund['memo']; //拒绝原因
                
                //临时兼容
                $params['reason'] = '商品已发出，如买家不再需要请拒收后申请仅退款或收到后申请退货退款';
                $params['reject_reason_code'] = '1';
            }
        }
        
        $result = $this->__caller->call($apiname, $params, [], $title, 10, $params['tid']);
        
        //请求状态
        if($result['rsp'] == 'succ'){
            $result = $this->_formatResultStatus($result);
        }
        
       // $operLogObj->write_log('refund_apply@ome', $apply_id, sprintf('[%s]触发%s退款,%s',$refund['trigger_event'],$refund['cancel_dly_status'] == 'SUCCESS'?'同意':'拒绝',$result['rsp'] == 'succ'?'成功':'失败：'.$result['err_msg']));

        return $result;
    }

    /**
     * 搜索Refund
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function searchRefund($sdf)
    {
        $result = parent::searchRefund($sdf);

        if (is_array($result['data']) 
            && is_array($result['data']['results'])
            && is_array($result['data']['results']['data'])
            && is_array($result['data']['results']['data']['aftersale_list'])
        ) {
            $result['data'] = $result['data']['results']['data']['aftersale_list'][0];
        }

        return $result;
    }
    
    /**
     * 格式化抖音平台请求退款返回的状态
     * 
     * @param array $response
     * @return array
     */
    public function _formatResultStatus($response)
    {
        //data
        $rspData = ($response['data'] ? $response['data'] : '');
        if(is_string($rspData)){
            $rspData = json_decode($response['data'], true);
        }
        
        //[兼容]聚合接口按list列表返回结果
        $items = $rspData['results']['data']['items'];
        if(empty($items)){
            return $response;
        }
        
        foreach ($items as $key => $val)
        {
            if($val['status_code'] != '0' && $val['status_msg'] != '成功'){
                $response['rsp'] = 'fail';
                $response['res'] = $val['status_code'];
                $response['err_msg'] .= $val['status_msg'];
                $response['msg'] .= $val['status_msg'];
            }
        }
        
        return $response;
    }
}
