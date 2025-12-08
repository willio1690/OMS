<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 同城配--商家配送物流轨迹
 *
 * @author wangbiao@shopex.cn
 * @version 2022.12.25
 */
class ome_logistics_seller
{
    /**
     * detail_delivery
     * @param mixed $logi_no logi_no
     * @param mixed $order_bn order_bn
     * @param mixed $type type
     * @return mixed 返回值
     */

    public function detail_delivery($logi_no=false, $order_bn=false, $type='')
    {
        $delivery_html = null;
        
        //发货单信息
        $data = $this->getDeliveryInfo($logi_no);
        $data['order_bn'] = $order_bn;
        
        //物流信息
        $result = $this->get_dly_info($data);
        if($result['rsp'] == 'succ'){
            $html = "<ul style='margin-top:10px;'>";
            
            foreach((array)$result['data'] as $traceKey => $traceVal)
            {
                $dataLine = ceil($traceVal['status_time'] / 1000);
                
                $html .= "<li style='line-height:15px;border-bottom:1px dotted  #ddd;'>【". $traceVal['action'] ."】". $traceVal['status_desc'] .",日期：". date('Y-m-d H:i:s', $dataLine) ."<li/>";
            }
            
            $html .='</ul>';
        }else{
            $html = "<ul>";
            
            if($result['err_msg'] == "'HTTP Error 500: Internal Server Error'"){
                $html .= "<li style='line-height:15px;margin-top:10px;border-bottom:1px dotted  #ddd;'><font color='red'>此订单可能缺少物流公司或运单号</font><li/>";
            }else{
                $html .= "<li style='line-height:15px;margin-top:10px;border-bottom:1px dotted  #ddd;'><font color='red'>". $result['err_msg'] ."</font><li/>";
            }
            
            $html .='</ul>';
        }
        
        $html .= "<div  style='font-weight:700;font-color:#000000;margin-bottom:10px;'><font>以上信息由物流公司提供，如无跟踪信息或有疑问，请咨询对应物流公司</font><div>";
        
        return array('html'=>$html,'rsp'=>$result['rsp']);
    }
    
    /**
     * 获取DeliveryInfo
     * @param mixed $logi_no logi_no
     * @return mixed 返回结果
     */
    public function getDeliveryInfo($logi_no=false)
    {
        //主单
        $delivery_sql = "select d.delivery_bn,d.shop_id,d.logi_id,d.logi_no,d.logi_name, c.type from sdb_ome_delivery d left join sdb_ome_dly_corp c on d.logi_id=c.corp_id 
                         where d.logi_no='". $logi_no ."'";
        $rs = kernel::database()->selectrow($delivery_sql);
        
        //[子单]没有主单，再查子单
        if(empty($rs)){
            $bill_sql = "select d.delivery_bn,d.shop_id,d.logi_id,d.logi_name, b.logi_no, c.type from sdb_ome_delivery d 
                         left join sdb_ome_delivery_bill b on d.delivery_id=b.delivery_id left join sdb_ome_dly_corp c on d.logi_id=c.corp_id 
                         where b.logi_no='". $logi_no ."'";
            $rs = kernel::database()->selectrow($bill_sql);
        }
        
        return $rs;
    }

    //查看物流状态
    /**
     * 获取_dly_info
     * @param mixed $data 数据
     * @return mixed 返回结果
     */
    public function get_dly_info($data)
    {
        $shop_id = $data['shop_id'];
        
        //params
        $params = array(
            'order_bn' => $data['order_bn'],
            'delivery_bn' => $data['delivery_bn'],
            'logi_code' => $data['type'],
            'logi_name' => $data['logi_name'],
            'logi_no' => $data['logi_no'],
        );
        
        //请求接口
        $result = kernel::single('erpapi_router_request')->set('shop', $shop_id)->delivery_logistics_track($params);
        if($result['rsp'] != 'succ'){
            $result['err_msg'] = $result['error_msg'];
            
            return $result;
        }
        
        return $result;
    }
}