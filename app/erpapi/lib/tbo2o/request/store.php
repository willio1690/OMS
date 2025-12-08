<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 淘宝o2o请求接口函数实现类
 * 20160825
 * @author wangjianjun<wangjianjun@shopex.cn>
 * @version 0.1
 */
class erpapi_tbo2o_request_store extends erpapi_tbo2o_request_abstract{

    /**
     * 门店新增接口请求
     * @param array $sdf 请求参数
     */
    public function store_create($sdf){
        $params = array(
            "data" => $this->arrayToXml($sdf),
        );
        $title = "门店新增";
        return $this->__caller->call(QIMEN_STORE_CREATE,$params,null,$title,10,"");
    }
    
    /**
     * 查询门店主营类目信息接口
     * @param array $sdf 请求参数
     */
    public function store_storecategory_get($sdf){
        $params = array(
            "data" => $this->arrayToXml($sdf),
        );
        $title = "查询门店主营类目信息";
        return $this->__caller->call(QIMEN_STORECATEGORY_GET,$params,null,$title,10,"");
    }
    
    /**
     * 更新门店信息接口
     * @param array $sdf 请求参数
     */
    public function store_update($sdf){
        $store_id = "storeId".$sdf["storeId"];
        $params = array(
            "data" => $this->arrayToXml($sdf),
        );
        $title = "更新门店";
        return $this->__caller->call(QIMEN_STORE_UPDATE,$params,null,$title,10,$store_id);
    }
    
    /**
     * 删除线下门店数据接口
     * @param array $sdf 请求参数
     */
    public function store_delete($sdf){
        $store_id = "storeId".$sdf["storeId"];
        $params = array(
                "data" => $this->arrayToXml($sdf),
        );
        $title = "删除线下门店数据";
        return $this->__caller->call(QIMEN_STORE_DELETE,$params,null,$title,10,$store_id);
    }
    
    /**
     * 查询门店信息接口
     * @param array $sdf 请求参数
     */
    public function store_query($sdf){
        $store_id = "storeId".$sdf["storeId"];
        $params = array(
            "data" => $this->arrayToXml($sdf),
        );
        $title = "查询门店信息";
        return $this->__caller->call(QIMEN_STORE_QUERY,$params,null,$title,10,$store_id);
    }
    
    /**
     * 新建/删除商品和门店的绑定关系
     * @param array $sdf 请求参数
     */
    public function store_itemstore_banding($sdf){
        $title = "新建商品和门店的绑定关系";
        if($sdf["actionType"] == "DELETE"){
            $title = "删除商品和门店的绑定关系";
        }
        $params = array(
            "data" => $this->arrayToXml($sdf),
        );
        return $this->__caller->call(QIMEN_ITEMSTORE_BANDING,$params,null,$title,10,"");
    }
    
    /**
     * 查询线上商品所关联的门店列表
     * @param array $sdf 请求参数
     */
    public function store_itemstore_query($sdf){
        $params = $sdf; //后修改
        $title = "查询线上商品所关联的门店列表";
        return $this->__caller->call(QIMEN_ITEMSTORE_QUERY,$params,null,$title,10,$sdf["0"]);
    }
    
    /**
     * 查询门店所关联的线上商品列表
     * @param array $sdf 请求参数
     */
    public function store_storeitem_query($sdf){
        $params = $sdf; //后修改
        $title = "查询门店所关联的线上商品列表";
        return $this->__caller->call(QIMEN_STOREITEM_QUERY,$params,null,$title,10,$sdf["0"]);
    }
    
    /**
     * [新增]维护宝贝货品映射关系
     * 
     * @param array $sdf 请求参数
     */
    public function store_scitem_add($sdf){
        
        $title = '同步后端商品至淘宝';
        
        //格式化参数
        $bn        = $sdf['bn'];
        $price     = floatval($sdf['price']);
        
        $data      = array(
                        'item_name'=>$sdf['name'],
                        'outer_code'=>$bn,
                        'item_type'=>intval($sdf['item_type']),
                        //'properties'=>$sdf['properties'],//商品属性
                        'bar_code'=>$sdf['barcode'],
                        //'wms_code'=>$sdf['wms_code'],//仓储商编码
                        'is_friable'=>intval($sdf['is_friable']),
                        'is_dangerous'=>intval($sdf['is_dangerous']),
                        'is_costly'=>intval($sdf['is_costly']),
                        'is_warranty'=>intval($sdf['is_warranty']),
                        'weight'=>intval($sdf['weight']),
                        'length'=>intval($sdf['length']),
                        'width'=>intval($sdf['width']),
                        'height'=>intval($sdf['height']),
                        'volume'=>intval($sdf['volume']),
                        'price'=>$price,
                        //'remark'=>$sdf['remark'],//备注
                        'matter_status'=>intval($sdf['matter_status']),
                        //'brand_id'=>$sdf['brand_id'],//品牌ID暂时不传
                        'brand_name'=>$sdf['brand_name'],
                        //'spu_id'=>$sdf['spu_id'],//spuId或是cspuid
                        'is_area_sale'=>intval($sdf['is_area_sale']),
                );
        
        //json
        $params    = array();
        $params['data']    = json_encode($data);
        unset($data, $price, $sdf);
        
        return $this->__caller->call(SCITEM_ADD, $params, null, $title, 15, $bn);
    }
    
