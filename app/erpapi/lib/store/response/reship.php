<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 门店响应退货入库处理类
 *
 * @author xiayuanjun@shopex.cn
 * @version 0.1
 *
 */
class erpapi_store_response_reship extends erpapi_store_response_abstract
{

    /**
     * status_update
     * @param mixed $params 参数
     * @return mixed 返回值
     */

    public function status_update($params){

        $this->__apilog['title'] = $this->__channelObj->store['name'] . '退货单' . $params['reship_bn'];
        $this->__apilog['original_bn'] = $params['reship_bn'];


        $data = array(
            'reship_bn'    => trim($params['reship_bn']),
            'logi_code'    => $params['logistics'],
            'logi_no'      => $params['logi_no'],
            'branch_bn'    => $params['warehouse'],
            'memo'         => $params['remark'],
            'operate_time' => $params['operate_time'] ? $params['operate_time'] : date('Y-m-d H:i:s'),
            'server_id'    => $this->__channelObj->store['server_id'],
        );
        $params['status'] = $params['status'] ? $params['status'] : $params['io_status'];
        switch($params['status']){
            case 'FINISH': $data['status']='FINISH';break;
            case 'PARTIN': $data['status']='PARTIN';break;
            case 'CLOSE':
            case 'FAILED':
            case 'DENY':
                $data['status'] = 'CLOSE'; break;
            default:
                $data['status'] = $params['status'];break;
        }

        $reship_items = array();
        $items = isset($params['item']) ? json_decode($params['item'],true) : array();
        if($items){
            foreach($items as $key=>$val){
                if (!$val['product_bn'] && !$val['bn']) continue;

                $bn = $val['product_bn'] ? $val['product_bn'] : $val['bn'];
                $sn_list = $val['sn_list'];
                $reship_items[$bn]['bn']            = $bn;
                $reship_items[$bn]['normal_num']    = (int)$reship_items[$bn]['normal_num'] + (int)$val['normal_num'];
                $reship_items[$bn]['defective_num'] = (int)$reship_items[$bn]['defective_num'] + (int)$val['defective_num'];
                $reship_items[$bn]['sn_list']    = $sn_list ;
            }
        }

        $data['items'] = $reship_items;
        return $data;
    }
}
