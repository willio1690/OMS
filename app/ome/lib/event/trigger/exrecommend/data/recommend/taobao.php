<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_event_trigger_exrecommend_data_recommend_taobao extends ome_event_trigger_exrecommend_data_recommend_common{
    public function getExrecommendSdf($order_data) {
        $tmall_shops = $this->get_all_tmall_shops();#所有天猫类型店铺
        parent::getExrecommendSdf($order_data);
        $trade_order_info = array();
        $order_list = array();

        #先把订单，按店铺平台分开
        foreach($this->__order_info as $orders){
            $order_shop_type = $orders['shop_type'];
            if(in_array($orders['shop_id'],$tmall_shops)) $order_shop_type='tmall';
            $trade_order_info[$order_shop_type][$orders['order_id']] = $orders;
            if($orders['order_bn'] == $order_data['main_order_bn']){
                $this->set_main_order_info($orders);#需要使用主单收货地址，做为本次智能发货的收货地址
            }
            $order_list[$orders['order_bn']] = array('order_id'=>$orders['order_id'],'process_status'=>$orders['process_status'],'logi_no'=>$orders['logi_no']);
        }
        #如果订单跨平台，不能再使用智选物流，否则，同一批合单订单，必然会生成多个物流单号，导致生成发货单时丢失面单
        if(count($trade_order_info)>1) return false;
        $recipient_info = $this->get_recipient_info();#主单的收货地址
        $raletion = array();
        #再按店铺平台，组装数据
        foreach($trade_order_info as $shop_type=>$shop_order){
            $order_ids = array_keys($shop_order);
            #店铺平台  
            $list['order_info']['order_channel_type'] = $this->get_shop_type($shop_type);
            #每个平台的订单列表
            $list['order_info']['trade_order_list'] = $this->get_order_info($shop_order);
            $list['package_info'] = $this->get_package_info($order_ids);
            $list['recipient'] = $recipient_info;
            $list['user_id'] = $this->__seller_id;
            
            #生成一个校验码(保证渠道、订单、包裹一样)
            $list['object_id'] = sprintf("%u",CRC32(json_encode($list)));;
            
            $this->__sdf['trade_order_info_list'][] = $list;
        }
        $this->__sdf['order_list'] = $order_list;
        return $this->__sdf;
    }
    public function get_package_info($order_ids){
        #检测已生成的包裹数量（拆单订单，在这里，只会有一个order_id）
        $nums = app::get('ome')->model('delivery')->checkDeliverNumsById($order_ids);
        $items_list = parent::get_package_info($order_ids);
        $package_info['id'] = $nums+1;#没有拆单就是第一个，有拆单，就是第N+1个
        $package_info['item_list'] =  $items_list;
        $package_info['volume'] = '0';
        $package_info['weight'] = '0';

        return $package_info;
    }
    #按发货网点，设置策略
    public function getStrategySdf($channel_id){
        $obj_dly_corp = app::get('ome')->model('dly_corp');
        $logisticsmanager_express_template = app::get('logisticsmanager')->model('express_template');
        $logisticsmanager_channel= app::get('logisticsmanager')->model('channel');
        #地址相同的渠道来源
        $same_send_area_channels = $logisticsmanager_channel->get_same_send_area_channels($channel_id);
        #所有淘宝类型的面单来源
        $support_logistics_code = $this->support_logistics_code();
        $filter['type|in'] = $support_logistics_code;
        $filter['channel_id'] =  $same_send_area_channels;
        $filter['tmpl_type'] = 'electron';
        $filter['disabled'] = 'false';
        
        #获取所有与发货网点相关的物流公司
        $all_dly_corp = $obj_dly_corp->getList('type,name,prt_tmpl_id',$filter);
        if(empty($all_dly_corp))return false;
        $prt_tmpl_ids = array();
        #遍历所有用到的打印id
        foreach($all_dly_corp as $corp_info){
            $prt_tmpl_ids[$corp_info['prt_tmpl_id']] = $corp_info['prt_tmpl_id'];
        }
        #根据打印ids，去检索出所有菜鸟标准打印模板，自定义的菜鸟模板不要
        $standard_template_infos = $logisticsmanager_express_template->getList('template_id,template_name,out_template_id',array('template_id|in'=>$prt_tmpl_ids,'template_type'=>array('cainiao_standard')));
        if(empty($standard_template_infos)) return false;
        $standard_template_id = array();
        foreach($standard_template_infos as $v){
            $standard_template_id[$v['template_id']] = $v['out_template_id'];
        }
        #菜鸟的智选，仓库与模板挂钩，一个仓库，合作的物流，只能回传一个模板
        foreach($all_dly_corp as $k=>$corp_info){
            $code_type = $corp_info[type];
            #1、非菜鸟标准模板物流，不能去创建或更新发货网点的策略
            if(!$standard_template_id[$corp_info['prt_tmpl_id']])continue;
            #2、一个仓库，合作的物流，只能回传一个模板，任意取一个code_type的模板
            $cocp_info_list[$code_type]['address'] = $this->__sdf['sender_info']['address'];#同一个网点，发货都是同一个
            $cocp_info_list[$code_type]['cloud_template_id'] = $standard_template_id[$corp_info['prt_tmpl_id']];#云打印模板
            $cocp_info_list[$code_type]['cp_code'] = $support_logistics_code[$corp_info['type']];
            $cocp_info_list[$code_type]['status'] = '1';#状态: 0-禁用, 1-启用  
        }
        if(!$cocp_info_list) return false;
        $params['delivery_strategy_info']['cocp_info_list'] = array_values($cocp_info_list);
        $params['delivery_strategy_info']['buyer_message_rule'] = '1';#识别买家备注: 0-忽略, 1-识别, 2-仅识别合作cp
        $params['delivery_strategy_info']['seller_memo_rule'] = '1';#识别卖家备注: 0-忽略, 1-识别, 2-仅识别合作cp
        return $params;
    }
    public function get_all_tmall_shops(){
        $rs = app::get('ome')->model('shop')->getList('shop_id',array('shop_type'=>'taobao','tbbusiness_type'=>'B'));
        $tmall_shops = array_map('current', $rs);
        return $tmall_shops;
    }
}