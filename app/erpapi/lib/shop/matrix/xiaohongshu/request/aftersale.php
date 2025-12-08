<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 抖音店铺退货业务请求Lib类
 */
class erpapi_shop_matrix_xiaohongshu_request_aftersale extends erpapi_shop_request_aftersale
{
    protected function __afterSaleApi($status, $returnInfo=null)
    {
        switch($status)
        {
            case '3':
                $api_method = SHOP_AGREE_REFUNDGOODS;
                break;
            case '5':
                $api_method = SHOP_REFUSE_REFUNDGOODS;
                break;
            default :
                $api_method = '';
                break;
        }
        
        return $api_method;
    }
    
    protected function __formatAfterSaleParams($aftersale, $status)
    {
        $params = array(
            'returns_id' => $aftersale['return_bn'], //退货单号
        );
        
        switch ($status)
        {
            case '3':
                //同意退货
                $params['audit_result'] = '200'; //同意
                
                //获取退货单信息
                $reshipObj = app::get('ome')->model('reship');
                $reshipInfo = $reshipObj->dump(array('return_id'=>$aftersale['return_id']), 'reship_id');
                $reshipInfo['reship_id'] = intval($reshipInfo['reship_id']);
                
                //获取退回寄件地址
                $filter = array('reship_id'=>$reshipInfo['reship_id']);
                $return_address = app::get('ome')->model('return_address')->dump($filter, '*');
                
                //params
                $receiver_info = array(
                        //'code' => '', //非必填，退回仓库编码，只有使用小红书退货服务才需要填
                        'country' => '中国', //非必填，国家
                        'province' => $return_address['province'], //非必填， 省份
                        'city' => $return_address['city'], //非必填，城市
                        'district' => $return_address['country'], //非必填  区
                        'street' => $return_address['addr'], //非必填，街道信息
                );
                $params['receiver_info'] = json_encode($receiver_info);
                
                break;
            case '5':
                //拒绝退货参数
                $params['audit_result'] = '500'; //拒绝
                
                //拒绝原因
                $refuse_message = ($aftersale['memo'] ? $aftersale['memo'] : $aftersale['content']);
                if($refuse_message){
                    $params['audit_description'] = $refuse_message;
                    $params['reject_reason'] = 1;
                }
                break;
            default: break;
        }
        
        return $params;
    }

    /**
     * 卖家确认收货
     * @param $data
     */

    public function returnGoodsConfirm($sdf)
    {
        $title = '售后确认收货['.$sdf['return_bn'].']';
        $specialObj = app::get('ome')->model('return_apply_special');
        $ras = $specialObj->db_dump(array('return_id'=>$sdf['return_id']), 'special');
        $special = $ras ? json_decode($ras['special'], 1) : array();
        $data = array(
            'refund_id' => $sdf['return_bn'],
            'order_status' => $special['order_status']
        );
        $this->__caller->call(SHOP_RETURN_GOOD_CONFIRM, $data, array(), $title, 10, $sdf['return_bn']);
    }
}