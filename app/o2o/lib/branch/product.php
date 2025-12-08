<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 门店基础物料类
 * @author wangjianjun@shopex.cn
 * @version 1.0
 */
class o2o_branch_product{
    
    //线上在售库存
    const __STORE = 1;
    
    //共享库存
    const __SHARE_STORE = 2;

    /**
     * 查询条件转换
     * 
     * @param Array $params 查询条件参数
     * @return String
     */

    private function get_filter($params=array()){
        $sql_filter = " where mbm.visibled=1";
        if(!empty($params)){
            switch ($params["search_key"]){
                case "material_name":
                    $sql_filter = $sql_filter." and mbm.material_name like '".$params['search_value']."%'";
                    break;
                case "material_bn":
                    $sql_filter = $sql_filter." and mbm.material_bn like '".$params['search_value']."%'";
                    break;
                case "brand_name":
                    $sql_join = " left join sdb_material_basic_material_ext as mbme on mbm.bm_id=mbme.bm_id";
                    $sql_filter = $sql_join.$sql_filter." and mbme.brand_id=".intval($params['search_value']);
                    break;
                case "type_name":
                    $sql_join = " left join sdb_material_basic_material_ext as mbme on mbm.bm_id=mbme.bm_id";
                    $sql_filter = $sql_join.$sql_filter." and mbme.cat_id=".intval($params['search_value']);
                    break;
            }
        }
        return $sql_filter;
    } 
    
    /**
     * 根据条件返回基础物料数据
     * 
     * @param Int $offset 下标位置
     * @param Int $limit 限制数
     * @param Array $params 查询条件参数
     * @return Array
     */
    public function get_product_info($offset="",$limit="",$params=array()){
        
        $mdlMaterialBasic = app::get('material')->model('basic_material');
        $mdlMaterialBasicExt = app::get('material')->model('basic_material_ext');
        $mdlOmeBrand = app::get('ome')->model('brand');
        $mdlOmeGoodsType = app::get('ome')->model('goods_type');
        
        $sql = "SELECT mbm.bm_id,mbm.material_name,mbm.material_bn FROM sdb_material_basic_material as mbm";
        $sql_filter = $this->get_filter($params);
        if($limit){
            $sql = $sql.$sql_filter." limit ". $offset .",". $limit;
        }else{
            $sql = $sql.$sql_filter;
        }
        
        $rs_material = $mdlMaterialBasic->db->select($sql);
        
        if(empty($rs_material)){
            return array();
        }
        
        $bm_ids = array();
        foreach ($rs_material as $var_material){
            $bm_ids[] = $var_material["bm_id"];
        }
        
        $rs_material_ext = $mdlMaterialBasicExt->getList("bm_id,specifications,brand_id,cat_id",array("bm_id|in"=>$bm_ids));
        $brand_ids = array();
        $cat_ids = array();
        foreach ($rs_material_ext as $var_material_ext){
            if($var_material_ext["brand_id"] && !in_array($var_material_ext["brand_id"],$brand_ids)){
                $brand_ids[] = $var_material_ext["brand_id"];
            }
            if($var_material_ext["cat_id"] && !in_array($var_material_ext["cat_id"],$cat_ids)){
                $cat_ids[] = $var_material_ext["cat_id"];
            }
        }
        
        //获取品牌
        if($brand_ids){
            $rs_brand = $mdlOmeBrand->getList("brand_id,brand_name",array("brand_id|in"=>$brand_ids));
            $rl_brand_id_name = array();
            foreach ($rs_brand as $var_brand){
                $rl_brand_id_name[$var_brand["brand_id"]] = $var_brand["brand_name"];
            }
        }
        
        //获取类型
        if($cat_ids){
            $rs_cat = $mdlOmeGoodsType->getList("type_id,name",array("type_id|in"=>$cat_ids));
            $rl_type_id_name = array();
            foreach ($rs_cat as $var_cat){
                $rl_type_id_name[$var_cat["type_id"]] = $var_cat["name"];
            }
        }
        
        //获取bm_id和规格、品牌、类型
        $rl_bm_id_info = array();
        foreach ($rs_material_ext as $item_material_ext){
            $rl_bm_id_info[$item_material_ext["bm_id"]] = array(
                "specifications" => $item_material_ext["specifications"],
                "brand_name" => $rl_brand_id_name[$item_material_ext["brand_id"]],
                "type_name" => $rl_type_id_name[$item_material_ext["cat_id"]],
            );
        }
        
        foreach ($rs_material as &$item_material){
            $item_material["specifications"] = "-";
            $item_material["brand_name"] = "-";
            $item_material["type_name"] = "-";
            if($rl_bm_id_info[$item_material["bm_id"]]["specifications"]){
                $item_material["specifications"] = $rl_bm_id_info[$item_material["bm_id"]]["specifications"];
            }
            if($rl_bm_id_info[$item_material["bm_id"]]["brand_name"]){
                $item_material["brand_name"] = $rl_bm_id_info[$item_material["bm_id"]]["brand_name"];
            }
            if($rl_bm_id_info[$item_material["bm_id"]]["type_name"]){
                $item_material["type_name"] = $rl_bm_id_info[$item_material["bm_id"]]["type_name"];
            }
        }
        unset($item_material);
        
        return $rs_material;
    }

