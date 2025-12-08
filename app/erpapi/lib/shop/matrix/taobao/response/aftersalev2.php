<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @desc
 * @author: jintao
 * @since: 2016/7/20
 */
class erpapi_shop_matrix_taobao_response_aftersalev2 extends erpapi_shop_response_aftersalev2 {
    protected $item_convert_field = [
        'sdf_field'     =>'oid',
        'order_field'   =>'oid',
        'default_field' =>'outer_id'
    ];

    protected function _formatAddParams($params) {
        $sdf = parent::_formatAddParams($params);
        if($params['tag_list']) {
            $tagList = json_decode($params['tag_list'], true);
            $tagList = serialize($tagList);
        }
        //识别如果是已完成的售后，转成退款单更新的逻辑
        if($sdf['has_good_return'] == 'true' && strtolower($params['status']) == 'success'){
            $refundOriginalObj = app::get('ome')->model('return_product');
            $refundOriginalInfo = $refundOriginalObj->getList('return_id', array('return_bn'=>$sdf['refund_bn'],'status' =>'4') , 0 , 1);
            if($refundOriginalInfo){
                $refundApplyObj = app::get('ome')->model('refund_apply');
                $refundApplyInfo = $refundApplyObj->getList('refund_apply_bn', array('return_id'=>$refundOriginalInfo[0]['return_id'],'status' =>array('0','1','2','5','6')) , 0 , 1);
                if($refundApplyInfo){
                    $sdf['refund_bn'] = $refundApplyInfo[0]['refund_apply_bn'];
                    $sdf['tmall_has_finished_return_product'] = true;
                }
            }
        }
        $taobaoSdf = array(
            'oid'               => $params['oid'],
            'cs_status'         => $params['cs_status'],
            'advance_status'    => $params['advance_status'],
            'split_taobao_fee'  => (float)$params['split_taobao_fee'],
            'split_seller_fee'  => (float)$params['split_seller_fee'],
            'total_fee'         => (float)$params['total_fee'],
            'seller_nick'       => $params['seller_nick'],
            'good_status'       => $params['good_status'],
            'refund_version'    => $params['refund_version'],
            'order_status'      => $params['order_status'],
            'current_phase_timeout'=>$params['current_phase_timeout']?strtotime($params['current_phase_timeout']):0,
            'ship_addr'         => $params['receiver_address'],
            'tag_list'          => $tagList ? $tagList : '',
            'attribute'         =>  $params['attribute'],
            'address'           => $params['address'] ? $params['address'] : '',
            't_ready'           =>$sdf['t_begin'],
            't_sent'           =>$sdf['modified'],
            't_received'       =>''
        );

        $attributeArr = explode(';', $taobaoSdf['attribute']);
        $attributeCode = [];
        foreach($attributeArr as $attribute) {
            if(strpos($attribute, ':') !== false) {
                list($key, $value) = explode(':', $attribute);
                $attributeCode[$key] = $value;
            }
        }

        // 关联退款单
        $taobaoSdf['associatedDisputeID'] = $attributeCode['associatedDisputeID'] ?? '';

        // 关联子单状态
        $taobaoSdf['disputeTradeStatus'] = $attributeCode['disputeTradeStatus'] ?? '';

        if ($sdf['reason'] == '补退已使用的红包' && $taobaoSdf['associatedDisputeID'] && $taobaoSdf['disputeTradeStatus']=='4') {
            $this->__apilog['result']['msg'] = '不接收补退红包，因为金额已经包含在';
            return [];
        }

        if($attributeCode['lastOrder']) {
            $taobaoSdf['refund_shipping_fee'] = $attributeCode['lastOrder'] / 100;
        }


        if(strstr($taobaoSdf['attribute'],'interceptItemListResult')) {
            preg_match_all('/interceptItemListResult:([^;]+);/', $taobaoSdf['attribute'], $matches);
            if($matches && $matches[1] && $matches[1][0]) {
                $intercept = json_decode(str_replace("#3B", ":", $matches[1][0]), 1);
                if($intercept[0]['autoInterceptAgree'] == 1) {
                    $taobaoSdf['has_good_return'] = 'true';
                    if($sdf['flag_type']) {
                        $taobaoSdf['flag_type'] = $sdf['flag_type'] | ome_reship_const::__ZERO_INTERCEPT;
                    } else {
                        $taobaoSdf['flag_type'] = ome_reship_const::__ZERO_INTERCEPT;
                    }
                }
            }
        }
        return array_merge($sdf, $taobaoSdf);
    }

