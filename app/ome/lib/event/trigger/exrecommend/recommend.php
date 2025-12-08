<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

#智选物流 
class ome_event_trigger_exrecommend_recommend{
    #检测商家是否订购智能发货服务(需要检测，是不是会有人在多个店铺订购，如果是在多个店铺订购这种怎么处理)
    public function issubscribe(){
        $channel_type = 'taobao';
        $channel_info = $this->get_channel_info($channel_type);

        if(!$channel_info)return false;
        return kernel::single('erpapi_router_request')->set('exrecommend',$channel_info['shop_id'])->recommend_issubscribe();
    }
    #获取智选物流
    public function exrecommend($branch_id,$order_data){
        $channel_type = 'taobao';#先检查是属于快递鸟或是菜鸟的智选物流
        $channel_info = $this->get_channel_info($channel_type,$branch_id);
     
        if(!$channel_info)return false;
        $sdf = kernel::single('ome_event_trigger_exrecommend_data_recommend_router')->setChannel($channel_type)->init($channel_info)->getExrecommendSdf($order_data);
       
        if (!$sdf) return false;
        $rs = kernel::single('erpapi_router_request')->set('exrecommend',$channel_info['shop_id'])->recommend_directRequest($sdf);
        return $rs;
    }
    #获取电子面单订购关系中智能发货引擎支持的合作物流公司
   public function cpQuery(){
        $channel_type = app::get('ome')->getConf('ome.exrecommend.channel.type');
        $channel_info = $this->get_channel_info($channel_type);
        if(!$channel_info)return false;
        return kernel::single('erpapi_router_request')->set('exrecommend',$channel_info['shop_id'])->recommend_cpQuery();
    } 
    #(业务上，暂时没用到,可能以后会用到)
/*     public function strategyDel($warehouse_id){
        $channel_type = app::get('ome')->getConf('ome.exrecommend.channel.type');
        $channel_info = $this->get_channel_info($channel_type);
        if(!$channel_info)return false;
        $sdf['warehouse_id'] = $warehouse_id;
        return kernel::single('erpapi_router_request')->set('exrecommend',$channel_info['shop_id'])->recommend_strategyDel($sdf);
    } */
    #智能发货引擎仓维度策略查询
     public function strategyQuery(){
        $channel_type = 'taobao';
        $channel_info = $this->get_channel_info($channel_type);
        if(!$channel_info)return false;
        return kernel::single('erpapi_router_request')->set('exrecommend',$channel_info['shop_id'])->recommend_strategyQuery();
    } 
    #智能发货引擎仓维度策略设置
    public function strategyUpdate($channel_id){
        $channel_type = 'taobao';
        $channel_info = $this->get_channel_info($channel_type,'',$channel_id);

        if(!$channel_info)return false;
        $sdf = kernel::single('ome_event_trigger_exrecommend_data_recommend_router')->setChannel($channel_type)->init($channel_info)->getStrategySdf($channel_id);  

        if (!$sdf) return true;
        return kernel::single('erpapi_router_request')->set('exrecommend',$channel_info['shop_id'])->recommend_strategyUpdate($sdf);
    }
    #查询智能发货引擎商家价格信息
/*     public function priceofferQuery(){
        $channel_type = app::get('ome')->getConf('ome.exrecommend.channel.type');
        $channel_info = $this->get_channel_info($channel_type);
        if(!$channel_info)return false;
        $sdf['query_cp_price_info_request'] = array();
        return kernel::single('erpapi_router_request')->set('exrecommend',$channel_info['shop_id'])->recommend_priceofferQuery($sdf);
    } */
    protected  function get_channel_info($channel_type='taobao',$branch_id='',$channel_id=''){
        if(!$channel_type)return false;
        if(in_array($channel_type,array('taobao'))){
            $obj_channel = app::get('logisticsmanager')->model('channel');
            #审单,选物流的时候，是按仓库，根据客户选择仓库地区和地址，生成send_area_id，然后去找电子面单来源
            if($branch_id){
                $obj_branch = app::get('ome')->model('branch');
                $rs = $obj_branch->getList('area,address',array('branch_id'=>$branch_id));
                $area = $rs[0]['area'];
                kernel::single('ome_func')->split_area( $area);
                #必须完全一致，可能有省份简写的情况，仓库区域和地址一定要要和淘宝的匹配
                $send_area = $area[0].'-'.$area[1].'-'.$area[2].'-'.$rs[0]['address'] ;
                
                $send_area_id = sprintf("%u",CRC32($send_area));
                $channel_filter['send_area_id'] =  $send_area_id; 
            }
            #设置策略的时候，是按电子面单来源的发货地址，直接同步物流公司到菜鸟后台
            else{
                $channel_filter['channel_id'] = $channel_id;
            }
            $warehouse_info = $obj_channel->get_taobao_channel(true,$channel_filter);
            $channel_info = $warehouse_info[0];
          
            if(empty($channel_info))return false;
        }
        return $channel_info;
    }
}