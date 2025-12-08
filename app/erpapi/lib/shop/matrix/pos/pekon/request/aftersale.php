<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_shop_matrix_pos_pekon_request_aftersale extends erpapi_shop_request_aftersale
{
    
    protected function __afterSaleApi($status, $returnInfo=null) {
        /*switch($status){
            case '3':
                $api_method = 'refundOrderAudit';
                break;
           
            default :
                $api_method = '';
                break;
        }
        return $api_method;*/
    }

    protected function __formatAfterSaleParams($aftersale,$status) {
        /*
        if ($status == 3) {
           
            $params = array(
                'orderNo'           => $aftersale['return_bn'],
                'thirdpartyOrderNo' => $aftersale['return_bn'],
                'auditAction'       => 'APPROVE',
                'method'            => 'refundOrderAudit',
            );
        
        }

        return $params;*/
    }

    /**
     * 更新AfterSaleStatus
     * @param mixed $aftersale aftersale
     * @param mixed $status status
     * @param mixed $mod mod
     * @return mixed 返回值
     */
    public function updateAfterSaleStatus($aftersale, $status='', $mod='async')
    {
        /*
        $rs = array();
        if(empty($aftersale)) {
            return array('rsp'=>'fail', 'msg'=>'no return');
        }
        
        //售后类型
        $return_type = $aftersale['return_type'];
        
        //订单信息
        $orderModel = app::get('ome')->model('orders');
        $order = $orderModel->dump($aftersale['order_id'], 'order_bn');
        
        //售后确认状态
        if (!$status) {
            $status = $aftersale['status'];
        }
        
        $api_method = $this->__afterSaleApi($status, $aftersale);
        if (empty($api_method)) {
            return true;
        }
        
        $return_title = ($this->_return_type[$return_type] ? $this->_return_type[$return_type] : '售后');
        $return_title .= '单号:'. $aftersale['return_bn'];
        
        $title = '店铺('.$this->__channelObj->channel['name'].')更新[交易售后状态]:'.$status.',(订单号:'.$order['order_bn']. $return_title .')';
        $params = $this->__formatAfterSaleParams($aftersale, $status);
       
        $callback = array();
        
        //request
        $result = $this->__caller->call($api_method, $params, $callback, $title, 10, $order['order_bn']);
        
        //msg
        if(isset($result['msg']) && $result['msg']){
            $rs['msg'] = $result['msg'];
        }elseif(isset($result['err_msg']) && $result['err_msg']){
            $rs['msg'] = $result['err_msg'];
        }elseif(isset($result['res']) && $result['res']){
            $rs['msg'] = $result['res'];
        }
        
        $rs['rsp'] = $result['rsp'];
        $rs['data'] = $result['data'] ? json_decode($result['data'], true) : array();
        
        return $rs;
        */
    }

    /**
     * warehouseConfirm
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function warehouseConfirm($sdf)
    {
        $title = '收到顾客退货['.$sdf['reship_bn'].']';
        $reship_items = $sdf['reship_items'];
        
        $order_items = [];
        sort($reship_items);
        foreach ($reship_items as $k => $value) {
            
           $items = array(
                'itemSeqNo' =>  $k+1,
                'skuCode'   =>  $value['bn'],
                'quantity'  =>  $value['normal_num']+$value['defective_num'],
           );
            $uniqueCodes = [];
            if($value['uniqueCodes']){
                foreach($value['uniqueCodes'] as $sv){
                    $uniqueCodes[]['uniqueCode'] = $sv;
                }

            }
            if($uniqueCodes) $items['uniqueCodes'] = $uniqueCodes;
            $order_items[] = $items;
        }
        $data = array(
           
            'refundOrderNo' => $sdf['reship_bn'],
           
            'orderItems' => $order_items
        );
        $rs = $this->__caller->call('refundOrderConfirm', $data, array(), $title, 10, $sdf['reship_bn']);

        return $rs;
    }
}