    protected function _getAddType($sdf) {
        if ($sdf['has_good_return'] == 'true') {//需要退货才更新为售后单
            if (in_array($sdf['order']['ship_status'],array('0'))) {
                #有退货，未发货的,做退款
                return 'refund';
            }elseif(in_array($sdf['order']['ship_status'],array('3','4')) && $sdf['tmall_has_finished_return_product']){
                #退款单
                return 'refund';
            }else{
                #有退货，已发货的,做售后
                return 'returnProduct';
            }
        }else{
            #无退货的，直接退款
            return 'refund';
        }
    }

    protected function _formatAddItemList($sdf, $convert=array()) {
        $convert = $this->item_convert_field;

        return parent::_formatAddItemList($sdf, $convert);
    }

    protected function _refundApplyAdditional($sdf) {
        $ret = array(
            'model' => 'refund_apply_taobao',
            'data' => array(
                'shop_id'           => $sdf['shop_id'],
                'oid'               => $sdf['oid'],
                'cs_status'         => $sdf['cs_status'],
                'advance_status'    => $sdf['advance_status'],
                'split_taobao_fee'  => $sdf['split_taobao_fee'],
                'split_seller_fee'  => $sdf['split_seller_fee'],
                'total_fee'         => $sdf['total_fee'],
                'seller_nick'       => $sdf['seller_nick'],
                'good_status'       => $sdf['good_status'],
                'has_good_return'   => $sdf['has_good_return'],
                'alipay_no'         => $sdf['alipay_no'],
                'current_phase_timeout'=>$sdf['current_phase_timeout'],
                'refund_fee'         => $sdf['refund_fee'],
                'refund_version'     => $sdf['refund_version'],
                'order_status'     => $sdf['order_status'],
            )
        );
        return $ret;
    }

    protected function _returnProductAdditional($sdf) {
        $ret = array(
            'model' => 'return_product_taobao',
            'data' => array(
                'shop_id'         => $sdf['shop_id'],
                'shipping_type'   => $sdf['shipping_type'],
                'cs_status'       => $sdf['cs_status'],
                'advance_status'  => $sdf['advance_status'],
                'split_taobao_fee'=> $sdf['split_taobao_fee'],
                'split_seller_fee'=> $sdf['split_seller_fee'],
                'total_fee'       => $sdf['total_fee'],
                'buyer_nick'      => $sdf['buyer_nick'],
                'seller_nick'     => $sdf['seller_nick'],
                'good_status'     => $sdf['good_status'],
                'has_good_return' => $sdf['has_good_return'],
                'good_return_time'=> $sdf['good_return_time'],
                'alipay_no'       => $sdf['alipay_no'],
                'ship_addr'       => $sdf['receiver_address'],
                'outer_lastmodify'=> $sdf['modified'],
                'oid'             => $sdf['oid'],
                'current_phase_timeout'=>$sdf['current_phase_timeout'],
                'tag_list'        => $sdf['tag_list'],
                'attribute'       =>  $sdf['attribute'],
                'address'         => $sdf['address'],
                'refund_fee'      => $sdf['refund_fee'],
                'refund_version'  => $sdf['refund_version'],
            )
        );
        return $ret;
    }
    
    /**
     * 售后申请单数据转换
     *
     * @param $sdf
     * @return array|false
     */
    protected function _returnProductAddSdf($sdf)
    {
        //平台状态值
        $status = strtoupper($sdf['status']);
        
        //format
        $sdf = parent::_returnProductAddSdf($sdf);
        if(!$sdf) {
            return false;
        }
        
        //商家拒绝退款
        //@todo：SELLER_REFUSE_BUYER是商家拒绝退款,只有CLOSED时才是取消退货单;
        if($status == 'SELLER_REFUSE_BUYER'){
            $sdf['status'] = '10';
        }
        
        //检查版本变化
        if($status == 'SELLER_REFUSE_BUYER' && $sdf['modified'] && $sdf['return_product']['outer_lastmodify']) {
            //@todo：商家拒绝退款时,发现refund_version版本号并没有变化,modified修改时间有变化;
            if($sdf['modified'] > $sdf['return_product']['outer_lastmodify']) {
                $sdf['refund_version_change'] = true;
            }
        }
        
        return $sdf;
    }
    
    /**
     * 退货数据转换
     *
     * @param $sdf
     * @return false|void
     */
    protected function _reshipAddSdf($sdf, $params=null)
    {
        //平台状态值
        $status = strtoupper($sdf['status']);
        
        //format
        $sdf = parent::_reshipAddSdf($sdf, $params);
        if(empty($sdf)){
            return false;
        }
        
        //商家拒绝退款
        //@todo：SELLER_REFUSE_BUYER是商家拒绝退款,只有CLOSED时才是取消退货单;
        if($status == 'SELLER_REFUSE_BUYER'){
            $sdf['status'] = '10';
        }
        
        return $sdf;
    }
}