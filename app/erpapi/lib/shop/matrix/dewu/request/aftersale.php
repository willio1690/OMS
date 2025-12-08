<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_shop_matrix_dewu_request_aftersale extends erpapi_shop_request_aftersale {
    protected function __afterSaleApi($status, $returnInfo=null)
    {
        $api_method = '';
        //opinion
        switch( $status ){
            case '3'://同意
                $api_method = SHOP_RESHIP_AUDIT;
                break;
            case '5'://拒绝
                $api_method = SHOP_RESHIP_AUDIT;
                break;
            default :
                $api_method = '';
                break;
        }
        return $api_method;
    }

    protected function __formatAfterSaleParams($aftersale,$status) {
        $params = array(
            'refund_order_no'=>$aftersale['return_bn'],
            'biz_type'=>'1'
        );
        switch ($status) {
            case '3':
                $params['result'] = '1';
                break;
            case '5':
                $params['result'] = '2';
                $params['audit_reject_code'] = '1';
                $params['result_desc'] = ($aftersale['memo'] ? $aftersale['memo'] : $aftersale['content']); //拒绝原因
                $file_url = '';
                if($aftersale['attachment']){
                    if(is_numeric($aftersale['attachment'])){
                        $fileLib = kernel::single('base_storager');
                        $file_url = $fileLib->getUrl($aftersale['attachment']);
                    }else{
                        $tempData = explode('|', $aftersale['attachment']);
                        $file_url = $tempData[0];
                    }
                }
                $params['pics'] = json_encode([$file_url]);

                break;
            default: break;
        }

        return $params;
    }

    /**
     * returnGoodsSign
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function returnGoodsSign($sdf){
        $title = '售后签收货品['.$sdf['return_bn'].']';
        $data = array(
            'refund_order_no' => $sdf['return_bn'],
            'biz_type'=>'1',
        );
        $this->__caller->call(SHOP_RETURN_GOOD_SIGN, $data, array(), $title, 10, $sdf['return_bn']);
    }

    /**
     * 卖家确认收货
     * @param $data
     */
    public function returnGoodsConfirm($sdf){
        $title = '售后确认收货['.$sdf['return_bn'].']';
        $data = array(
            'refund_order_no' => $sdf['return_bn'],
            'biz_type'=>'1',
            'result'=>'1'
        );
        $this->__caller->call(SHOP_RETURN_GOOD_CHECK, $data, array(), $title, 10, $sdf['return_bn']);
    }

}