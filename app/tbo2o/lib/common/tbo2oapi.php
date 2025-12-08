<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class tbo2o_common_tbo2oapi{
    
    /*
     * 门店新增接口参数
     * 参数 
     * $tbo2o_shop淘宝o2o全渠道配置
     * $store_id tbo2o_store表local_store_id
     * $rt_cat 门店类目接口返回信息
     */

    public function getStoreCreateParam($store_id){
        //获取主店铺信息
        $tbo2o_shop = kernel::single('tbo2o_common')->getTbo2oShopInfo();
        //获取门店相关信息
        $mdlTb2oStore = app::get('tbo2o')->model('store');
        $tbo2o_store = $mdlTb2oStore->dump(array("local_store_id"=>$store_id));
        //营业时间
        $arr_time = explode("-",$tbo2o_store["open_hours"]);
        $startTime = trim($arr_time[0]);
        $endTime = trim($arr_time[1]);
        //门店区域
        $arr_area = explode(":",$tbo2o_store["area"]);
        $arr_area_text = explode("/",$arr_area[1]);
        $province = $arr_area_text[0];
        $city = $arr_area_text[1];
        $area = $city;
        if($arr_area_text[2]){
            $area = $arr_area_text[2];
        }
        //获取绑定淘宝店信息
        $tb_shop_info = $this->getTbShopInfoByShopId($tbo2o_shop["shop_id"]);
        //全渠道格式化省名/市名
        $arr_area_format = $this->_formate_province_city($province,$city);
        return array(
            "storeCode" => $tbo2o_store["store_bn"].$tb_shop_info["tb_shop_id"],
            "companyName" => $tbo2o_shop["company_name"],
            "storeName" => $tbo2o_store["store_name"],
            "storeType" => strtoupper($tbo2o_store["store_type"]),
            "mainCategory" => $tbo2o_store["cat_id"],
            "startTime" => $startTime,
            "endTime" => $endTime,
            "address" => array(
                "region" => "",
                "province" => $arr_area_format["province"],
                "city" => $arr_area_format["city"],
                "area" => $area,
                "town" => "",
                "detailAddress" => $tbo2o_store["address"],
                "countryCode" => "",
            ),
            "storeStatus" => strtoupper($tbo2o_store["status"]),
            "storeDescription" => "",
            "storeKeeper" => array(
                "name" => $tbo2o_store["contacter"],
                "tel" => $tbo2o_store["tel"],
                "mobile" => $tbo2o_store["mobile"],
                "fax" => $tbo2o_store["fax"],
                "zipCode" => $tbo2o_store["zip"],
            ),
            "remark" => "",
        );
    }
    
    /*
     * 门店更新接口参数
     * 参数
     * $tbo2o_shop淘宝o2o全渠道配置
     * $store_id tbo2o_store表local_store_id
     * $rt_cat 门店类目接口返回信息
     */

    public function getStoreUpdateParam($store_id){
        //获取主店铺信息
        $tbo2o_shop = kernel::single('tbo2o_common')->getTbo2oShopInfo();
        //获取门店相关信息
        $mdlTb2oStore = app::get('tbo2o')->model('store');
        $tbo2o_store = $mdlTb2oStore->dump(array("local_store_id"=>$store_id));
        //营业时间
        $arr_time = explode("-",$tbo2o_store["open_hours"]);
        $startTime = trim($arr_time[0]);
        $endTime = trim($arr_time[1]);
        //门店区域
        $arr_area = explode(":",$tbo2o_store["area"]);
        $arr_area_text = explode("/",$arr_area[1]);
        $province = $arr_area_text[0];
        $city = $arr_area_text[1];
        $area = $city;
        if($arr_area_text[2]){
            $area = $arr_area_text[2];
        }
        //获取绑定淘宝店信息
        $tb_shop_info = $this->getTbShopInfoByShopId($tbo2o_shop["shop_id"]);
        //全渠道格式化省名/市名
        $arr_area_format = $this->_formate_province_city($province,$city);
        return array(
                "storeId" => $tbo2o_store["outer_store_id"],
                "storeCode" => $tbo2o_store["store_bn"],
                "shopId" => $tb_shop_info["tb_shop_id"],
                "companyName" => $tbo2o_shop["company_name"],
                "storeName" => $tbo2o_store["store_name"],
                "storeType" => strtoupper($tbo2o_store["store_type"]),
                "mainCategory" => $tbo2o_store["cat_id"],
                "startTime" => $startTime,
                "endTime" => $endTime,
                "address" => array(
                    "region" => "",
                    "province" => $arr_area_format["province"],
                    "city" => $arr_area_format["city"],
                    "area" => $area,
                    "town" => "",
                    "detailAddress" => $tbo2o_store["address"],
                    "countryCode" => "",
                ),
                "storeStatus" => strtoupper($tbo2o_store["status"]),
                "storeDescription" => "",
                "storeKeeper" => array(
                    "name" => $tbo2o_store["contacter"],
                    "tel" => $tbo2o_store["tel"],
                    "mobile" => $tbo2o_store["mobile"],
                    "fax" => $tbo2o_store["fax"],
                    "zipCode" => $tbo2o_store["zip"],
                ),
                "remark" => "",
        );
    }
    
    /*
     * 商品关联绑定接口
     */

    public function getItemstoreBandingParam($type="ADD",$itemId,$storeIds){
        $storeIds_arr = array();
        foreach ($storeIds as $v_s){
            $storeIds_arr[] = array("storeId"=>$v_s);
        }
        return array(
            "actionType" => $type,
            "itemId" => $itemId,
            "storeIds" => $storeIds_arr,
            "remark" => ""
        );
    }
    
    //全渠道接口格式化省名
    /**
     * _formate_province_city
     * @param mixed $province province
     * @param mixed $city city
     * @return mixed 返回值
     */
    public function _formate_province_city($province,$city){
        $return_arr = array(
                "province" => $province,
                "city" => $city
        );
        $zhixiashi = array('北京','上海','天津','重庆');
        $zizhiqu = array('内蒙古','宁夏','新疆','西藏','广西','香港','澳门');
        if (in_array($province,$zhixiashi)) {
            $return_arr = array(
                "province" => $province.'市',
                "city" => $province.'市市辖区'
            );
        }elseif(in_array($province,$zizhiqu)){
            $province_f = $province.'自治区'; //内蒙古 西藏
            if($province == "香港" || $province == "澳门"){
                $province_f = $province.'特别行政区';
            }
            if($province == "宁夏"){
                $province_f = $province.'回族自治区';
            }
            if($province == "新疆"){
                $province_f = $province.'维吾尔自治区';
            }
            if($province == "广西"){
                $province_f = $province.'壮族自治区';
            }
            $return_arr = array(
                    "province" => $province_f,
                    "city" => $city
            );
        }elseif(!preg_match('/(.*?)省/',$province)){
             $return_arr = array(
                    "province" => $province.'省',
                    "city" => $city
            );
        }
        return $return_arr;
    }
    
    /**
     * _formate_tb_province_city
     * @param mixed $tb_address tb_address
     * @return mixed 返回值
     */
    public function _formate_tb_province_city($tb_address){
        $return_str = "";
        $tb_zhixiashi_province = array('北京市','上海市','天津市','重庆市');
        $tb_zizhiqu_province = array('内蒙古自治区','西藏自治区','宁夏回族自治区','新疆维吾尔自治区','广西壮族自治区','香港特别行政区','澳门特别行政区');
        if(in_array($tb_address["province"],$tb_zhixiashi_province)){
            //去掉最后一个字 市
            $local_province = substr($tb_address["province"],0,-3);
            $local_city = $tb_address["province"];
        }elseif (in_array($tb_address["province"],$tb_zizhiqu_province)){
            switch ($tb_address["province"]){
                case "内蒙古自治区":
                case "西藏自治区":
                    $local_province = substr($tb_address["province"],0,-9);
                    break;
                case "宁夏回族自治区":
                case "广西壮族自治区":
                case "香港特别行政区":
                case "澳门特别行政区":
                    $local_province = substr($tb_address["province"],0,-15);
                    break;
                case "新疆维吾尔自治区":
                    $local_province = substr($tb_address["province"],0,-18);
                    break;
            }
            $local_city = $tb_address["city"];
        }else{
            $prov = array();
            preg_match('/(.*?)省/',$tb_address["province"],$prov);
            if($prov){
                //去掉最后的省字
                $local_province = substr($tb_address["province"],0,-3);
            }
            $local_city = $tb_address["city"];
        }
        //组area字段字符串
        if($local_province){
            $mdlEccommonRegions = app::get('eccommon')->model('regions');
            $rs_province = $mdlEccommonRegions->dump(array("region_grade"=>1,"local_name"=>$local_province),"region_id");
            if($rs_province){
                $rs_city = $mdlEccommonRegions->dump(array("region_grade"=>2,"p_region_id"=>$rs_province["region_id"],"local_name"=>$local_city),"region_id,haschild");
                if($rs_city){
                    if($rs_city["haschild"] == "1"){
                        //本地目前只支持3级地区
                        $rs_area = $mdlEccommonRegions->dump(array("region_grade"=>3,"p_region_id"=>$rs_city["region_id"],"local_name"=>$tb_address["area"]),"region_id");
                        if($rs_area){
                            $return_str = "mainland:".$local_province."/".$local_city."/".$tb_address["area"].":".$rs_area["region_id"];
                        }
                    }else{
                        $return_str = "mainland:".$local_province."/".$local_city.":".$rs_city["region_id"];
                    }
                }
            }
        }
        //最终返回
        return $return_str;
    }
    
    //根据淘宝的address字段获取area字段 mainland:字符串
    /**
     * 获取AreaFromTbAddress
     * @param mixed $tb_address tb_address
     * @param mixed $local_area local_area
     * @return mixed 返回结果
     */
    public function getAreaFromTbAddress($tb_address,$local_area){
        //本地area字段
        $arr_area = explode(":",$local_area);
        $arr_area_text = explode("/",$arr_area[1]);
        $province = $arr_area_text[0];
        $city = $arr_area_text[1];
        $area = $city;
        if($arr_area_text[2]){
            $area = $arr_area_text[2];
        }
        //全渠道格式化省名/市名
        $arr_area_format = $this->_formate_province_city($province,$city);
        if($tb_address["province"] == $arr_area_format["province"] && $tb_address["city"] == $arr_area_format["city"] && $tb_address["area"] == $area){
            //淘宝上没有修改地区
            return $local_area;
        }
        //有在淘宝上修改地区的
        return $this->_formate_tb_province_city($tb_address);
    }
    
    //获取淘宝店铺ID
    /**
     * 获取TbShopInfoByShopId
     * @param mixed $shop_id ID
     * @return mixed 返回结果
     */
    public function getTbShopInfoByShopId($shop_id){
        $mdlOmeShop = app::get('ome')->model('shop');
        $rs_shop = $mdlOmeShop->dump(array("shop_id"=>$shop_id),"addon");
        return $rs_shop["addon"];
    }
    
    //盘点库存 获取阿里全渠道requset接口参数
    /**
     * 获取InventoryRequestParam
     * @param mixed $product_store product_store
     * @param mixed $branch_id ID
     * @param mixed $type type
     * @return mixed 返回结果
     */
    public function getInventoryRequestParam($product_store,$branch_id,$type="initial"){
        //获取主店铺信息
        $tbo2o_shop = kernel::single('tbo2o_common')->getTbo2oShopInfo();
        //获取门店信息
        $mdlO2oStore = app::get('o2o')->model('store');
        $rs_o2o_store = $mdlO2oStore->dump(array("branch_id"=>$branch_id),"store_id");
        //获取淘宝门店ID
        $mdlTbo2oStore = app::get('tbo2o')->model('store');
        $rs_tb_store = $mdlTbo2oStore->dump(array("store_id"=>$rs_o2o_store["store_id"]),"outer_store_id");
        //获取淘宝主店铺绑定信息
        $tb_shop_info = $this->getTbShopInfoByShopId($tbo2o_shop["shop_id"]);
        $return_arr = array(
            "userId" => $tb_shop_info["tb_user_id"],
            "operationTime" => date("Y-m-d H:i:s",time()),
            "stores" => array(
                "store" => array(
                    "warehouseType" => "STORE",
                    "warehouseId" => $rs_tb_store["outer_store_id"],
                ),
            ),
        );
        $bm_ids = array();
        foreach ($product_store as $var_p){
            $bm_ids[] = $var_p["bm_id"];
        }
        //获取基础物料(后端商品)的bn
        $mdlBasicMaterial = app::get('material')->model('basic_material');
        $rs_basic_ma = $mdlBasicMaterial->getList("bm_id,material_bn",array("bm_id|in"=>$bm_ids));
        $bm_bns = array();
        $rl_bm_id_bn = array();
        foreach ($rs_basic_ma as $var_b_m){
            $bm_bns[] = $var_b_m["material_bn"];
            $rl_bm_id_bn[$var_b_m["bm_id"]] = $var_b_m["material_bn"];
        }
        //获取shop_skus商品数据
        $mdlTbo2oShopSkus = app::get('tbo2o')->model('shop_skus');
        $rs_tb_shop_skus = $mdlTbo2oShopSkus->getList("shop_sku_id,shop_iid,shop_product_bn,product_bn",array("product_bn|in"=>$bm_bns));
        $rl_bn_skus = array();
        foreach ($rs_tb_shop_skus as $var_s_s){
            $rl_bn_skus[$var_s_s["product_bn"]] = $var_s_s;
        }
        //获取bm_id和sku之间的关系
        $rl_bm_id_sku = array();
        foreach ($rl_bm_id_bn as $key_b_i_b => $var_b_i_b){
            $rl_bm_id_sku[$key_b_i_b] = $rl_bn_skus[$var_b_i_b];
        }
        //组storeInventories明细参数
        $time_micro = utils::microtime();
        $time_micro = str_replace(".", "", $time_micro);
        $storeInventories_arr = array(); 
        foreach ($product_store as $var_p_s){
            $temp_arr = array(
                    "itemId" => $rl_bm_id_sku[$var_p_s["bm_id"]]["shop_iid"],
                    "outerId" => $rl_bm_id_sku[$var_p_s["bm_id"]]["shop_product_bn"],
                    "skuId" => $rl_bm_id_sku[$var_p_s["bm_id"]]["shop_sku_id"],
            );
            if($type == "initial"){
                if($var_p_s["store"] > 0){
                    $temp_arr["billNum"] = $time_micro++;
                    $temp_arr["inventoryType"] = "CERTAINTY";
                    $temp_arr["quantity"] = $var_p_s["store"];
                    $storeInventories_arr[]["storeInventory"] = $temp_arr;
                }
                if($var_p_s["share_store"] > 0){
                    $temp_arr["billNum"] = $time_micro++;
                    $temp_arr["inventoryType"] = "UNCERTAINTY";
                    $temp_arr["quantity"] = $var_p_s["share_store"];
                    $storeInventories_arr[]["storeInventory"] = $temp_arr;
                }
            }else{
                //update
                if($var_p_s["short_over"] != "0" ){
                    $temp_arr["billNum"] = $time_micro++;
                    $temp_arr["billType"] = "BALANCE";
                    $temp_arr["inventoryType"] = "CERTAINTY";
                    $temp_arr["quantity"] = $var_p_s["short_over"];
                    $temp_arr["finalQuantity"] = $var_p_s["store"];
                    $storeInventories_arr[]["storeInventory"] = $temp_arr;
                }
                if($var_p_s["share_short_over"] != "0" ){
                    $temp_arr["billNum"] = $time_micro++;
                    $temp_arr["billType"] = "BALANCE";
                    $temp_arr["inventoryType"] = "UNCERTAINTY";
                    $temp_arr["quantity"] = $var_p_s["share_short_over"];
                    $temp_arr["finalQuantity"] = $var_p_s["share_store"];
                    $storeInventories_arr[]["storeInventory"] = $temp_arr;
                }
            }
        }
        $return_arr["stores"]["store"]["storeInventories"] = $storeInventories_arr;
        return $return_arr;
    }
    
    //阿里全渠道回写电商仓库存 获取requset接口参数
    function getO2oInvInitialRequestParam($omnichannel_stock,$tbo2o_shop){
        //获取淘宝主店铺绑定信息
        $tb_shop_info = $this->getTbShopInfoByShopId($tbo2o_shop["shop_id"]);
        $return_arr = array(
                "userId" => $tb_shop_info["tb_user_id"],
                "operationTime" => date("Y-m-d H:i:s",time()),
                "stores" => array(
                        "store" => array(
                                "warehouseType" => "WAREHOUSE",
                                "warehouseId" => $tbo2o_shop["branch_bn"],
                        ),
                ),
        );
        //获取销售物料bn的sku_id和item_id
        $sm_bns = array();
        foreach ($omnichannel_stock as $var_o){
            $sm_bns[] = $var_o["bn"];
        }
        $mdlTbo2oShopSkus = app::get('tbo2o')->model('shop_skus');
        $rs_skus = $mdlTbo2oShopSkus->getList("shop_sku_id,shop_iid,shop_product_bn",array("shop_product_bn|in"=>$sm_bns));
        $rl_bn_ids = array();
        foreach ($rs_skus as $var_s){
            $rl_bn_ids[$var_s["shop_product_bn"]] = array(
                "shop_sku_id" => $var_s["shop_sku_id"],
                "shop_iid" => $var_s["shop_iid"],
                "shop_product_bn" => $var_s["shop_product_bn"],
            );
        }
        //组storeInventories明细参数
        $time_micro = utils::microtime();
        $time_micro = str_replace(".", "", $time_micro);
        $storeInventories_arr = array();
        foreach ($omnichannel_stock as $var_o_s){
            $temp_arr = array(
                "billNum" => $time_micro++,
                "itemId" => $rl_bn_ids[$var_o_s["bn"]]["shop_iid"],
                "outerId" => $rl_bn_ids[$var_o_s["bn"]]["shop_product_bn"],
                "skuId" => $rl_bn_ids[$var_o_s["bn"]]["shop_sku_id"],
                "inventoryType" => "CERTAINTY",
                "quantity" => $var_o_s["quantity"],
            );
            $storeInventories_arr[]["storeInventory"] = $temp_arr;
        }
        $return_arr["stores"]["store"]["storeInventories"] = $storeInventories_arr;
        return $return_arr;
    }
    
    //新增门店
    /**
     * tbStoreCreate
     * @param mixed $store_id ID
     * @param mixed $errormsg errormsg
     * @return mixed 返回值
     */
    public function tbStoreCreate($store_id,&$errormsg){
        $param = $this->getStoreCreateParam($store_id);
        $result = kernel::single('tbo2o_event_trigger_store')->storeCreate($param);
        $return_result = false;
        $update_arr = array();
        if($result["rsp"] == "fail"){
            $update_arr["sync"] = 2;
            $errormsg = $result["msg"];
        }
        $data = json_decode($result["data"],true);
        if ($data["succ"][0]["response"]["flag"] == "success"){
            $update_arr["sync"] = 3; //新增成功
            $return_result = true;
            if($data["succ"][0]["response"]["storeId"]){
                $update_arr["outer_store_id"] = $data["succ"][0]["response"]["storeId"];
            }
        }
        if(!empty($update_arr)){
            $mdlTbo2oStore = app::get('tbo2o')->model('store');
            $filter_arr = array("store_id"=>$store_id);
            $mdlTbo2oStore->update($update_arr,$filter_arr);
        }
        return $return_result;
    }
    
    //更新门店
    /**
     * tbStoreUpdate
     * @param mixed $store_id ID
     * @param mixed $errormsg errormsg
     * @return mixed 返回值
     */
    public function tbStoreUpdate($store_id,&$errormsg){
        $param = $this->getStoreUpdateParam($store_id);
        $result = kernel::single('tbo2o_event_trigger_store')->storeUpdate($param);
        $return_result = false;
        $update_arr = array();
        if($result["rsp"] == "fail"){
            //更新失败
            $update_arr["sync"] = 2;
            $errormsg = $result["msg"];
        }
        $data = json_decode($result["data"],true);
        if ($data["succ"][0]["response"]["flag"] == "success"){
            $update_arr["sync"] = 3; //更新成功
            $return_result = true;
        }
        if(!empty($update_arr)){
            $mdlTbo2oStore = app::get('tbo2o')->model('store');
            $filter_arr = array("store_id"=>$store_id);
            $mdlTbo2oStore->update($update_arr,$filter_arr);
        }
        return $return_result;
    }
    
    //删除门店
    /**
     * tbStoreDelete
     * @param mixed $store_id ID
     * @param mixed $errormsg errormsg
     * @return mixed 返回值
     */
    public function tbStoreDelete($store_id,&$errormsg){
        $mdlTbo2oStore = app::get('tbo2o')->model('store');
        $rs_tbo2o_store = $mdlTbo2oStore->dump(array("store_id"=>$store_id),"outer_store_id");
        $param = array("storeId"=>$rs_tbo2o_store["outer_store_id"]);
        $result = kernel::single('tbo2o_event_trigger_store')->storeDelete($param);
        $return_result = false;
        if($result["rsp"] == "fail"){
            //删除失败
            $errormsg = $result["msg"];
        }
        $data = json_decode($result["data"],true);
        if ($data["succ"][0]["response"]["flag"] == "success"){
            $update_arr["sync"] = 1; //删除成功
            $update_arr["outer_store_id"] = "";
            $return_result = true;
        }
        if(!empty($update_arr)){
            $mdlTbo2oStore = app::get('tbo2o')->model('store');
            $filter_arr = array("store_id"=>$store_id);
            $mdlTbo2oStore->update($update_arr,$filter_arr);
        }
        return $return_result;
    }
    
    //门店查询
    /**
     * tbStoreQuery
     * @param mixed $outer_store_id ID
     * @param mixed $errormsg errormsg
     * @return mixed 返回值
     */
    public function tbStoreQuery($outer_store_id,&$errormsg){
        $param = array("storeId"=>$outer_store_id);
        $result = kernel::single('tbo2o_event_trigger_store')->storeQuery($param);
        $return_result = false;
        if($result["rsp"] == "fail"){
            //查询失败
            $errormsg = $result["msg"];
        }
        $data = json_decode($result["data"],true);
        if ($data["succ"][0]["response"]["flag"] == "success"){
            //查询成功
            $return_result = $data["succ"][0]["response"];
        }
        return $return_result;
    }
    
    //淘宝门店关联宝贝绑定和解绑
    /**
     * tbStoreItemStoreBanding
     * @param mixed $id ID
     * @param mixed $type type
     * @param mixed $errormsg errormsg
     * @return mixed 返回值
     */
    public function tbStoreItemStoreBanding($id,$type,&$errormsg){
        //获取商品id
        $mdlTbo2oStoreItems = app::get('tbo2o')->model('store_items');
        $rs_item = $mdlTbo2oStoreItems->dump(array("id"=>$id));
        //获取淘宝门店id
        $mdlTbo2oStore = app::get('tbo2o')->model('store');
        $rs_tb_store = $mdlTbo2oStore->dump(array("store_id"=>$rs_item["store_id"]),"outer_store_id");
        $param = kernel::single('tbo2o_common_tbo2oapi')->getItemstoreBandingParam($type,$rs_item["item_iid"],array($rs_tb_store["outer_store_id"]));
        $result = kernel::single('tbo2o_event_trigger_store')->storeItemstoreBanding($param);
        $return_result = false;
        if($result["rsp"] == "fail"){
            //操作失败
            $errormsg = $result["msg"];
        }
        $data = json_decode($result["data"],true);
        if ($data["succ"][0]["response"]["flag"] == "success"){
            $return_result = true;
            //绑定成功 更新bind字段和bind时间
            $filter_arr = array("id"=>$id);
            if($type == "ADD"){
                //绑定
                $update_arr = array("is_bind"=>1,"bind_time"=>time());
            }else{
                //解绑
                $update_arr = array("is_bind"=>0,"bind_time"=>"0");
            }
            $mdlTbo2oStoreItems->update($update_arr,$filter_arr);
        }
        return $return_result;
    }
    
}
