<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class eccommon_platform_regions{

    /**
     * sync
     * @param mixed $data 数据
     * @return mixed 返回值
     */
    public function sync($data){
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        
        $shop_id = $data['shop_id'];
        $shopInfo = app::get('ome')->model('shop')->db_dump(['shop_id'=>$shop_id]);

        $params = array(
            'region_grade'  =>  1,
        );
        $rs = kernel::single('erpapi_router_request')->set('shop', $shop_id)->branch_getProvince($params);

        if($rs['rsp'] == 'succ' && $rs['data']){
            $insertData = $rs['data'];
            $this->save($insertData);
            $data = app::get('eccommon')->model('platform_regions')->getList('id,outregion_id,outregion_name', ['shop_type'=>$shopInfo['shop_type'], 'region_grade'=>'1']);
            if($data) {
                return [true, ['msg'=>'同步成功', 'data'=>$data]];
            }
        }
        return [false, ['msg'=>'同步失败:'.$rs['msg']]];
    }

    /**
     * syncArea
     * @param mixed $data 数据
     * @return mixed 返回值
     */
    public function syncArea($data) {
        $params = array(
            'outregion_id' => $data['outregion_id'],
            'outregion_name' => $data['outregion_name'],
        );
        
        $res = kernel::single('erpapi_router_request')->set('shop', $data['shop_id'])->branch_getAreasByProvince($params);
        if($res['rsp'] != 'succ') {
            return [false, ['msg' => '获取失败：'. $res['error_msg']]];
        }
        
        if(empty($res['data'])) {
            return [false, ['msg' => '获取失败：没有获取到平台四级地区数据']];
        }
        
        //save
        $insertData = $res['data'];
        $this->save($insertData);
        return [true, ['msg'=>'保存成功']];
    }

    /**
     * 保存
     * @param mixed $data 数据
     * @return mixed 返回操作结果
     */
    public function save($data){

        $regionsObj = app::get('eccommon')->model('platform_regions');
        foreach($data as $v){

            $outregion_id       = $v['outregion_id'];
            $outregion_name     = $v['outregion_name'];
            if (isset($v['outparent_id'])) {
                $where = array('shop_type'=>$v['shop_type'],'region_grade'=>$v['region_grade'],'outregion_name'=>$outregion_name,'outparent_id'=>$v['outparent_id']);
            }else{
                $where = array('shop_type'=>$v['shop_type'],'region_grade'=>$v['region_grade'],'outregion_name'=>$outregion_name);
            }
            $regions = $regionsObj->dump($where,'id,mapping');

            if($regions){
               if($regions['mapping'] == '0'){
                    $local_regions = $this->getLocalRegion($v);

                    if($local_regions){
                        $update_data = array(
                            'mapping'        =>  '1',
                            'local_region_id'=>$local_regions['region_id'],
                            'local_region_name'=>$local_regions['local_name'],
                        );
                        $regionsObj->update($update_data,array('id'=>$regions['id']));
                    }
               }


            }else{
                $local_regions = $this->getLocalRegion($v);
                $insert_data = array(

                    'outregion_id'   =>  $outregion_id,
                    'outregion_name' =>  $outregion_name,
                    'shop_type'      =>  $v['shop_type'],
                    'region_grade'   =>  $v['region_grade']? $v['region_grade'] : 1,
                );

                if ($v['outparent_id']){
                    $insert_data['outparent_id'] = $v['outparent_id'];
                }
                if($local_regions){
                    $insert_data['local_region_id'] = $local_regions['region_id'];
                    $insert_data['local_region_name'] = $local_regions['local_name'];
                    $insert_data['mapping'] = '1';
                }

                if (($v['region_grade']== 2 || $v['region_grade']== 3 ) && $v['outparent_id']){
                    $parentRegion = $this->getRegionByParentId($v['shop_type'],$v['outparent_id']);
                    $insert_data['outparent_name'] = $parentRegion['outregion_name'];
                }

                $regionsObj->save($insert_data);

            }
        }

    }


    /**
     * platformList
     * @return mixed 返回值
     */
    public function platformList(){
        $shopObj = app::get('ome')->model('shop');
        $shop_list = $shopObj->getlist('*',array('shop_type'=>array('luban','kuaishou', 'pinduoduo', 'weimobv')));
        $platform = array();
        if ($shop_list) {
            foreach ($shop_list as $k=>$shop ) {
                if ($shop['node_id'] != '') {
                    $platform[$shop['shop_type']] = $shop;
                }
            }
        }

        return $platform;
    }

    /**
     * 获取LocalRegion
     * @param mixed $data 数据
     * @return mixed 返回结果
     */
    public function getLocalRegion($data){
        $db = kernel::database();
        $outregion_name = $data['outregion_name'];
        $region_grade = $data['region_grade'];
        $regionMdl = app::get('eccommon')->model('regions');

        if($region_grade == 1){

            $regions = $regionMdl->dump(array('local_name|head'=>$outregion_name,'region_grade'=>'1'), 'package,region_id,local_name');
            if(!$regions){
                $formatregion = $this->_formate_province($outregion_name);

                $regions = $regionMdl->dump(array('local_name|head'=>$formatregion,'region_grade'=>'1'), 'package,region_id,local_name');
            }
        }

        if($region_grade == 2){

            $parentRegion = $this->getRegionByParentId($data['shop_type'],$data['outparent_id']);

            $regions = $regionMdl->dump(array('local_name|head'=>$outregion_name,'region_grade'=>'2','p_region_id'=>$parentRegion['local_region_id']),'region_id,p_region_id,local_name');

            if(!$regions){
                $formatregion = $this->_formate_city($outregion_name);

                $regions = $regionMdl->dump(array('local_name|head'=>$formatregion,'region_grade'=>'2','p_region_id'=>$parentRegion['local_region_id']),'region_id,p_region_id,local_name');
            }
        }

        if($region_grade == 3){

            $parentRegion = $this->getRegionByParentId($data['shop_type'],$data['outparent_id']);

            $regions = $regionMdl->dump(array('local_name|head'=>$outregion_name,'region_grade'=>'3','p_region_id'=>$parentRegion['local_region_id']),'region_id,p_region_id,local_name');

            if(!$regions){
                $formatregion = $this->_formate_street($outregion_name);
                $regions = $regionMdl->dump(array('local_name|head'=>$formatregion,'region_grade'=>'3','p_region_id'=>$parentRegion['local_region_id']),'region_id,p_region_id,local_name');
            }

        }


        return $regions;
    }

    /**
     * 获取RegionList
     * @return mixed 返回结果
     */
    public function getRegionList(){
        $regionsObj = app::get('eccommon')->model('regions');
        $regionList = $regionsObj->getlist('region_id,local_name,region_grade,p_region_id',array('region_grade'=>1));

        return $regionList;

    }

    /**
     * synckpl
     * @return mixed 返回值
     */
    public function synckpl(){

        $channelObj = app::get('channel')->model('channel');
        $channel = $channelObj->dump(array('filter_sql' => " ( node_id is not null or node_id!='' ) and node_type='yjdf'"),'channel_id');

        $params = array(
            'parent_id'     =>  '4744',
            'region_grade'  =>  1,
            'channel_id'    =>  $channel['channel_id'],
        );
        $rs = $this->provinceRequest($params);

        $zhixiashi = array('北京','北京市', '上海','上海市', '天津','天津市', '重庆','重庆市');

        if($rs['rsp'] == 'succ' && $rs['data']){

            foreach($rs['data'] as $cv){
                if(!$cv['outregion_id']) continue;
                if(in_array($cv['outregion_name'],$zhixiashi)){//直辖市转换
                    $zhixia_data = array();
                    $outregion_name  = $cv['outregion_name'];
                    if (!preg_match('/(.*?)市/', $outregion_name)){
                        $outregion_name=$outregion_name.'市';
                    }
                    $zhixia_data[] = array(

                        'region_grade'  =>  2,
                        'outregion_id'  =>  $cv['outregion_id'],
                        'outregion_name'=>  $outregion_name,
                        'shop_type'     =>  $cv['shop_type'],
                        'outparent_id'  =>  $cv['outregion_id'],
                    );

                    $this->save($zhixia_data);
                    $cvparams = array(
                        'region_grade'  =>  3,
                        'parent_id'     =>  $cv['outregion_id'],
                        'channel_id'    =>  $channel['channel_id'],
                    );

                    $cvrs = $this->provinceRequest($cvparams);

                }else{


                    $cvparams = array(
                        'region_grade'  =>  2,
                        'parent_id'     =>  $cv['outregion_id'],
                        'channel_id'    =>  $channel['channel_id'],
                    );

                    $cvrs = $this->provinceRequest($cvparams);

                    if($cvrs['rsp'] == 'succ' && $cvrs['data']){
                        foreach($cvrs['data'] as $zv){
                            if(!$zv['outregion_id']) continue;
                            $zvparams = array(
                                'region_grade'  =>  3,
                                'parent_id'     =>  $zv['outregion_id'],
                                'channel_id'    =>  $channel['channel_id'],
                            );
                            $this->provinceRequest($zvparams);

                        }
                    }
                }

            }
        }

    }

    /**
     * provinceRequest
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function provinceRequest($sdf){
        $params = array(
            'parent_id'     =>  $sdf['parent_id'],
            'region_grade'  =>  $sdf['region_grade'],
        );

        $rs = kernel::single('erpapi_router_request')->set('wms',$sdf['channel_id'])->branch_getAreaList($params);

        if($rs['rsp'] == 'succ' && $rs['data']){

            $this->save($rs['data']);
            return $rs;
        }
    }

    function _formate_province($province)
    {

        
        $tranregion =  preg_replace("/省|自治区|壮族自治区|回族自治区|维吾尔自治区|市|特别行政区/","",$province);

        return $tranregion;
    }

    function _formate_city($city)
    {

       $tranregion =  preg_replace("/市|州|自治州|地区|区划|县/","",$city);

        return $tranregion;
    }

    function _formate_street ($street)
    {

        $tranregion =  preg_replace("/市|区|县|镇|乡|街道|旗/","",$street);

        return $tranregion;


    }

    /**
     * 获取RegionByParentId
     * @param mixed $shop_type shop_type
     * @param mixed $outparent_id ID
     * @return mixed 返回结果
     */
    public function getRegionByParentId($shop_type,$outparent_id){
        $regionsObj = app::get('eccommon')->model('platform_regions');
        $regions = $regionsObj->getList('local_region_id,outregion_name',array('shop_type'=>$shop_type,'outregion_id'=>$outparent_id),0,1,'id desc');
        $regions =current($regions);

        return $regions;
    }
    
    /**
     * 获取平台市、区、镇地区库(主要用于获取四级地区镇)
     * 
     * @param $data
     * @return void
     */
    public function syncTown($params)
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
    
        $regionsObj = app::get('eccommon')->model('platform_regions');
        
        //setting
        $result = array('rsp'=>'fail', 'error_msg'=>'');
        $page = intval($params['page_no']);
        $page_size = 1;
        $shop_bn = $params['shop_bn'];
        $offset = $page - 1;
        
        //shop
        $shopInfo = app::get('ome')->model('shop')->db_dump(array('shop_bn'=>$shop_bn));
        if(empty($shopInfo)){
            $result['error_msg'] = '店铺信息不存在!';
            return $result;
        }
        $shop_id = $shopInfo['shop_id'];
        
        //filter
        $filter = array(
            'shop_type' => $shopInfo['shop_type'], //店铺类型
            'region_grade' => 1,
        );
        
        //count
        $total_num = $regionsObj->count($filter);
        
        //获取省级地区
        $regionInfo = $regionsObj->getList('id,outregion_id,outregion_name,local_region_id,local_region_name', $filter, $offset, 1, 'id ASC');
        $regionInfo = $regionInfo[0];
        if(empty($regionInfo)){
            $result['error_msg'] = '平台地区信息不存在!';
            return $result;
        }
        
        //request
        $params = array(
            'outregion_id' => $regionInfo['outregion_id'],
            'outregion_name' => $regionInfo['outregion_name'],
        );
        
        $res = kernel::single('erpapi_router_request')->set('shop', $shop_id)->branch_getAreasByProvince($params);
        if($res['rsp'] != 'succ') {
            $result['error_msg'] = '获取失败：'. $res['error_msg'];
            return $result;
        }
        
        if(empty($res['data'])) {
            $result['error_msg'] = '获取失败：没有获取到平台四级地区数据';
            return $result;
        }
        
        //save
        $insertData = $res['data'];
        $this->save($insertData);
        
        //本次拉取数据条数
        $current_num = 1;
        
        //data
        $next_page = ($page + 1);
        $data = array(
            'rsp' => 'succ',
            'current_page' => $page, //当前页码
            'next_page' => $next_page, //下一页页码
            'page_size' => $page_size, //每次拉取数量
            'all_pages' => ceil($total_num / $page_size), //总页码
            'total_num' => $total_num, //数据总记录数
            'current_num' => $current_num, //本次拉取记录数
            'current_succ_num' => $current_num, //处理成功记录数
            'current_fail_num' => 0, //处理失败记录数
        );
        
        //是否拉取下一页(如果为0则无需拉取)
        if ($data['current_page'] == $data['all_pages']) {
            $data['next_page'] = 0;
        }
        
        return $data;
    }

    /**
     * 获取PlatformRegions
     * @param mixed $params 参数
     * @param mixed $shop_type shop_type
     * @return mixed 返回结果
     */
    public function getPlatformRegions($params, $shop_type)
    {
        $regionsObj = app::get('eccommon')->model('platform_regions');
        $regionLib = kernel::single('ome_region');
        
        //setting
        $data = array();
        
        //get
        $receiver_state = $params['receiver_state'];
        $receiver_city = $params['receiver_city'];
        $receiver_district = $params['receiver_district'];
        $receiver_street = $params['receiver_street'];
        
        //check
        if(empty($receiver_state) || empty($receiver_city) || empty($receiver_district)){
            return $data;
        }
        
        //兼容匹配平台特殊省份及自治区
        $receiver_state_2 = $regionLib->matchingReceiverProvince($receiver_state);
        
        //省
        $filter = array(
            'shop_type' => $shop_type,
            'region_grade' => 1,
            'outregion_name' => array($receiver_state, $receiver_state_2),
        );
        $regionInfo = $regionsObj->dump($filter, 'id,outregion_id,outregion_name');
        if(empty($regionInfo)){
            return $data;
        }
        $data['provinceCode'] = $regionInfo['outregion_id'];
        $data['provinceName'] = $regionInfo['outregion_name'];
        
        //市
        $filter = array(
            'shop_type' => $shop_type,
            'region_grade' => 2,
            'outregion_name' => $receiver_city,
            'outparent_id' => $regionInfo['outregion_id'], //父级地区ID
        );
        $regionInfo = $regionsObj->dump($filter, 'id,outregion_id,outregion_name');
        if(empty($regionInfo)){
            return $data;
        }
        $data['cityCode'] = $regionInfo['outregion_id'];
        $data['cityName'] = $regionInfo['outregion_name'];
        
        //区
        $filter = array(
            'shop_type' => $shop_type,
            'region_grade' => 3,
            'outregion_name' => $receiver_district,
            'outparent_id' => $regionInfo['outregion_id'], //父级地区ID
        );
        $regionInfo = $regionsObj->dump($filter, 'id,outregion_id,outregion_name');
        if(empty($regionInfo)){
            return $data;
        }
        $data['districtCode'] = $regionInfo['outregion_id'];
        $data['districtName'] = $regionInfo['outregion_name'];
        
        //check
        if($receiver_street){
            //镇
            $filter = array(
                'shop_type' => $shop_type,
                'region_grade' => 4,
                'outregion_name' => $receiver_street,
                'outparent_id' => $regionInfo['outregion_id'], //父级地区ID
            );
            $regionInfo = $regionsObj->dump($filter, 'id,outregion_id,outregion_name');
            if($regionInfo){
                $data['townCode'] = $regionInfo['outregion_id'];
                $data['townName'] = $regionInfo['outregion_name'];
            }
        }
        
        return $data;
    }
}