    /**
     * [更新]修改的后端商品信息至淘宝
     * 
     * @param array $sdf 请求参数
     */
    public function store_scitem_update($sdf)
    {
        $title = '更新后端商品至淘宝';
        
        //格式化参数
        $bn        = $sdf['bn'];
        $price     = floatval($sdf['price']);
        
        $data      = array(
                        'item_id'=>$sdf['outer_id'],
                        'outer_code'=>$bn,
                        'item_name'=>$sdf['name'],
                        'item_type'=>intval($sdf['item_type']),
                        //'update_properties'=>$sdf['properties'],//需要更新的商品属性
                        'bar_code'=>$sdf['barcode'],
                        //'wms_code'=>$sdf['wms_code'],//仓储商编码
                        'is_friable'=>intval($sdf['is_friable']),
                        'is_dangerous'=>intval($sdf['is_dangerous']),
                        'is_costly'=>intval($sdf['is_costly']),
                        'is_warranty'=>intval($sdf['is_warranty']),
                        'weight'=>intval($sdf['weight']),
                        'length'=>intval($sdf['length']),
                        'width'=>intval($sdf['width']),
                        'height'=>intval($sdf['height']),
                        'volume'=>intval($sdf['volume']),
                        'price'=>$price,
                        //'remark'=>$sdf['remark'],//备注
                        'matter_status'=>intval($sdf['matter_status']),
                        //'brand_id'=>$sdf['brand_id'],//品牌ID暂时不传
                        'brand_name'=>$sdf['brand_name'],
                        //'spu_id'=>$sdf['spu_id'],//spuId或是cspuid
                        //'remove_properties'=>$sdf['remove_properties'],//移除商品属性列表
                        'is_area_sale'=>intval($sdf['is_area_sale']),
                );
        
        //json
        $params    = array();
        $params['data']    = json_encode($data);
        unset($data, $price, $sdf);
        
        return $this->__caller->call(SCITEM_UPDATE, $params, null, $title, 15, $bn);
    }
    
    /**
     * 同步淘宝后端商品
     *
     * @param array $sdf 请求参数
     */
    public function store_scitem_query($sdf)
    {
        $title = '同步淘宝后端商品';
        
        //格式化参数
        $data      = array(
                        'page_index'=>intval($sdf['page']),
                        'page_size'=>intval($sdf['page_size']),
                    );
        
        //json
        $params    = array();
        $params['data']    = json_encode($data);
        unset($data, $sdf);
        
        return $this->__caller->call(SCITEM_QUERY, $params, null, $title, 30, '');
    }
    
    /**
     * 宝贝和货品的关联
     * 
     * @param array $sdf 请求参数
     */
    public function store_scitem_map_add($sdf){
        $title    = "创建IC商品与后端商品的映射关系";
        
        //格式化参数
        $product_bn    = $sdf['shop_product_bn'];
        $data      = array(
                'item_id'=>$sdf['shop_iid'],
                'sku_id'=>$sdf['shop_sku_id'],
                'sc_item_id'=>$sdf['outer_id'],
                'outer_code'=>$sdf['product_bn'],
                //'need_check'=>'true',//进行高级校验,前端商品或SKU的商家编码必须与后端商品的商家编码一致,否则会拒绝关联
        );
        
        //json
        $params    = array();
        $params['data']    = json_encode($data);
        unset($data, $sdf);
        
        return $this->__caller->call(SCITEM_MAP_ADD, $params, null, $title, 20, $product_bn);
    }
    
    /**
     * [解绑]指定用户的商品与后端商品的映射关系
     * 
     * @param array $sdf 请求参数
     */
    public function store_scitem_map_delete($sdf){
        $title    = "解绑淘宝前端宝贝与后端商品的映射关系";
        
        //格式化参数
        $product_bn    = $sdf['shop_product_bn'];
        $data      = array(
                'sc_item_id'=>$sdf['outer_id'],
                //'user_nick'=>'',//店铺用户user_nick
        );
        
        //json
        $params    = array();
        $params['data']    = json_encode($data);
        unset($data, $sdf);
        
        return $this->__caller->call(SCITEM_MAP_DELETE, $params, null, $title, 20, $product_bn);
    }
    
    /**
     * 全量更新电商仓或门店库存
     * @param array $sdf 请求参数
     */
    public function store_inventory_iteminitial($sdf){
        $inventory_bn = "";
        $title = "阿里全渠道电商仓库存回写";
        //盘点触发的库存接口有盘点单号
        if(isset($sdf["inventory_bn"])){
            $inventory_bn = $sdf["inventory_bn"];
            unset($sdf["inventory_bn"]);
            $title = "全量更新门店库存";
        }
        $params = array(
                "data" => $this->arrayToXml($sdf),
        );
        return $this->__caller->call(QIMEN_STOREINVENTORY_ITEMINITIAL,$params,null,$title,10,$inventory_bn);
    }
    
    /**
     * 增量更新门店或电商仓库存
     * @param array $sdf 请求参数
     */
    public function store_inventory_itemupdate($sdf){
        $inventory_bn = "";
        //盘点触发的库存接口有盘点单号
        if(isset($sdf["inventory_bn"])){
            $inventory_bn = $sdf["inventory_bn"];
            unset($sdf["inventory_bn"]);
        }
        $params = array(
                "data" => $this->arrayToXml($sdf),
        );
        $title = "增量更新门店仓库存";
        return $this->__caller->call(QIMEN_STOREINVENTORY_ITEMUPDATE,$params,null,$title,10,$inventory_bn);
    }
    
}