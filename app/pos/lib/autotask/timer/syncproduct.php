<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/*
 * 商品同步
 */
class pos_autotask_timer_syncproduct {
    /* 当前的执行时间 */
    public static $now;
    
    /* 执行的间隔时间 */
    const intervalTime = 1800;
    
    function __construct()
    {
        self::$now = time();
    }

    /**
     * 处理
     * @param mixed $params 参数
     * @param mixed $error_msg error_msg
     * @return mixed 返回值
     */
    public function process($params, &$error_msg = '')
    {
        
        base_kvstore::instance('pos/sync/product')->fetch('sync-lastexectime',$lastExecTime);
        if (!$lastExecTime) {
            $lastExecTime = strtotime('-1 days');
        }
         
        base_kvstore::instance('pos/sync/product')->store('sync-lastexectime', self::$now);

        $data = array(
            'start_date'    =>  date('Y-m-d H:i:s',$lastExecTime),
            'end_date'      =>  date('Y-m-d H:i:s',self::$now),
        );


        $this->syncStore();

        $materials = $this->getMaterials($data);
       
        if($materials){

            $this->insertSyncProduct($materials);

            $stores = $this->getStores();
            foreach($stores as $store){
                $this->insertProductPrice($store,$materials);
            }
        }

        //填充因没有跑进来的product有 price没有
        $this->fillsyncPrice();
       
        $this->syncProductQueue();
     
        $this->syncPriceQueue();
       
    }

    
    
    /**
     * 获取Materials
     * @param mixed $params 参数
     * @return mixed 返回结果
     */
    public function getMaterials($params)
    {
        
        $start_date = strtotime($params['start_date']);
        $end_date = strtotime($params['end_date']);
        $basicMaterialSelect = kernel::single('material_basic_select');
        $data    = $basicMaterialSelect->getlist('bm_id, material_bn, material_name,type', array('last_modified|between'=>array($start_date,$end_date)));
        $bm_ids = array_column($data,'product_id');
        $extMdl = app::get('material')->model('basic_material_ext');
        $exts = $extMdl->getlist('cat_id,bm_id',array('bm_id'=>$bm_ids));
        $ext = array_column($exts,null,'bm_id');
        $type_ids = array_column($exts,'cat_id');
        $typeMdl = app::get('ome')->model('goods_type');
        $lists = $typeMdl->getList('name,type_id',array('type_id' => $type_ids));
        $types = array_column($lists,null,'type_id');
        foreach($data as $k=>$v){
            $type_id = $ext[$v['product_id']]['cat_id'];
            $type_name = $types[$type_id]['name'];
            $data[$k]['type_name'] = $type_name;
        }
        return $data;
    }

    /**
     * 获取Stores
     * @return mixed 返回结果
     */
    public function getStores(){
        $storeMdl = app::get('o2o')->model('store');
        
        $servers = kernel::single('pos_event_trigger_common')->getChannelId('pekon');
        $server_id = $servers['server_id'];
        $stores = $storeMdl->getlist('store_id,store_bn,store_sort',array('server_id'=>$server_id));
        return $stores;
    }


    /**
     * insertSyncProduct
     * @param mixed $data 数据
     * @return mixed 返回值
     */
    public function insertSyncProduct($data) {
        
        $syncproductMdl = app::get('pos')->model('syncproduct');
        $product_ids = array_column($data, 'product_id');

        $material_exts = $this->getMaterialExt($product_ids);
        $syncproducts = $syncproductMdl->getlist('id,bm_id',array('bm_id'=>$product_ids));

        $sync_list = array_column($syncproducts,'id');

        $sync_productids = $syncproducts  ? array_column($syncproducts,'bm_id') : array();  


        if($sync_list){
            $syncproductMdl->db->exec("UPDATE sdb_pos_syncproduct SET sync_status='0' WHERE id in(".implode(',',$sync_list).")");
        }
      
        $inItems = [];
        foreach($data as $v){
            if(in_array($v['product_id'],$sync_productids)) continue;
            $retail_price = 0;

            if($material_exts[$v['product_id']]){
                $retail_price = $material_exts[$v['product_id']]['retail_price'];
            }
            $inItems[] = array(

                'material_bn'   =>  $v['bn'],
                'bm_id'         =>  $v['product_id'],
                'type'          =>  $v['type'],
                'retail_price'  =>  $retail_price,
            );
        }
        if($inItems){
            $sql = kernel::single('ome_func')->get_insert_sql($syncproductMdl, $inItems);
            $rs = $syncproductMdl->db->exec($sql);
        }
        

    }

    