    //获取自定义搜素选项
    /**
     * 获取_search_options
     * @return mixed 返回结果
     */
    public function get_search_options(){
        $options = array(
            'material_name'=>'物料名称',
            'material_bn'=>'物料编码',
            'brand_name'=>'品牌',
            'type_name'=>'分类',
        );
        return $options;
    }
    
    //获取自定义搜素选项
    /**
     * 获取_search_list
     * @return mixed 返回结果
     */
    public function get_search_list(){
        //品牌
        $brandObj = app::get('ome')->model('brand');
        $brand_tmp =$brandObj->getList('brand_name,brand_id');
        $brand = array();
        foreach($brand_tmp as $branddata){
            $brand[$branddata['brand_id']] = $branddata['brand_name'];
        }
        
        //类型
        $typeObj = app::get('ome')->model('goods_type');
        $type_tmp = $typeObj->getList('type_id,name');
        $type = array();
        foreach($type_tmp as $typedata){
            $type[$typedata['type_id']] = $typedata['name'];
        }
        
        $list = array(
                'brand_name'=>$brand,
                'type_name'=>$type,
        );
        return $list;
    }
    
    //计算记录条数
    function do_count($params=array()){
        $mdlMaterialBasic = app::get('material')->model('basic_material');
        $sql = "SELECT count(mbm.bm_id) as total_count FROM sdb_material_basic_material as mbm";
        $sql_filter = $this->get_filter($params);
        $sql = $sql.$sql_filter;
        $count = $mdlMaterialBasic->db->select($sql);
        return $count[0]["total_count"];
    }
    
    /**
     * 识别单个基础物料在具体门店下是否管控库存
     * 
     * @param Int $branch_id 门店仓ID
     * @param Int $bm_id 基础物料ID
     * @return Boolean
     */
    function isCtrlBmStore($branch_id,$bm_id){
        $mdlO2oBranchProduct = app::get('o2o')->model('branch_product');
        $rs_branch_product = $mdlO2oBranchProduct->dump(array("branch_id"=>$branch_id,"bm_id"=>$bm_id),"is_ctrl_store");
        $return_value = true;
        if ($rs_branch_product["is_ctrl_store"] == 1 ){
            $return_value = true;
        }
        return $return_value;
    }
    
    /**
     * 识别一批基础物料在具体门店下是否管控库存
     * 
     * @param Int $branch_id 门店仓ID
     * @param Int $bm_ids 基础物料ID数组
     * @return Array
     */
    function isCtrlBmsStore($branch_id,$bm_ids){
        if(!is_array($bm_ids)){
            $bm_ids = array($bm_ids);
        }
        $mdlO2oBranchProduct = app::get('o2o')->model('branch_product');
        $rs_branch_product = $mdlO2oBranchProduct->getList("bm_id,is_ctrl_store",array("branch_id"=>$branch_id,"bm_id|in"=>$bm_ids));
        $rl_bm_id_result = array();
        foreach ($rs_branch_product as $var_b_p){
            $is_ctrl_store_bool = false;
            if($var_b_p["is_ctrl_store"] == 1){
                $is_ctrl_store_bool = true;
            }
            $rl_bm_id_result[$var_b_p["bm_id"]] = $is_ctrl_store_bool;
        }
        return $rl_bm_id_result;
    }
    
