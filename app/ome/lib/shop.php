<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class ome_shop{

    private static $_shopid_instance = array();
    private static $_nodeid_instance = array();

    /**
     * 通过shop_id获取店铺信息
     * 从数据表获取
     * @access public
     * @param String $shop_id 店铺ID
     * @return Array 店铺信息
     */
    function getRowByShopId($shop_id=''){
        if (empty($shop_id)) return NULL;

        if ( empty(self::$_shopid_instance[$shop_id]) ) {
            $this->getRow(array('shop_id' => $shop_id));
        }

        return self::$_shopid_instance[$shop_id];
    }

    /**
     * 通过node_id获取店铺信息
     * 先从KV获取，为空则从数据表获取
     * @access public
     * @param String $node_id 节点ID
     * @return Array 店铺信息
     */
    function getRowByNodeId($node_id=''){
        if (empty($node_id)) return NULL;

        if ( empty(self::$_nodeid_instance[$node_id]) ) {
            $this->getRow(array('node_id' => $node_id));
        }

        return self::$_nodeid_instance[$node_id];
    }

    /**
     * 根据条件获取店铺信息
     * @access public
     * @param Array $filter 查询条件
     * @param String $col 查询字段
     * @return Array 店铺信息
     */
    function getRow($filter='',$col='*'){
        $shopObj = app::get('ome')->model('shop');
        $shop = $shopObj->getRow($filter,$col);

        if ($shop) {
            self::$_shopid_instance[$shop['shop_id']] = $shop;
            self::$_nodeid_instance[$shop['node_id']] = $shop;
        }
        
        return $shop;
    }

    /**
     * 根据已绑定的店铺
     * @access public
     * @param Array $filter 查询条件
     * @param String $col 查询字段
     * @return Array 店铺列表
     */
    function shop_list($filter='',$col='*'){
        if (!is_array($filter)){
            $filter = array('shop_id'=>$filter);
        }
        $shopObj = app::get('ome')->model('shop');
        $shop_list = $shopObj->getList($col,$filter,0,-1);
        if ($shop_list){
            foreach ($shop_list as $key=>$val){
                if (empty($val['node_id'])){
                    unset($shop_list[$key]);
                }
            }
        }
        return $shop_list;
    }
    
    /**
     * 店铺直连渠道配置
     * @param $svae_data
     * @return array
     * @author db
     * @date 2023-06-20 5:22 下午
     */
    public function savechannel($svae_data)
    {
        $oShop       = app::get('ome')->model('shop');
        $shop_detail = $oShop->db_dump(['shop_id' => $svae_data['shop_id']], 'config,shop_id,node_id');
        if (!$shop_detail) {
            return [false, '店铺不存在'];
        }
        $config = @unserialize($shop_detail['config']);
        // if ($shop_detail['node_id']) {
        //     return [false, '店铺已经绑定'];
        // }
        
        $direct_config = $svae_data['config'];
        switch ($direct_config['adapter']) {
            case 'openapi':
                if (!$direct_config['node_type']) {
                    return [false, '请选择平台'];
                }

                if (!$direct_config['node_id']) {
                    return [false, '节点号必填'];
                }
                
                // 判断参数是否都填了
                $params = kernel::single('ome_auth_config')->getPlatformParam($direct_config['node_type']);
                if ($params) {
                    foreach ($params as $key => $label) {
                        if (!$direct_config[$key]) {
                            return [false, $label . '不能为空'];
                        }
                    }
                }

                if ($oShop->db_dump(['node_id' => $direct_config['node_id'], 'shop_id|noequal' => $shop_detail['shop_id']])) {
                    return [false, 'node_id重复，请重新保存'];
                }


                $svae_data['shop_type'] = $direct_config['node_type'];
                $svae_data['node_type'] = $direct_config['node_type'];
                $svae_data['node_id']   = $direct_config['node_id'];

                $config                 = array_merge($config, $direct_config);
                break;
            case 'matrixonline':
                $config = array_merge($config, $direct_config);
                break;
            default:
                # code...
                break;
        }
        $svae_data['config'] = serialize($config);
        $rt                  = $oShop->update($svae_data, ['shop_id' => $svae_data['shop_id']]);
        if (!$rt) {
            return [false, '绑定失败'];
        }
        return [true, '绑定成功'];
    }
    
    /**
     * 获取指定店铺详细信息(包含店铺配置信息)
     * 
     * @param $shop_id
     * @return array | void
     */
    public function getShopDetailInfo($shop_id)
    {
        $shopMdl = app::get('ome')->model('shop');
        
        //detail
        $shopInfo = $shopMdl->db_dump(['shop_id'=>$shop_id], '*');
        if(empty($shopInfo)){
            return false;
        }
        
        //config
        if($shopInfo['config']){
            $configInfo = @unserialize($shopInfo['config']);
            $shopInfo['config'] = $configInfo;
        }
        
        return $shopInfo;
    }
    
    /**
     * 获取唯品会店铺配置(是否：减掉唯品会购物车预占)
     * 
     * @param $shop_id
     * @return bool
     */
    public function getShopVopCartStockFreeze($shop_id)
    {
        $shopInfo = $this->getShopDetailInfo($shop_id);
        if(empty($shopInfo)){
            return false;
        }
        
        if(empty($shopInfo['config'])){
            return false;
        }
        
        $is_vop_cart_stock_freeze = false;
        if($shopInfo['config']['vop_cart_stock_freeze'] == 'yes'){
            $is_vop_cart_stock_freeze = true;
        }
        
        return $is_vop_cart_stock_freeze;
    }
    
    /**
     * 获取唯品会店铺配置(是否：减掉唯品会购物车预占)
     * 
     * @param $shop_id
     * @return void
     */
    public function getShopVopCooperationNo($shop_id)
    {
        $shopInfo = $this->getShopDetailInfo($shop_id);
        if(empty($shopInfo)){
            return '';
        }
        
        if(empty($shopInfo['config'])){
            return '';
        }
        
        $cooperation_no = '';
        if(isset($shopInfo['config']['cooperation_no'])){
            $cooperation_no = $shopInfo['config']['cooperation_no'];
        }
        
        return $cooperation_no;
    }

    public function getcols(){

        $customcolsMdl = app::get('desktop')->model('customcols');

        $customcolslist = $customcolsMdl->getlist('col_name,col_key',array('tbl_name'=>'sdb_ome_shop'));

        if($customcolslist){

            
            return $customcolslist;
        }
    }

    /**
     * 获取PropsList
     * @param mixed $shop_id ID
     * @return mixed 返回结果
     */
    public function getPropsList($shop_id){
        $propsMdl = app::get('ome')->model('shop_props');

        $propsList = $propsMdl->getlist('*', ['shop_id' => $shop_id]);

        $arr_props = array();
        foreach($propsList as $v){

            $arr_props[$v['props_col']] = $v['props_value'];

        }

        return $arr_props;
    }
}
