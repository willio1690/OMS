<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_event_trigger_exrecommend_data_recommend_hqepay extends ome_event_trigger_exrecommend_data_recommend_common{
    public function getExrecommendSdf($order_params) {
        $this->set_recipient_info($order_params);
        $all_order_ids = $order_params['combine_order_ids'];
        $order_data = app::get('ome')->model("orders")->get_order_infos($all_order_ids);
        foreach($order_data as $order_info){
            $detail_list[] = array(
                'tid'=>$order_params['main_order_bn'],
                'iscod'=>'0', # 是否代收货款：0.否，1.是
                'nonuse_express'=>'', #不使用的物流公司列表
                'buyer_remark'=>$order_info['custom_mark']?$order_info['custom_mark']:'', #买家留言
                'customer_remark'=>$order_info['mark_text']?$order_info['mark_text']:'', #客服备注
                'enable_airlines'=>'', #是否航空禁运：0.否，1.是
                'receiver'=>json_encode($this->__sdf['recipient_info']['address']),
                'seceiver'=>json_encode($this->__sdf['sender_info']['address']),
                'goods'=>$order_info['order_item_list'],
            );
        }
        $data['detail_list'] = json_encode($detail_list);
        $data['from_node_id'] = base_shopnode::node_id('ome');
        $data['warehouse_id'] = $this->__branch_id;
        return $data;
    }
    #收货方信息
    public function set_recipient_info($order_params){
        preg_match("/:(.*):/",  $order_params['main_ship_area'],$_ship_area);
        if($_ship_area[0]){
            $ship_area = explode('/', $_ship_area[1]);
        }
        $this->__sdf['recipient_info'] = array(
            'address'=> array(
                'ProvinceName'=>$ship_area[0],
                'CityName'=>$ship_area[1],
                'ExpAreaName'=>$ship_area[2]?$ship_area[2]:$ship_area[1],
                'Address'=>'',
            ),
        );
    }
    #发货方地址信息
    function set_sender_info($channel_info){
        $branch_info = app::get('ome')->model('branch')->getList('area',array('branch_id'=>$this->__branch_id));
        if(empty( $branch_info))return false;
        preg_match("/:(.*):/",  $branch_info[0]['area'],$tmp_branch_area);
        if($tmp_branch_area[0]){
            $branch_area = explode('/', $tmp_branch_area[1]);
        }
        $this->__sdf['sender_info'] = array(
            'address'=> array(
                'ProvinceName'=>$branch_area[0],
                'CityName'=>$branch_area[1],
                'ExpAreaName'=>$branch_area[2]?$branch_area[2]:$branch_area[1],
                'Address'=>'',
            ),
        );
    }  
}