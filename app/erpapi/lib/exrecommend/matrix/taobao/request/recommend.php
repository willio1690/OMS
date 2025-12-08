<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

#菜鸟智选物流
class erpapi_exrecommend_matrix_taobao_request_recommend extends erpapi_exrecommend_request_recommend
{
    #获取智选cp和电子面单信息(淘宝那边：交易订单和已经存在的交易订单号不能交叉获取，上次处理过的，不能合没有处理过的混在一起，得是同一个批次的)
    /**
     * directRequest
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function directRequest($sdf){
        $data['sender'] = $sdf['sender_info'];
        $data['trade_order_info_list'] = $sdf['trade_order_info_list'];
        $params['smart_delivery_batch_request'] =  json_encode($data);
        $back =  $this->requestCall(STORE_CN_SMARTDELIVERY_GET, $params,array(),$sdf);
        return $this->backToResult($back,$sdf);
    }
    #查询商家是否订购智能发货引擎服务
    /**
     * issubscribe
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function issubscribe($sdf=[]){
        $this->title = '查询是否订购智能发货服务';
        $params = array();
        $rs = $this->requestCall(STORE_CN_SMARTDELIVERY_ISSUBSCRIBE, $params,array(),$sdf);
        $data = json_decode($rs['data'],true);
        $data = $data?:[];

        if(!$data['successful'])return false;
        return true;
    }
    #获取电子面单订购关系中智能发货引擎支持的合作物流公司
    /**
     * cpQuery
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function cpQuery($sdf){
        $params = array();
        $rs = $this->requestCall(STORE_CN_SMARTDELIVERY_CPQUERY, $params,array(),$sdf);
        return $rs;
    }
    #智能发货引擎仓维度策略查询
    /**
     * strategyQuery
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function strategyQuery($sdf){
        $params = array();
        $this->title = '获取智选物流仓策略';
        $rs = $this->requestCall(STORE_CN_SMARTDELIVERY_STRATEGY_QUERY, $params,array(),$sdf);
        return $rs;
    }
    #删除策略
    /**
     * strategyDel
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function strategyDel($sdf){
        $params['warehouse_id'] = $sdf['warehouse_id'];
        $this->title = '删除智选物流仓策略';
        $rs = $this->requestCall(STORE_CN_SMARTDELIVERY_STRATEGY_DELETE, $params,array(),$sdf);
        return $rs;
    }
    #智能发货引擎仓维度策略设置
    /**
     * strategyUpdate
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function strategyUpdate($sdf){
        $params['delivery_strategy_set_request'] = json_encode($sdf);
        $this->title = '设置智选物流仓策略';
        $rs = $this->requestCall(STORE_CN_SMARTDELIVERY_STRATEGY_UPDATE, $params,array(),$sdf);
        return $rs;
    }
    #查询智能发货引擎商家价格信息
    /**
     * priceofferQuery
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function priceofferQuery($sdf){
        $params['query_cp_price_info_request'] = json_decode($sdf);
        $rs = $this->requestCall(STORE_CN_SMARTDELIVERY_PRICEOFFERQUERY, $params,array(),$sdf);
        return $rs;
    }
    #商家修改智能发货引擎推荐的cp
    /**
     * cpModify
     * @return mixed 返回值
     */
    public function cpModify(){
    
    }
    /**
     * backToResult
     * @param mixed $back back
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function backToResult($back,&$sdf){
        $obj_waybill = app::get('logisticsmanager')->model('waybill');
        $obj_waybill_extends = app::get('logisticsmanager')->model('waybill_extend');
        $obj_waybill_channel = app::get('logisticsmanager')->model('channel');
        
        $obj_dly_corp = app::get('ome')->model('dly_corp');
        $oOperation_log = app::get('ome')->model('operation_log');
        
        $recommend_data = array();
        $recommend = empty($back['data']) ? '' : json_decode($back['data'], true);
        if(!$recommend)return false;
        $smart_delivery_response_wrapper_list = $recommend['smart_delivery_response_wrapper_list']['smart_delivery_response_wrapper'][0];
        if($smart_delivery_response_wrapper_list['success'] =='false') return false;
        $this_cp_code = $smart_delivery_response_wrapper_list['smart_delivery_response']['smart_delivery_cp_info']['cp_code'];#智能发货引擎推荐物流公司
        $the_waybill_code = $smart_delivery_response_wrapper_list['smart_delivery_response']['waybill_cloud_print_info']['waybill_code'];#电子面单号
        #智选物流，推荐物流和物流单号，2个必须都返回，少一个都不可以使用
        if(! $this_cp_code || !$the_waybill_code) return false;
        $print_data = $smart_delivery_response_wrapper_list['smart_delivery_response']['waybill_cloud_print_info']['print_data'];#模板内容
        $json_packet = json_decode($print_data,true);
        #根据发货地址和返回物流编码，重新找到面单来源
        $channel_id = $obj_waybill_channel->get_channel_id($this_cp_code,$json_packet['data']['sender']['address']);
        $the_corp_info = $obj_dly_corp->getlist('corp_id',array('channel_id'=>$channel_id,'type'=>$this_cp_code));
        if(!channel_id || !$the_corp_info)return false;
        //$oOperation_log->write_log('order_modify@ome',$orders['order_id'],'获取菜鸟智选物流并添加面单号'.$the_waybill_code);
        
       $waybill_data['waybill_number'] = $the_waybill_code;
       $waybill_data['channel_id'] = $channel_id;
       $waybill_data['logistics_code'] = $recommend_data['taobao']['cp_code'];
       $waybill_data['status'] = '0';
       $waybill_data['create_time'] = time();
       $obj_waybill->save($waybill_data);
       #模板数据也保存起来
       if($waybill_data['id']){
           $waybill_extends_data['waybill_id'] = $waybill_data['id'];
           $waybill_extends_data['json_packet'] =  $print_data;
           $obj_waybill_extends->save($waybill_extends_data);
       }
       $recommend_data['taobao']['corp_id_list'][$the_corp_info[0]['corp_id']]['selected'] = 'true';
       $recommend_data['taobao']['exrecommend_corp_id'] = $the_corp_info[0]['corp_id'];
       $recommend_data['taobao']['waybill_code'] = $the_waybill_code;
       return $recommend_data;
    }
}