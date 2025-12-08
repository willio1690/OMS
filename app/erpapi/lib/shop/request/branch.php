<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * Created by PhpStorm.
 * User: yaokangming
 * Date: 2019/4/23
 * Time: 15:45
 */
class erpapi_shop_request_branch extends erpapi_shop_request_abstract {

    /**
     * feedbackDelivery
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */

    public function feedbackDelivery($sdf) {
        $title = '待寻仓订单寻仓回写';
        $results = array(array(
            'order_sn' => $sdf['order_bn'],
            'feedback_status' => 'SUCCESS',
            'warehouse' => $sdf['warehouse']
        ));
        $params = array('feed_back_results' => json_encode($results));
        $rsp = $this->__caller->call(SHOP_BRANCH_FEEDBACK, $params, array(), $title, 10, $sdf['order_bn']);
        return $rsp;
    }


    /**
     * 创建Warehouse
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function createWarehouse($sdf){
        $title = '创建单个区域仓';

        $params = array(

            'out_warehouse_id'  =>  $sdf['out_warehouse_id'],
            'name'              =>  $sdf['name'],
            'intro'              => $sdf['name'],
        );

        $rsp = $this->__caller->call(SHOP_CREATE_WAREHOUSE, $params, array(), $title, 10, $sdf['name']);

        $result = array();
        $result['rsp']        = $rsp['rsp'];
        $result['err_msg']    = $rsp['err_msg'];
        $result['msg_id']     = $rsp['msg_id'];
        $result['res']        = $rsp['res'];
        $data       = json_decode($rsp['data'],1);
        $result['data'] = is_array($data) && is_array($data['results']) ? $data['results']['data'] : [];
       
        return $result;
    }


    /**
     * editWarehouse
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function editWarehouse($sdf){
        $title = '编辑单个区域仓';

        $params = array(

            'out_warehouse_id'  =>  $sdf['out_warehouse_id'],
            'name'              =>  $sdf['name'],
            'info'              =>  $sdf['name'],
        );

        $rs = $this->__caller->call(SHOP_EDIT_WAREHOUSE, $params, array(), $title, 10, $sdf['name']);
        return $rs;

    }

    /**
     * bindWarehouse
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function bindWarehouse($sdf){

        $title = '绑定地址到区域仓';
        $params = array(

            'out_warehouse_id'  =>  $sdf['out_warehouse_id'],
            'addr_list'              =>  $sdf['addr'],
         
        );

        $rsp = $this->__caller->call(SHOP_BIND_WAREHOUSE_ADDR, $params, array(), $title, 10, $sdf['out_warehouse_id']);

        $result = array();
        $result['rsp']        = $rsp['rsp'];
        $result['err_msg']    = $rsp['err_msg'];
        $result['msg_id']     = $rsp['msg_id'];
        $result['res']        = $rsp['res'];
        $data       = json_decode($rsp['data'],1);
        $result['data'] = is_array($data) && is_array($data['results']) ? $data['results']['data'] : [];
       
        return $result;

    }

    /**
     * unbindWarehouse
     * @return mixed 返回值
     */
    public function unbindWarehouse(){
        $title = '地址与区域仓解绑';
        $params = array(

            'out_warehouse_id'  =>  $sdf['out_warehouse_id'],
            'addr'              =>  $sdf['addr'],
         );

        $rs = $this->__caller->call(SHOP_UNBIND_WAREHOUSE_ADDR, $params, array(), $title, 10, $sdf['out_warehouse_id']);
        return $rs;
    }

    /**
     * 获取Province
     * @param mixed $sdf sdf
     * @return mixed 返回结果
     */
    public function getProvince($sdf){
        $title = '获取四级地址全量省份信息';
        $primary_bn = date('Ymd');
        
        $params = array(
           
        );
        $rsp =  $this->__caller->call(SHOP_GET_ADDRESS_PROVINCE, $params, array(), $title, 10, $primary_bn);
        $result = array();
        $result['rsp']        = $rsp['rsp'];
        $result['err_msg']    = $rsp['err_msg'];
        $result['msg_id']     = $rsp['msg_id'];
        $result['res']        = $rsp['res'];
        $data       = json_decode($rsp['data'],1);
        $data = $this->_formatProvinceData($data);
        $area = array();
        foreach($data as $v){
            $area[] = array(
                'shop_type'     =>  $this->__channelObj->channel['node_type'],
                'outregion_id'  =>  $v['province_id'],
                'outregion_name'=>  $v['province'],
                'region_grade'  =>  $sdf['region_grade'],
    
            );
        }
        $result['data'] = $area;
        return $result;
    }

