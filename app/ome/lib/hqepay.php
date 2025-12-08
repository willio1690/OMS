<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

#华强宝物流跟踪
class ome_hqepay{
    function detail_delivery($logi_no = false,$order_bn = false,$type = ''){
        #获取物流数据
        $data = $type=='return'?$this->getReturnInfo($logi_no):$this->getDeliveryInfo($logi_no);
        if(empty($data['logi_type'])) {
            $shipping = $this->getShipping($order_bn);
            if($shipping['logi_type']) {
                $data['logi_type'] = $shipping['logi_type'];
                $data['logi_name'] = $shipping['logi_name'];
            }
        }
        $rpc_data['order_bn'] = $order_bn;
        $rpc_data['logi_code'] = $data['logi_type'];#物流编码
        $rpc_data['company_name'] = $data['logi_name'];
        $rpc_data['logi_no'] = $data['logi_no'];
        //if (strtoupper($data['logi_type']) == 'SF') {
            $data['ship_mobile'] = kernel::single('ome_view_helper2')->modifier_ciphertext($data['ship_mobile'], 'order', 'ship_name');
            $rpc_data['customer_name'] = substr($data['ship_mobile'],-4);
        //}
        $rpc_result = $this->get_dly_info($rpc_data);
        if($rpc_result['rsp'] == 'succ'){
            $count = count( $rpc_result['data']);
            $max = $count - 1;#最新那条物流记录
            $html = "<ul style='margin-top:10px;'>";
            foreach($rpc_result['data'] as $key=>$val){
                #这时间是最新的
                if($max == $key ){
                    $html .= "<li style='line-height:15px;border-bottom:1px dotted  #ddd;'><font  style='font-size:13px;COLOR: red'>".$val['AcceptTime']."".$val['AcceptStation']."</font><li/>";
                }else{
                    $html .= "<li style='line-height:15px;border-bottom:1px dotted  #ddd;'>"."<em style='font-size:13px;COLOR: black'>".$val['AcceptTime']."</em>&nbsp;&nbsp;".$val['AcceptStation']." <li/>";
                }
            }
            $html .='</ul>';
        }else{
            $html = "<ul>";
            if($rpc_result['err_msg'] == "'HTTP Error 500: Internal Server Error'"){
                $html .= "<li style='line-height:15px;margin-top:10px;border-bottom:1px dotted  #ddd;'><font color='red'>此订单可能缺少物流公司或运单号</font><li/>";
            }else{
                $html .= "<li style='line-height:15px;margin-top:10px;border-bottom:1px dotted  #ddd;'><font color='red'>".$rpc_result['err_msg']."</font><li/>";
            }
        }
        $html .='</ul>';
        $html .= "<div  style='font-weight:700;font-color:#000000;margin-bottom:10px;'>快递鸟提供数据支持(<font>以上信息由物流公司提供，如无跟踪信息或有疑问，请咨询对应物流公司</font>)<div>";
        
        return array('html'=>$html,'rsp'=>$rpc_result['rsp']);
    }
    function getDeliveryInfo($logi_no = false){
        $deliveryMdl = app::get('ome')->model('delivery');
        $delivery = $deliveryMdl->db_dump(array ('logi_no' => $logi_no),'logi_no,logi_id,logi_name,ship_mobile');
        if (!$delivery) {
            $billMdl = app::get('ome')->model('delivery_bill');
            $bill = $billMdl->db_dump(array ('logi_no'=>$logi_no),'delivery_id');
        
            if ($bill['delivery_id']) {
                $delivery = $deliveryMdl->db_dump($bill['delivery_id'],'logi_no,logi_id,logi_name,ship_mobile');
            }
        }
    
        if ($delivery['logi_id']) {
            $corp = app::get('ome')->model('dly_corp')->db_dump($delivery['logi_id'], 'type');
        
            $delivery['logi_type'] = $corp['type'];
        }
    
        return $delivery;
    }

    #ERP与华强宝快递对接，查看物流状态
    function get_dly_info($rpc_data = false){
        $return = array();
        $rs = kernel::single('erpapi_router_request')->set('hqepay','1227722633')->hqepay_query($rpc_data);
        if ($rs['rsp'] == 'succ') {
            $data = @json_decode($rs['data'],true);

            $traces = $data['Traces']; krsort($traces);

            $return['data'] = $traces;
        }

        $return['rsp']     = $rs['rsp'];
        $return['err_msg'] = $rs['msg'];

        return $return;
    }

    function getReturnInfo($logi_no = false){
        $reship = app::get('ome')->model('reship')->db_dump(array ('return_logi_no' => $logi_no), 'return_logi_no,return_logi_name,ship_mobile');
    
        $corp = array ();
        if ($reship['return_logi_name']) {
            $corpMdl = app::get('ome')->model('dly_corp');
        
            $corp = $corpMdl->db_dump(['name' => $reship['return_logi_name']], 'type,corp_id,name');
        
            if (!$corp) {
                $corp = $corpMdl->db_dump(['type' => $reship['return_logi_name']], 'type,corp_id,name');
            }
        }
    
        $data = array (
            'logi_no'     => $logi_no,
            'logi_id'     => $corp['corp_id'],
            'logi_name'   => $corp['name'],
            'logi_type'   => $corp['type'],
            'ship_mobile' => $reship['ship_mobile'],
        );
    
        if (!$data['logi_type'] && preg_match('/^[A-Z]+/', $logi_no, $match)){
            $data['logi_type'] = $match[0];
        }
    
        return $data;
    }

    /**
     * 获取Shipping
     * @param mixed $order_bn order_bn
     * @return mixed 返回结果
     */
    public function getShipping($order_bn) {
        $order = app::get('ome')->model('orders')->db_dump(['order_bn'=>$order_bn], 'shipping,shop_type');
        return kernel::single('ome_hqepay_shipping')->getLogiNameType($order['shipping'], $order['shop_type']);
    }
}

