<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_shop_matrix_weimobv_request_branch extends erpapi_shop_request_branch {

    protected function _formatProvinceData($data) {
        $return = [];
        if (is_array($data) && is_array($data['data'])) {
            foreach($data['data'] as $v) {
                $return[] = [
                    'province_id' => $v['code'],
                    'province' => $v['name'],
                ];
            }
        }
        return $return;
    }

    protected $areaOutregionId = 'code';
    protected $areaOutregionName = 'name';
    protected $areaOutparentId = 'parentCode';
    /**
     * 获取AreasByProvince
     * @param mixed $data 数据
     * @return mixed 返回结果
     */
    public function getAreasByProvince($data)
    {
        $title             = '获取四级地址' . $data['outregion_name'] . '信息';
        
        $params            = array(
            'province_id' => $data['outregion_id']
        );
        $rsp               = $this->__caller->call(STORE_ADDRESS_GETBY_PROVINCE, $params, array(), $title, 10, $data['outregion_name']);
        $result            = array();
        $result['rsp']     = $rsp['rsp'];
        $result['err_msg'] = $rsp['err_msg'];
        $result['msg_id']  = $rsp['msg_id'];
        $result['res']     = $rsp['res'];
        $data              = json_decode($rsp['data'], 1);
        if(!is_array($data) || !is_array($data['data'])) {
            return $result;
        }
        $address = array();
        foreach ((array)$data['data'] as $key =>  $oneValue) {
            $address[] = array(
                'shop_type'      => $this->__channelObj->channel['node_type'],
                'outregion_id'   => $oneValue[$this->areaOutregionId],
                'outregion_name' => $oneValue[$this->areaOutregionName],
                'region_grade'   => 2,
                'outparent_id'   => $oneValue[$this->areaOutparentId],
            );
            $params            = array(
                'province_id' => $oneValue[$this->areaOutregionId]
            );
            $rsp               = $this->__caller->call(STORE_ADDRESS_GETBY_PROVINCE, $params, array(), $title, 10, $oneValue[$this->areaOutregionName]);
            if(empty($rsp['data'])) {
                continue;
            }
            $oneData = json_decode($rsp['data'], 1);
            if(!is_array($oneData) || !is_array($oneData['data'])) {
                continue;
            }
            foreach ($oneData['data'] as $two => $twoValue) {
                $address[] = array(
                    'shop_type'      => $this->__channelObj->channel['node_type'],
                    'outregion_id'   => $twoValue[$this->areaOutregionId],
                    'outregion_name' => $twoValue[$this->areaOutregionName],
                    'region_grade'   => 3,
                    'outparent_id'   => $twoValue[$this->areaOutparentId],
                );
                //暂不同步四级地址
                // continue;
                $params            = array(
                    'province_id' => $twoValue[$this->areaOutregionId]
                );
                $rsp               = $this->__caller->call(STORE_ADDRESS_GETBY_PROVINCE, $params, array(), $title, 10, $twoValue[$this->areaOutregionName]);
                if(empty($rsp['data'])) {
                    continue;
                }
                $twoData = json_decode($rsp['data'], 1);
                if(!is_array($twoData) || !is_array($twoData['data'])) {
                    continue;
                }
                
                //镇或者街道
                foreach ($twoData['data'] as $threeKey => $threeVal) {
                    $address[] = array(
                            'shop_type' => $this->__channelObj->channel['node_type'],
                            'outparent_id' => $threeVal[$this->areaOutparentId], //父ID
                            'outregion_id' => $threeVal[$this->areaOutregionId], //镇或者街道ID
                            'outregion_name' => $threeVal[$this->areaOutregionName], //镇或者街道名称
                            'region_grade' => 4,
                    );
                }
                
            }
        }
        $result['data'] = $address;
        return $result;
    }

}