    protected function _formatProvinceData($data) {
        if (is_array($data) && is_array($data['results']) && is_array($data['results']['data'])) {
            return $data['results']['data'];
        }
        return [];
    }

    protected $areaOutregionId = 'code';
    protected $areaOutregionName = 'name';
    protected $areaOutparentId = 'father_code';
    protected function _formatAreasByProvince($data) {
        return $data;
    }
    
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
        $data = $this->_formatAreasByProvince($data);
        $address = array();
        foreach ($data as $key => $value) {
            foreach ($value['sub_districts'] as $one => $oneValue) {
                $address[] = array(
                    'shop_type'      => $this->__channelObj->channel['node_type'],
                    'outregion_id'   => $oneValue[$this->areaOutregionId],
                    'outregion_name' => $oneValue[$this->areaOutregionName],
                    'region_grade'   => 2,
                    'outparent_id'   => $oneValue[$this->areaOutparentId],
                );
                foreach ($oneValue['sub_districts'] as $two => $twoValue) {
                    $address[] = array(
                        'shop_type'      => $this->__channelObj->channel['node_type'],
                        'outregion_id'   => $twoValue[$this->areaOutregionId],
                        'outregion_name' => $twoValue[$this->areaOutregionName],
                        'region_grade'   => 3,
                        'outparent_id'   => $twoValue[$this->areaOutparentId],
                    );
                    
                    if(empty($twoValue['sub_districts'])){
                        continue;
                    }
                    
                    //镇或者街道
                    foreach ($twoValue['sub_districts'] as $threeKey => $threeVal) {
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
        }
        $result['data'] = $address;
        return $result;
    }


    /**
     * 获取Stock
     * @param mixed $sdf sdf
     * @return mixed 返回结果
     */
    public function getStock($sdf)
    {
        $title = '查询渠道库存['.$sdf['branch_bn'].']';
        
        $params = array(
           'out_warehouse_id' => $sdf['branch_bn'],
           'sku_id'           => $sdf['material_bn'],
        );

        $result =  $this->__caller->call(SHOP_ITEM_STOCK_GET, $params, array(), $title, 10, $sdf['material_bn']);

        if ($result['rsp'] == 'succ') {
            $result['data'] = @json_decode($result['data'],true);
        }
        return $result;
    }
    
    /**
     * 同步OMS仓库给到翱象系统
     * 
     * @param array $params
     * @return array
     */
    public function createAoxiangWarehouse($branchList)
    {
        $title = '同步OMS仓库给到翱象系统';
        
        $original_bn = $branchList[0]['shop_bn'];
        $original_bn = ($original_bn ? $original_bn : date('Ymd', time()));
        
        //warehouse
        $warehouse_infos = array();
        foreach ($branchList as $key => $branchInfo)
        {
            $status = ($branchInfo['disabled'] == 'true' ? '0' : '1');
            
            //联系人信息
            $contact_info = array (
                'type' => 'mr', //联系人分组：mr=默认组、th=退货组、kf=客服组、rk=入库组、ck=出库组、kn=库内组
                'name' => $branchInfo['uname'], //联系人名称
                'mobile' => $branchInfo['mobile'], //联系人手机
                'tel' => $branchInfo['phone'], //固定电话
                'province' => $branchInfo['province'], //省份
                'city' => $branchInfo['city'], //城市
                'area' => $branchInfo['area'], //地区
                'town' => $branchInfo['town'], //乡镇
                'detail_address' => $branchInfo['address'], //详细地址
            );
            
            //仓库信息
            $branchInfo = array (
                'wms_warehouse_code' => $branchInfo['wms_channel_bn'], //WMS仓储编码
                'wms_warehouse_name' => $branchInfo['wms_channel_name'], //WMS仓储名称
                'erp_warehouse_code' => $branchInfo['branch_bn'], //OMS仓库编码
                'erp_warehouse_name' => $branchInfo['branch_name'], //OMS仓库名称
                'erp_warehouse_biz_code' => $branchInfo['branch_bn'], //商家编码
                'province' => $branchInfo['province'], //省份
                'city' => $branchInfo['city'], //城市
                'area' => $branchInfo['area'], //地区
                'town' => $branchInfo['town'], //乡镇
                'detail_address' => $branchInfo['address'], //详细地址
                'zip_code' => $branchInfo['zip'],
                'status' => $status, //状态：0=停用,1=启用
                'contact_infos' => $contact_info, //联系人信息(必填1个)
            );
            
            $warehouse_infos[] = $branchInfo;
        }
        
        //params
        $requestParams = array(
            //'request_id' => uniqid(),
            //'request_time' => time(),
            'warehouse_infos' => json_encode($warehouse_infos), //仓库数组,最多50条
        );
        
        //callback
        $callback = array();
        
        //request
        $result = $this->__caller->call(SHOP_AOXIANG_WAREHOUSE_CREATE, $requestParams, $callback, $title, 10, $original_bn);
        
        //format
        if($result['rsp'] == 'succ'){
            $dataList = $result['data'];
            
            //unset
            unset($result['data']);
            
            //string
            if(is_string($dataList)){
                $dataList = json_decode($dataList, true);
            }
            
            $detailItems = $dataList['data']['detail']['detail_item'];
            
            $resData = array();
            foreach ((array)$detailItems as $key => $val)
            {
                $warehouse_code = $val['warehouse_code'];
                
                //check
                if($val['success'] == 1 || $val['success'] == 'true'){
                    $resData['succ'][$warehouse_code] = array('warehouse_code'=>$warehouse_code, 'seller_id'=>$val['seller_id']);
                }else{
                    $resData['fail'][$warehouse_code] = array('warehouse_code'=>$warehouse_code, 'message'=>$val['message']);
                }
            }
            
            $result['datalist'] = $resData;
        }
        
        return $result;
    }
    
    /**
     * 删除翱象系统里OMS同步的仓库
     * 
     * @param array $params
     * @return array
     */
    public function deleteAoxiangWarehouse($branchList)
    {
        $title = '删除翱象系统里OMS同步的仓库';
        
        $original_bn = $branchList[0]['shop_bn'];
        $original_bn = ($original_bn ? $original_bn : date('Ymd', time()));
        
        //warehouse
        $warehouse_infos = array();
        foreach ($branchList as $key => $branchInfo)
        {
            //联系人信息
            $contact_info = array (
                'type' => 'mr', //联系人分组：mr=默认组、th=退货组、kf=客服组、rk=入库组、ck=出库组、kn=库内组
                'name' => $branchInfo['uname'], //联系人名称
                'mobile' => $branchInfo['mobile'], //联系人手机
                'tel' => $branchInfo['phone'], //固定电话
                'province' => $branchInfo['province'], //省份
                'city' => $branchInfo['city'], //城市
                'area' => $branchInfo['area'], //地区
                'town' => $branchInfo['town'], //乡镇
                'detail_address' => $branchInfo['address'], //详细地址
            );
            
            //仓库信息
            $branchInfo = array (
                'wms_warehouse_code' => $branchInfo['wms_channel_bn'], //WMS仓储编码
                'wms_warehouse_name' => $branchInfo['wms_channel_name'], //WMS仓储名称
                'erp_warehouse_code' => $branchInfo['branch_bn'], //OMS仓库编码
                'erp_warehouse_name' => $branchInfo['branch_name'], //OMS仓库名称
                'erp_warehouse_biz_code' => $branchInfo['branch_bn'], //商家编码
                'province' => $branchInfo['province'], //省份
                'city' => $branchInfo['city'], //城市
                'area' => $branchInfo['area'], //地区
                'town' => $branchInfo['town'], //乡镇
                'detail_address' => $branchInfo['address'], //详细地址
                'zip_code' => $branchInfo['zip'],
                'status' => '0', //状态：0=停用,1=启用
                'contact_infos' => $contact_info, //联系人信息(必填1个)
            );
            
            $warehouse_infos[] = $branchInfo;
        }
        
        //params
        $requestParams = array(
            'warehouse_infos' => json_encode($warehouse_infos), //仓库数组,最多50条
        );
        
        //callback
        $callback = array();
        
        //request
        $result = $this->__caller->call(SHOP_AOXIANG_WAREHOUSE_CREATE, $requestParams, $callback, $title, 10, $original_bn);
        
        //format
        if($result['rsp'] == 'succ'){
            $dataList = $result['data'];
            
            //unset
            unset($result['data']);
            
            //string
            if(is_string($dataList)){
                $dataList = json_decode($dataList, true);
            }
            
            $detailItems = $dataList['data']['detail']['detail_item'];
            
            $resData = array();
            foreach ((array)$detailItems as $key => $val)
            {
                $warehouse_code = $val['warehouse_code'];
                
                //check
                if($val['success'] == 1 || $val['success'] == 'true'){
                    $resData['succ'][$warehouse_code] = array('warehouse_code'=>$warehouse_code, 'seller_id'=>$val['seller_id']);
                }else{
                    $resData['fail'][$warehouse_code] = array('warehouse_code'=>$warehouse_code, 'message'=>$val['message']);
                }
            }
            
            $result['datalist'] = $resData;
        }
        
        return $result;
    }
}
