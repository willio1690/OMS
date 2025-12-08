<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 快手
 * Class erpapi_shop_matrix_kuaishou_request_branch
 */
class erpapi_shop_matrix_kuaishou_request_branch extends erpapi_shop_request_branch
{
    /**
     * 快手获取平台省市区地址code
     * @param $sdf
     * @return array
     * @author db
     * @date 2023-05-09 3:47 下午
     */

    public function getProvince($sdf)
    {
        $title      = '快手获取四级地址全量省份信息';
        $primary_bn = date('Ymd');
        
        $params            = array();
        $rsp               = $this->__caller->call(SHOP_GET_ADDRESS_PROVINCE, $params, array(), $title, 10, $primary_bn);
        $result            = array();
        $result['rsp']     = $rsp['rsp'];
        $result['err_msg'] = $rsp['err_msg'];
        $result['msg_id']  = $rsp['msg_id'];
        $result['res']     = $rsp['res'];
        $data              = json_decode($rsp['data'], 1);
        $data              = $data['results'];
        $node_type = $this->__channelObj->channel['node_type'];
        $address = array();
        foreach ($data as $v) {
            $province_id = $v['code']; //省ID
            
            $address[] = array(
                'shop_type'      => $node_type,
                'outregion_id'   => $v['code'],
                'outregion_name' => $v['name'],
                'region_grade'   => $sdf['region_grade'],
            );
            
            foreach ($v['children'] as $one => $oneValue) {
                $city_id = $oneValue['code']; //市ID
                
                $address[] = array(
                    'shop_type'      => $node_type,
                    'outparent_id' => $province_id,
                    'outregion_id'   => $oneValue['code'],
                    'outregion_name' => $oneValue['name'],
                    'region_grade'   => 2,
                );
                foreach ($oneValue['children'] as $two => $twoValue) {
                    $district_id = $twoValue['code']; //地区ID
                    
                    $address[] = array(
                        'shop_type'      => $node_type,
                        'outparent_id' => $city_id,
                        'outregion_id'   => $twoValue['code'],
                        'outregion_name' => $twoValue['name'],
                        'region_grade'   => 3,
                    );
                    
                    if (empty($twoValue['children'])) {
                        continue;
                    }
                    
                    //镇或者街道
                    foreach ($twoValue['children'] as $threeKey => $threeVal) {
                        $address[] = array(
                            'shop_type'      => $node_type,
                            'outparent_id' => $district_id,
                            'outregion_id'   => $threeVal['code'], //镇或者街道ID
                            'outregion_name' => $threeVal['name'], //镇或者街道名称
                            'region_grade'   => 4,
                        );
                    }
                }
            }
        }
        $result['data'] = $address;
        
        return $result;
    }
    
    /**
     * 快手获取四级地区信息(平台不支持,矩阵单独进行兼容)
     * 
     * @param $sdf
     * @return array
     * @author db
     * @date 2023-05-09 3:47 下午
     */
    public function getAreasByProvince($data)
    {
        $title = '快手获取[' . $data['outregion_name'] . ']地区库';
        $primary_bn = date('Ymd');
        
        //node_type
        $node_type = $this->__channelObj->channel['node_type'];
        $province_id = $data['outregion_id']; //省ID
        
        //request
        $params = array(
            'province_id' => $province_id
        );
        $rsp = $this->__caller->call(STORE_ADDRESS_GETBY_PROVINCE, $params, array(), $title, 10, $primary_bn);
        if($rsp['rsp'] != 'succ'){
            $rsp['error_msg'] = ($rsp['err_msg'] ? $rsp['err_msg'] : $rsp['msg']);
            return $rsp;
        }
        
        //json
        $data = json_decode($rsp['data'], 1);
        $regionList = $data['results']['children'];
        if(empty($regionList)){
            $rsp['rsp'] = 'fail';
            $rsp['error_msg'] = '平台没有返回四级地区列表数据';
            return $rsp;
        }
        
        //list
        $dataList = array();
        foreach ($regionList as $parantKey => $parentVal)
        {
            $city_id = $parentVal['code']; //市ID
            
            //市级
            $dataList[] = array(
                'shop_type' => $node_type,
                'outparent_id' => $province_id,
                'outregion_id' => $city_id,
                'outregion_name' => $parentVal['name'],
                'region_grade' => 2,
            );
            
            //check
            if(empty($parentVal['children'])){
                continue;
            }
            
            //children
            foreach ($parentVal['children'] as $childKey => $childVal)
            {
                $district_id = $childVal['code']; //地区ID
                
                //区级
                $dataList[] = array(
                    'shop_type' => $node_type,
                    'outparent_id' => $city_id,
                    'outregion_id' => $district_id,
                    'outregion_name' => $childVal['name'],
                    'region_grade' => 3,
                );
                
                //check
                if(empty($childVal['children'])){
                    continue;
                }
                
                //children
                foreach ($childVal['children'] as $townKey => $townVal)
                {
                    $town_id = $townVal['code']; //镇ID
                    
                    //镇级
                    $dataList[] = array(
                        'shop_type' => $node_type,
                        'outparent_id' => $district_id,
                        'outregion_id' => $town_id,
                        'outregion_name' => $townVal['name'],
                        'region_grade' => 4,
                    );
                }
            }
        }
        
        //unset
        unset($rsp, $regionList);
        
        $msg = $title.'成功';
        return $this->succ($msg, '200', $dataList);
    }
}