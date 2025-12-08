<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_api_function_v1_delivery extends openapi_api_function_abstract implements openapi_api_function_interface{

    /**
     * 获取List
     * @param mixed $params 参数
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回结果
     */
    public function getList($params,&$code,&$sub_msg){
        $filter = array();
        
        $filter['create_starttime'] = $params['create_starttime'];
        $filter['create_endtime'] = $params['create_endtime'];
        $filter['ship_starttime'] = $params['ship_starttime'];
        $filter['ship_endtime'] = $params['ship_endtime'];
        //$filter['ship_time'] = $params['ship_time'];
        $filter['branch_name'] = $params['branch_name'];
        $filter['shop_name'] = $params['shop_name'];
        $filter['receive_area'] = $params['receive_area'];
        $filter['corp_name'] = $params['corp_name'];
        //$filter['start_time'] = strtotime($params['start_time']);
        //$filter['end_time'] = strtotime($params['end_time']);
        $page_no = intval($params['page_no']) > 0 ? intval($params['page_no']) : 1;
        $limit = (intval($params['page_size']) > 100 || intval($params['page_size']) <= 0) ? 100 : intval($params['page_size']);

        if($page_no == 1){
            $offset = 0;
        }else{
            $offset = ($page_no-1)*$limit;
        }
        
        //获取发货单列表
        $original_delivery_data = kernel::single('openapi_data_original_delivery')->getList($filter,$offset,$limit);
        
        $delivery_arr = array();
        foreach ($original_delivery_data['lists'] as $k => $delivery){
                $delivery_arr[$k]['shop_code'] = $this->charFilter($delivery['shop_bn']);
                $delivery_arr[$k]['shop_name'] = $this->charFilter($delivery['shop_name']);
                $delivery_arr[$k]['order_no'] = $this->charFilter($delivery['order_bn']);
                $delivery_arr[$k]['member_name'] = $this->charFilter($delivery['member_name']);
                $delivery_arr[$k]['delivery_bn'] = $this->charFilter($delivery['delivery_bn']);
                //$delivery_arr[$k]['pay_method'] = $delivery['payment'];
                //$delivery_arr[$k]['sale_time'] = date('Y-m-d H:i:s',$delivery['sale_time']);
                $delivery_arr[$k]['create_time'] = $delivery['create_time'] ? date('Y-m-d H:i:s',$delivery['create_time']) : '';
                //$delivery_arr[$k]['pay_time'] = date('Y-m-d H:i:s',$delivery['paytime']);
                $delivery_arr[$k]['ship_time'] = $delivery['delivery_time'] ? date('Y-m-d H:i:s',$delivery['delivery_time']) : '';
                //$delivery_arr[$k]['order_check_op'] = $delivery['order_check_name'];
                //$delivery_arr[$k]['order_check_time'] = date('Y-m-d H:i:s',$delivery['create_time']);
                //$delivery_arr[$k]['goods_amount'] = $delivery['total_amount'];
                $delivery_arr[$k]['freight_amount'] = $this->charFilter($delivery['cost_freight']);
                //$delivery_arr[$k]['additional_amount'] = $delivery['additional_costs'];
                //$delivery_arr[$k]['has_tax'] = $delivery['is_tax'] == 'false' ? '否' : '是';
                //$delivery_arr[$k]['pmt_amount'] = $delivery['discount'];
                //$delivery_arr[$k]['sale_amount'] = $delivery['sale_amount'];
                $delivery_arr[$k]['logi_name'] = $this->charFilter($delivery['logi_name']);
                $delivery_arr[$k]['logi_no'] = $this->charFilter($delivery['logi_no']);
                $delivery_arr[$k]['branch_name'] = $this->charFilter($delivery['branch_name']);
                $delivery_arr[$k]['branch_bn'] = $this->charFilter($delivery['branch_bn']);
                $delivery_arr[$k]['delivery_no'] = $this->charFilter($delivery['delivery_bn']);
                $delivery_arr[$k]['consignee'] = $this->charFilter($delivery['ship_name']);
                $delivery_arr[$k]['consignee_area'] = $delivery['ship_area'];
                $delivery_arr[$k]['consignee_addr'] = $this->charFilter($delivery['ship_addr']);//str_replace('"','\"',$delivery['ship_addr']);
                $delivery_arr[$k]['consignee_zip'] = $this->charFilter($delivery['ship_zip']);
                $delivery_arr[$k]['consignee_tel'] = $this->charFilter($delivery['ship_tel']);
                $delivery_arr[$k]['consignee_mobile'] = $this->charFilter($delivery['ship_mobile']);
                $delivery_arr[$k]['consignee_email'] = $this->charFilter($delivery['ship_email']);
                $delivery_arr[$k]['mark_memo'] = $this->charFilter($delivery['mark_memo']);
                $delivery_arr[$k]['custom_memo'] = $this->charFilter($delivery['custom_memo']);
                
                //京东包裹列表(云交易订单号)
                $delivery_arr[$k]['packages'] = $delivery['packages'];
                
                //签收信息
                $delivery_arr[$k]['logi_status'] = $this->charFilter($delivery['logi_status']);
                $delivery_arr[$k]['sign_status'] = $delivery['sign_status'];
                $delivery_arr[$k]['sign_time'] = $delivery['sign_time'];
                
                //items
                foreach ($delivery['items'] as $key => $delivery_item){
                    $delivery_arr[$k]['items'][$delivery_item['item_id']]['item_id'] = $delivery_item['item_id'];
                    $delivery_arr[$k]['items'][$delivery_item['item_id']]['bn'] = $this->charFilter($delivery_item['bn']);
                    $delivery_arr[$k]['items'][$delivery_item['item_id']]['product_name'] = $this->charFilter($delivery_item['product_name']);
                    //$delivery_arr[$k]['items'][$delivery_item['item_id']]['spec_name'] = '';
                    $delivery_arr[$k]['items'][$delivery_item['item_id']]['price'] = $delivery_item['price'];
                    $delivery_arr[$k]['items'][$delivery_item['item_id']]['nums'] = $delivery_item['number'];
                    //$delivery_arr[$k]['items'][$delivery_item['item_id']]['pmt_price'] = $delivery_item['pmt_price'];
                    //$delivery_arr[$k]['items'][$delivery_item['item_id']]['sale_price'] = $delivery_item['sale_price'];
                    //$delivery_arr[$k]['items'][$delivery_item['item_id']]['apportion_pmt'] = $delivery_item['apportion_pmt'];
                    //$delivery_arr[$k]['items'][$delivery_item['item_id']]['sales_amount'] = $delivery_item['sales_amount'];
                    
                    //WMS仓储采购价格
                    $delivery_arr[$k]['items'][$delivery_item['item_id']]['purchase_price'] = $delivery_item['purchase_price'];
                    
                }
        }

        unset($original_delivery_data['lists']);
        
        $original_delivery_data['lists'] = $delivery_arr;
        
        return $original_delivery_data;
    }
    
    /**
     * 添加
     * @param mixed $params 参数
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回值
     */
    public function add($params,&$code,&$sub_msg){
        
    }
    
    
}