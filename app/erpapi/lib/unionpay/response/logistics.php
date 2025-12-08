<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_unionpay_response_logistics extends erpapi_hqepay_response_abstract {
    #华强宝的物流状态和ERP物流状态映射关系
    static public $state_status = array(
        '15'=>'1',#已揽收
        '18'=>'3',#已签收
   
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
        $this->__apilog['title'] = '接受物流信息（物流单号：' . $sdf['waybillNo']. '）';
        $this->__apilog['original_bn'] = $sdf['waybillNo'];
        $data = array(
            'logi_status' => self::$state_status[$sdf['wlStatus']],
            'logi_no' => $sdf['waybillNo'],
        );
        if ($data['logi_status'] == '1'){
            $data['sign_time'] = strtotime($sdf['createTime']);
        }
        if ($data['logi_status'] == '2'){
             $data['embrace_time'] = strtotime($sdf['createTime']);
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