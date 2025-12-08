<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_hqepay_response_logistics extends erpapi_hqepay_response_abstract {
    #华强宝的物流状态和ERP物流状态映射关系
    static public $state_status = array(
        '1'=>'1',#已揽收
        '2'=>'2',#在途中
        '3'=>'3',#已签收
        '4'=>'4',#退件/问题件
        '5'=>'5',#待取件
        '6'=>'6',#待派件
        '301'=>'3',#正常签收
        '302'=>'3',#派件异常后最终签收
        '304'=>'3',#代收签收
        '311'=>'3',#快递柜或驿站签收
    );

    /**
     * @param $params array
     * @return array (
     *              'State' => 1, #物流状态
     *              'LogisticCode' => '', #物流单号
     *          )
     */
    protected function _formatParams($params) {
        return $params;
    }

    public function push($params){
        $sdf = $this->_formatParams($params);
        $this->__apilog['title'] = '接受物流信息（物流单号：' . $sdf['LogisticCode']. '）';
        $this->__apilog['original_bn'] = $sdf['LogisticCode'];
        $data = array(
            'logi_status' => self::$state_status[$sdf['State']],
            'logi_no' => $sdf['LogisticCode'],
        );
        $Traces = $params['Traces'] ? json_decode($params['Traces'],true) : '';
        if ( in_array($data['logi_status'],array('1','3','4')) ){
            if ($Traces){
                foreach ($Traces as $trace){
                    $trace['Action'] = substr($trace['Action'], 0, 1);
                    if ($trace['Action'] == '3'){
                        $data['sign_time'] = strtotime($trace['AcceptTime']);
                    }
                    if ($trace['Action'] == '1' && !isset($data['embrace_time'])){
                        $data['embrace_time'] = strtotime($trace['AcceptTime']);
                    }
                    if ($trace['Action'] == '4'){
                        $data['problem_time'] = strtotime($trace['AcceptTime']);
                    }
                }
            }
        }
        
        
        $filter = array(
            'process' => 'TRUE',
            'pause' => 'FALSE',
            'logi_no' => $data['logi_no'],
            'status' => 'succ',
            'logi_status|noequal' => $data['logi_status']
        );
        $deliveryData = app::get('ome')->model('delivery')->getList('delivery_id,is_cod', $filter, 0, 1);
        if($deliveryData) {
            $data['delivery_id'] = $deliveryData[0]['delivery_id'];
            $data['is_cod'] = $deliveryData[0]['is_cod'];
        }
        return $data;
    }
}