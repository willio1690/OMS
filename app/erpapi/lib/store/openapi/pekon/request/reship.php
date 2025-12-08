<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * pos发货单对接shopex pos
 *
 * https://docs.pekon.com/docCenter/home?docId=52234293
 *
 * @author sunjing@shopex.cn
 * @version 0.1
 *
 */
class erpapi_store_openapi_pekon_request_reship extends erpapi_store_request_reship
{



    /**
     * reship_create
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */

    public function reship_create($sdf)
    {
        $reship_bn = $sdf['reship_bn'];

        // 判断是否已被删除
        $iscancel = kernel::single('console_service_commonstock')->iscancel($reship_bn);
        if ($iscancel) {
            $this->succ('退货单已取消,终止同步');
        }

        $title = $this->__channelObj->store['name'] . '退货单添加';

        $method = $this->get_reship_create_apiname($sdf);

        $params = $this->_format_reship_create_params($sdf);

        $callback = array();
        
        $rs = $this->call($method, $params, $callback, $title, 10, $reship_bn, true, $gateway);

        if($rs['rsp'] == 'succ'){
            $rs['data']['wms_order_code'] = $rs['data']['orderNo'];
        }
        return $rs;
    }

    
   
    protected function _format_reship_create_params($sdf)
    {
        
        //if($sdf['return_type'] == 'change' && $sdf['order_source'] == 'ipromotion'){//云店换货
           // return $this->promation_create_params($sdf);

       // }else{
            return $this->reship_create_params($sdf);
        //}

        
    }