    /**
     * 变更门店确定性库存
     * 
     * @param Int $branch_id 门店仓ID
     * @param Int $bm_id 基础物料ID
     * @param Int $num 数量
     * @param Int $operator 处理方式 +增加 -减少 =更新
     * @return Boolean
     */
    function changeStoreConfirmStore($branch_id,$bm_id,$num,$operator){
        $mdlO2oProductStore = app::get('o2o')->model('product_store');
        $where = '';
        switch($operator){
            case "+":
                $store = "store=store+".$num;
                break;
            case "-":
                $store = "store=IF((CAST(store AS SIGNED)-$num)>0,store-$num,0) ";
                $where .= ' AND store>=' . $num;
                break;
            case "=":
                $store = "store=".$num;
                break;
        }

        $sql = "UPDATE ".DB_PREFIX."o2o_product_store SET ".$store.",last_modified=".time()." WHERE bm_id=".$bm_id." AND branch_id=".$branch_id.$where;
        if($mdlO2oProductStore->db->exec($sql)){
            $rs = $mdlO2oProductStore->db->affect_row();
            if(is_numeric($rs) && $rs > 0){
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    /**
     * 变更门店确定性预占
     * 
     * @param Int $branch_id 门店仓ID
     * @param Int $bm_id 基础物料ID
     * @param Int $num 数量
     * @param Int $operator 处理方式 +增加 -减少
     * @return Boolean
     */
    function changeStoreConfirmFreeze($branch_id,$bm_id,$num,$operator){
        $mdlO2oProductStore = app::get('o2o')->model('product_store');
        switch($operator){
            case "+":
                $store_freeze = "store_freeze=store_freeze+".$num;
                break;
            case "-":
                $store_freeze = "store_freeze=IF((CAST(store_freeze AS SIGNED)-$num)>0,store_freeze-$num,0) ";
                break;
        }

        $sql = "UPDATE ".DB_PREFIX."o2o_product_store SET ".$store_freeze.",last_modified=".time()." WHERE bm_id=".$bm_id." AND branch_id=".$branch_id;
        if($mdlO2oProductStore->db->exec($sql)){
            $rs = $mdlO2oProductStore->db->affect_row();
            if(is_numeric($rs) && $rs > 0){
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }
    
    //获取门店仓branch_id和order_id的订单items层（基础物料）是否有供货关系 以及压进库存等信息
    function getO2oBrPrInOrIt($order_id,$branch_id){
        $mdlOmeOrder = app::get('ome')->model('order_items');
        $mdlO2oBranchProduct = app::get('o2o')->model('branch_product');
        $mdlO2oProductStore = app::get('o2o')->model('product_store');
        //获取所有和门店仓相关的商品
        $rs_branch_product = $mdlO2oBranchProduct->getList("bm_id,is_ctrl_store,status",array("branch_id"=>$branch_id));
        if(!empty($rs_branch_product)){
            //有关联关系的
            $rl_bm_id_info = array();
            foreach($rs_branch_product as $var_b_p){
                $rl_bm_id_info[$var_b_p["bm_id"]] = array(
                        "o2o_is_ctrl_store" => $var_b_p["is_ctrl_store"],
                        "o2o_sale_status" => $var_b_p["status"]
                );
            }
        }
        //获取所有和门店仓相关的库存信息
        $rs_product_store = $mdlO2oProductStore->getList("bm_id,store,share_store",array("branch_id"=>$branch_id));
        if(!empty($rs_product_store)){
            //有库存信息的
            $rl_bm_id_store = array();
            foreach($rs_product_store as $var_p_s){
                $rl_bm_id_store[$var_p_s["bm_id"]] = array(
                        "o2o_store" => $var_p_s["store"],
                        "o2o_share_store" => $var_p_s["share_store"]
                );
            }
        }
        //组$rl_bm_id_info 压进o2o_store o2o_share_store
        foreach($rl_bm_id_info as $k => &$v_i){
            $v_i["o2o_store"] = $rl_bm_id_store[$k]["o2o_store"];
            $v_i["o2o_share_store"] = $rl_bm_id_store[$k]["o2o_share_store"];
        }
        unset($v_i);
        //获取order_items
        $rs_items = $mdlOmeOrder->getList("*",array("order_id"=>$order_id));
        foreach($rs_items as &$var_item){
            if($rl_bm_id_info[$var_item["product_id"]]){
                $var_item["o2o_product_store"] = true;
                $var_item["o2o_is_ctrl_store"] = $rl_bm_id_info[$var_item["product_id"]]["o2o_is_ctrl_store"];
                $var_item["o2o_sale_status"] = $rl_bm_id_info[$var_item["product_id"]]["o2o_sale_status"];
                $var_item["o2o_store"] = $rl_bm_id_info[$var_item["product_id"]]["o2o_store"];
                $var_item["o2o_share_store"] = $rl_bm_id_info[$var_item["product_id"]]["o2o_share_store"];
            }else{
                $var_item["o2o_product_store"] = false;
            }
        }
        unset($var_item);
        return $rs_items;
    }

    /*
     * [o2o全渠道]基础物料供货库存
    *
    * @param int $order_id
    * @param int $branch_id 单个仓库ID
    * @return array
    */
    function getO2oBranchStore($bm_id)
    {
        $branchProductObj   = app::get('o2o')->model('branch_product');
        $productStoreObj    = app::get('o2o')->model('product_store');
        $storeObj           = app::get('o2o')->model('store');
        $basicMStockFreezeLib  = kernel::single('material_basic_material_stock_freeze');
        
        //基础物料对应所有供货关系
        $bmList    = $branchProductObj->getList('bm_id, branch_id, is_ctrl_store, status AS sale_status', array('bm_id'=>$bm_id));
        if(empty($bmList))
        {
            return array();
        }
        
        $branch_store    = array();
        foreach ($bmList as $key => $val)
        {
            $is_relation      = true;#存在供货关系
            $is_ctrl_store    = ($val['is_ctrl_store'] == 1 ? true : false);#是否管控库存
            
            //停售状态"隐藏"供货关系
            if($val['sale_status'] != 1)
            {
                $is_relation    = false;
            }
            
            //门店是否库存管控
            if($is_ctrl_store)
            {
                $storeInfo    = $storeObj->db->selectrow("SELECT is_ctrl_store FROM sdb_o2o_store WHERE branch_id='". $val['branch_id'] ."'");
                if($storeInfo['is_ctrl_store'] == 2)
                {
                    $is_ctrl_store    = false;
                }
            }
            
            $tmp_bm_id        = $val['bm_id'];
            $tmp_branch_id    = $val['branch_id'];
            
            $branch_store[$tmp_bm_id][$tmp_branch_id]    = array('is_relation'=>$is_relation, 'is_ctrl_store'=>$is_ctrl_store);
        }
        
        //基础物料对应所有门店库存
        $storeList    = $productStoreObj->getList('bm_id, branch_id, store, store_freeze, share_store, share_freeze', array('bm_id'=>$bm_id));
        if($storeList)
        {
            foreach ($storeList as $key => $val)
            {
                $tmp_bm_id        = $val['bm_id'];
                $tmp_branch_id    = $val['branch_id'];
                
                //根据门店仓库ID、基础物料ID获取该物料仓库级的预占
                $val['store_freeze']    = $basicMStockFreezeLib->getO2oBranchFreeze($tmp_bm_id, $tmp_branch_id);
                
                $store        = $val['store'] - $val['store_freeze'];
                $share_store  = $val['share_store'] - $val['share_freeze'];
                
                $branch_store[$tmp_bm_id][$tmp_branch_id]['store']          = ($store > 0 ? $store : 0);
                $branch_store[$tmp_bm_id][$tmp_branch_id]['share_store']    = ($share_store > 0 ? $share_store : 0);
            }
        }
        
        return $branch_store[$bm_id];
    }
    
    /**
     * [全渠道]订单加载门店仓库库存信息
     * 
     * @param Array $combineOrders
     * @return Array
     */
    function getItemBnBranchStore($combineOrders)
    {
        foreach ($combineOrders as $order_id => $orderItem)
        {
            foreach ($orderItem['items'] as $goods_type => $goodsItem)
            {
                foreach ($goodsItem as $obj_id => $objItem)
                {
                    $item_store_i = 1;
                    
                    foreach ($objItem['order_items'] as $item_id => $itemRow)
                    {
                        $branch_store    = $this->getO2oBranchStore($itemRow['product_id']);
                        
                        if($branch_store)
                        {
                            foreach ($branch_store as $branch_id => $branchItem)
                            {
                                $combineOrders[$order_id]['items'][$goods_type][$obj_id]['order_items'][$item_id]['branch_store'][$branch_id]  = $branchItem;
                                
                                //重新计算PKG捆绑商品门店仓库库存数量
                                if($goods_type == 'pkg' || $goods_type == 'lkb')
                                {
                                    //如果不存在供货关系,则跳出
                                    if(empty($branchItem['is_relation']))
                                    {
                                        break;
                                    }
                                    
                                    //如果不管控库存,则跳过
                                    if(empty($branchItem['is_ctrl_store']))
                                    {
                                        continue;
                                    }
                                    
                                    $branchItem['store']    = intval($branchItem['store']);
                                    $branchItem['store']    = floor($branchItem['store'] / $itemRow['nums']);
                                    
                                    if($item_store_i > 1)
                                    {
                                        $branchItem['store'] = min($branchItem['store'], $combineOrders[$order_id]['items'][$goods_type][$obj_id]['branch_store'][$branch_id]['store']);
                                    }
                                    
                                    $combineOrders[$order_id]['items'][$goods_type][$obj_id]['branch_store'][$branch_id] = $branchItem;
                                    
                                    $item_store_i++;
                                }
                            }
                        }
                    }
                }
            }
        }
        
        return $combineOrders;
    }

}