    /**
     * insertProductPrice
     * @param mixed $stores stores
     * @param mixed $data 数据
     * @return mixed 返回值
     */
    public function insertProductPrice($stores,$data) {
        $productpriceMdl = app::get('pos')->model('productprice');
       
        $bm_ids = array_column($data,'product_id');
        $material_bns = array_column($data,'bn');
        $store_id = $stores['store_id'];
        
        $products = $productpriceMdl->getlist('*',array('store_id'=>$store_id,'bm_id'=>$bm_ids));
        $productList = array();
        if($products){
            foreach($products as $v){
                $productList[$v['store_id']][$v['bm_id']] = $v['id'];
            }
        }
        
        $storeMdl = app::get('o2o')->model('store'); 
       
        $store_bn = $stores['store_bn'];
        $tariff = 1;
        $store_sort = $stores['store_sort'];
      
        $inItems = [];
        foreach($data as $v){
            $bm_id = $v['product_id'];
            
         
           
            if($productList && $productList[$store_id][$bm_id]){
                $id = $productList[$store_id][$bm_id];
               
                
            }else{
               
                $price_status = '0';
                if(!$price) {
                    $price_status = '1';

                    $price = 0;
                }
                $inItems[] = array(

                    'material_bn'       =>  $v['bn'],
                    'bm_id'             =>  $bm_id,
                    'store_id'          =>  $store_id,
                    'store_bn'          =>  $store_bn,
                    'tariff'            =>  $tariff,
                    'price'             =>  $price,
                    'store_sort'        =>  $store_sort,
                    'price_status'      =>  $price_status, 
                );
            }
            
        }
        if($inItems){
            $sql = kernel::single('ome_func')->get_insert_sql($productpriceMdl, $inItems);
            $rs = $productpriceMdl->db->exec($sql);
        }
              
    }

    

    /*
     *  定时轮询门店
     */

    public function syncStore(){
        $servers = kernel::single('pos_event_trigger_common')->getChannelId('pekon');
        $server_id = $servers['server_id'];
        $storeMdl = app::get('o2o')->model('store');
        $stores = $storeMdl->getlist('store_id,store_bn',array('server_id'=>$server_id,'sync_status'=>array('0','2')));

        foreach($stores as $store){
            kernel::single('pos_event_trigger_shop')->add($store['store_id']);
        }
       
    }


    /**
     * 定时同步商品
     * @param  
     * @return 
     */
    public function syncProductQueue(){
        $syncproductMdl = app::get('pos')->model('syncproduct');
        $total = $syncproductMdl->count(array('sync_status'=>array('0','2')));
        if($total <= 0){
            return true;
        }
        $limit = 200;
        $pagenums = ceil($total / $limit);

        //分页获取数据保存到文件中
        for ($page = 1; $page <= $pagenums; $page++) {
            $offset = ($page - 1) * $limit;

            $products = $syncproductMdl->getlist('id,bm_id',array('sync_status'=>array('0','2')),$offset,$limit,' type asc');
            foreach($products as $v){
                kernel::single('pos_event_trigger_goods')->add($v['id']);
            }
        }

    }

    /**
     * 定时同步价格
     * @param  
     * @return 
     */
    public function syncPriceQueue(){
       
       $productpriceMdl = app::get('pos')->model('productprice');
       $total = $productpriceMdl->count(array('sync_status'=>array('0')));
        if($total <= 0){
            return true;
        }
        $limit = 200;
        $pagenums = ceil($total / $limit);

        for ($page = 1; $page <= $pagenums; $page++) {
            $offset = 0;
            $products = $productpriceMdl->getlist('*',array('sync_status'=>array('0')),$offset,$limit);
            if(!$products) break;
            if($products){
                kernel::single('pos_event_trigger_goods')->syncprice($products);
            }
            
        }
        
    }

    public function getMaterialExt($bm_ids){
        $basicMaterialExtObj = app::get('material')->model('basic_material_ext');
        $extList             = $basicMaterialExtObj->getList('bm_id, cost, brand_id,retail_price', array('bm_id' => $bm_ids));
        $extList = array_column($extList, null, 'bm_id');
        return $extList;
    }

    /**
     * fillsyncPrice
     * @return mixed 返回值
     */
    public function fillsyncPrice(){
        $db = kernel::database();
        $sql = "SELECT bm_id FROM sdb_pos_syncproduct where sync_status in('1') AND bm_id not in(select bm_id from sdb_pos_productprice)";
        $syncproducts = $db->select($sql);
        if(!$syncproducts) return true;
        $bm_ids = array_column($syncproducts,'bm_id');

        $basicMaterialSelect = kernel::single('material_basic_select');
        $materials    = $basicMaterialSelect->getlist('bm_id, material_bn, material_name,type', array('bm_id'=>$bm_ids));
        $stores = $this->getStores();
        foreach($stores as $store){
            $this->insertProductPrice($store,$materials);
        }

    }

   

    /**
     * 获取StoresChannel
     * @param mixed $channel channel
     * @return mixed 返回结果
     */
    public function getStoresChannel($channel){
        $storeMdl = app::get('o2o')->model('store');
        
        $servers = kernel::single('pos_event_trigger_common')->getChannelId('pekon');
        $server_id = $servers['server_id'];
        $stores = $storeMdl->getlist('store_id,store_bn,store_sort',array('server_id'=>$server_id,'store_sort'=>$channel));
        return $stores;
    }
}