    /**
     * promation_create_params
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function promation_create_params($sdf){

        $create_time = preg_match('/-|\//', $sdf['create_time']) ? $sdf['create_time'] : date("Y-m-d H:i:s", $sdf['create_time']);
        $warehouseCode = POS_DEFAULT_BRANCH;    
        $items = [];
        $totalQuantity = 0;
        $totalAmount = 0;
        if ($sdf['items']) {
            sort($sdf['items']);
            foreach ((array) $sdf['items'] as $k => $v) {
                $amount = $v['num']*$v['price'];
                $totalQuantity+=(int) $v['num'];
                $totalAmount+=$amount;
                $items[] = array(

                    'number'            =>  ($k + 1),
                    'thirdPartyItemNo'  =>  $sdf['reship_bn'],
                    'skuCode'           =>  $v['bn'],
                    'warehouseCode'     =>  $warehouseCode,
                    'quantity'          =>  (int) $v['num'],
                    'price'             =>  $v['price'] ? (float) $v['price'] : 0,
                    'amount'            =>  $amount,
                );
            }
            
        }
        
        $params = array(
           'orgCode'        =>  $sdf['branch_bn'],
           'warehouseCode'  =>  $warehouseCode,
           'docDate'        =>  $create_time,
           'thirdPartyDocNo'=>  $sdf['reship_bn'],
           'status'         =>  '1',
           'employeeCode'   =>  '18770038856',
           'totalQuantity'  =>  $totalQuantity,
           'totalAmount'    =>  $totalAmount,
           'memo'           =>  '',
           'items'          =>  $items,
        );
        return $params;
    }


    /**
     * reship_create_params
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function reship_create_params($sdf){

        $create_time = preg_match('/-|\//', $sdf['create_time']) ? $sdf['create_time'] : date("Y-m-d H:i:s", $sdf['create_time']);
        $itemsMdl = app::get('ome')->model('reship_items');
        $orderItems = [];
        $totalQuantity = 0;
        $totalAmount = 0;
        if ($sdf['items']) {
            sort($sdf['items']);
            foreach ((array) $sdf['items'] as $k => $v) {

                $order_item_id = $v['order_item_id'];
                $items = $itemsMdl->db->selectrow("SELECT s.oid FROM sdb_ome_order_objects as s left join sdb_ome_order_items as i on s.obj_id=i.obj_id WHERE i.item_id=".$order_item_id."");

                $oid = $items['oid'];
                $oid = $oid ? list($itemoid,$seqno)=explode('_',$oid) : '';
                $amount = $v['num']*$v['price'];
                $totalQuantity+=(int) $v['num'];
                $totalAmount+=$amount;
                $items = array(

                    'itemSeqNo'             =>  ($k + 1),
                    'refSalesOrderItemSeqNo'=>  $seqno ?  $seqno : 1,
                    'productSkuCode'        =>  $v['bn'],
                    'price'                 =>  $v['price'] ? (float) $v['price'] : 0,
                    'quantity'              =>  (int) $v['num'],
                    'amount'                =>  $amount,
                );

                $uniqueCodes = [];
                if($v['uniqueCodes']){
                    foreach($v['uniqueCodes'] as $sv){
                        $uniqueCodes[]['uniqueCode'] = $sv;
                    }
                    
                }
                if($uniqueCodes) $items['uniqueCodes'] = $uniqueCodes;
                $orderItems[] = $items;
            }
            
        }
        $b_type = $sdf['b_type'];
        $deliveryOrgType = $b_type == '1' ? 'DC' : 'Store';
        $orderType = $sdf['return_type'] == 'change' ? 'NMR' : 'YDR';
        $params = array(
            'orderType'         =>  $orderType,
            'returnType'        =>  'PART',
            'actualOrderSource' => 'OMS',
            'thirdpartyOrderNo' =>  $sdf['reship_bn'],
            'referenceOrderNo'  =>  $sdf['order_bn'],
            'businessTime'      =>  $create_time,
            'salesOrgCode'      =>  $sdf['shop_bn'],
            //'salesEmpCode'      =>  '',
            'totalQuantity'     =>  $totalQuantity,
            'freight'           =>  $sdf['freight'] ? $sdf['freight'] :'0.00',
            'amount'            =>  $totalAmount,
            'currencyCode'      =>  'CNY',
            'refundReasonText'  =>  $sdf['return_type'] == 'change' ? 'exchange' : 'normal',
            'memo'              =>  '',
            'memberName'        =>  '',
            'memberCode'        =>  '',
            'memberPhone'       =>  '',
            'auditStatus'       =>  'AUDIT_SUCCESS',
            'refundType'        => 'ReturnAndRefund',
            'orderItems'        =>  $orderItems,
            
            'deliveryOrgType'   =>  $deliveryOrgType,
        );
        $payments = [];

        if($deliveryOrgType == 'Store' && $sdf['return_type'] == 'return'){//退门店需要给付款单信息
            //判断是否是全额退款
            foreach($sdf['payments'] as $v){
                $payments[]=[
                    'payType'       =>  $v['pay_bn'],
                    'amount'        =>  $v['totalmoney'],
                    'payTime'       =>  date('Y-m-d H:i:s',$v['paytime']),
                    //'needOnlinePay' =>  'Y',

                ];
            }
            
            //$params['payments'] = $payments;
        }
        if($deliveryOrgType == 'DC' && $sdf['return_type'] == 'change'){//退门店需要给付款单信息
            foreach($sdf['payments'] as $v){
                $payments[]=[
                    'payType'       =>  $v['pay_bn'],
                    'amount'        =>  $v['tmoney'],
                    'payTime'       =>  date('Y-m-d H:i:s',$v['paytime']),
                    'needOnlinePay' =>  'N',

                ];
            }
            
            $params['payments'] = $payments;
        }
        return $params;

    }


    /**
     * reship_check
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function reship_check($sdf){
        $title = $this->__channelObj->store['channel_name'] . '退货单审核';

        $params = array(
            'orderNo'       => $sdf['reship_bn'],
            'auditAction'   =>  'APPROVE',
        );

        $callback = array();

      

        return $this->call('refundOrderAudit', $params, $callback, $title, 10, $reship_bn, true, $gateway);
    }


    protected function get_reship_create_apiname($sdf)
    {
        //if($sdf['return_type'] == 'change' && $sdf['order_source'] == 'ipromotion'){//云店换货
            //return 'CreateOtherInDocument';

        //}else{
            return 'CreateRefundOrder';
        //}
        
    }

    /**
     * reship_cancel
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function reship_cancel($sdf)
    {
        $reship_bn = $sdf['reship_bn'];

       

        $title = $this->__channelObj->store['name'] . '退货单取消';

        $method = $this->get_reship_cancel_apiname($sdf);

        $params = $this->_format_reship_cancel_params($sdf);

        $callback = array();
        
        $rs = $this->call($method, $params, $callback, $title, 10, $reship_bn, true, $gateway);

       
        return $rs;
    }

    /**
     * format reship cancel
     *
     * @return void
     * @author
     **/
    protected function _format_reship_cancel_params($sdf)
    {
        $params = array(
            'thirdpartyOrderNo' => $sdf['reship_bn'],
           
        );

        return $params;
    }   

    protected function get_reship_cancel_apiname($sdf)
    {
        return 'cancelRefundOrder';
        
    }
}
