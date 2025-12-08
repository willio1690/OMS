<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_unionpay{
    const _TO_NODE_ID = '1705101437';
    function detail_delivery($logi_no = false,$order_bn = false){
        #获取物流数据
        $data = $this->getDeliveryInfo($logi_no);
        $delivery_html = null;
        $rpc_data['order_bn'] = $order_bn;
        $rpc_data['logi_code'] = $data['type'];#物流编码
        $rpc_data['company_name'] = $data['name'];
        $rpc_data['logi_no'] = $data['logi_no'];
    
        $rpc_result = $this->get_dly_info($rpc_data);

        if($rpc_result['rsp'] == 'succ'){
            $count = count( $rpc_result['data']);
            $max = $count - 1;#最新那条物流记录
            $html = "<ul style='margin-top:10px;'>";
            foreach($rpc_result['data'] as $key=>$val){
                #这时间是最新的
                if($max == $key ){
                    $html .= "<li style='line-height:15px;border-bottom:1px dotted  #ddd;'><font  style='font-size:13px;COLOR: red'>".$val['CreateTime']."".$val['WlNote']."</font><li/>";
                }else{
                    $html .= "<li style='line-height:15px;border-bottom:1px dotted  #ddd;'>"."<em style='font-size:13px;COLOR: black'>".$val['CreateTime']."</em>&nbsp;&nbsp;".$val['WlNote']." <li/>";
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
        $html .= "<div  style='font-weight:700;font-color:#000000;margin-bottom:10px;'>(<font>以上信息由物流公司提供，如无跟踪信息或有疑问，请咨询对应物流公司</font>)<div>";
        return $html;
    }
    function getDeliveryInfo($logi_no = false){
        $logi_no = "'".$logi_no."'";
        #主单
        $delivery_sql = "select
                            d.logi_no,d.logi_name name,c.type from sdb_ome_delivery  d
                          left join sdb_ome_dly_corp  c on d.logi_id= c.corp_id
                          where d.logi_no=".$logi_no;
        #子单
        $bill_sql = "select
                d.logi_name name ,b.logi_no,c.type
                from sdb_ome_delivery d
                left join sdb_ome_delivery_bill b on d.delivery_id=b.delivery_id
                left join sdb_ome_dly_corp  c on d.logi_id= c.corp_id
                where b.logi_no=".$logi_no;
        #先找主单
        $rs = kernel::database()->selectrow($delivery_sql);
        #主单没有，再查子单
        if(empty($rs)){
            $rs = kernel::database()->selectrow($bill_sql);
        }
        return $rs;
    }    

    function get_dly_info($rpc_data = false){
        #检测是否已经绑定华强宝物流
        base_kvstore::instance('ome/bind/')->fetch('ome_bind_unionpay', $is_ome_bind_unionpay);
        if(!$is_ome_bind_unionpay){
            $rs = kernel::single('erpapi_router_request')->set('unionpay',self::_TO_NODE_ID)->unionpay_bind();

            if(!$rs){
                $return_data['rsp'] = 'fail';
                $return_data['err_msg'] = '没有绑定!';
                return  $return_data;
            }
        }
        $params = array(
            'tid'           => $rpc_data['order_bn'],
            'company_code'  => $rpc_data['logi_code'],
            'company_name'  => $rpc_data['company_name'],
            'logistic_code' => $rpc_data['logi_no'],
        );

        $data = kernel::single('erpapi_router_request')->set('unionpay',self::_TO_NODE_ID)->unionpay_query($params);

        if($data['rsp'] == 'succ'){
            #倒叙排序
            krsort($data['data']);
        }
        return $data;
    }    
    

}

