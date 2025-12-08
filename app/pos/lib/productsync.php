<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class pos_productsync{

    
   
    //同步全部商品
    public function sync_all($filter)
    {
        $basicMaterialSelect    = kernel::single('material_basic_select');

        @ini_set('memory_limit','128M');
        $wfsObj = app::get('pos')->model('syncproduct');
        $db = kernel::database();
        $data = $this->getOrglist();
        $view = $filter['view'];
        if($view != ''){
            $desktop_filter_model = app::get('desktop')->model('filter');
            $_desktop_filter = $desktop_filter_model->getList('*',array('model'=>'pos_mdl_syncproduct'));

            $_count = count($data);
            $_filter = $_desktop_filter[(int)($view-$_count[0]['_count']-1)];
            $_filter_query = array();
            parse_str($_filter['filter_query'],$_filter_query);
            $filter = array_merge($filter,$_filter_query);
        }

      
      
        $sql_counter = " SELECT count(*) ";
        $sql_list = " SELECT * ";
        $wfsObj->filter_use_like = true;
        $sql_base = ' FROM `sdb_pos_syncproduct` WHERE '.$wfsObj->_filter($filter);
        $sql = $sql_counter . $sql_base;
   
        $count = $db->count($sql);
        $limit = 500;
        if ($count){
            $pagecount = ceil($count/$limit);
            for ($page=1;$page<=$pagecount;$page++){
                $offset = ($page-1) * $limit;
                $sql = $sql_list.$sql_base." ORDER BY `id` LIMIT ".$offset.",".$limit;
             
                $products = $db->select($sql);
                if ($products){
                    $product_ids = array();
                    foreach ($products as $p){
                        $product_ids[] = $p['bm_id'];
                    }
                }

              
                $this->syncProduct_notifydata($product_ids);
            
                
                $products_sdf = $product_ids = $products = NULL;
            }
        }

        return true;
    }

    
    /**
    *通过wms_id获取未分派的商品
    *@params $wms_id wms_id
    *return array 未分派的商品
    **/
    public function get_goods_by_wms($offset='0',$limit='999999')
    {
        $basicMaterialSelect    = kernel::single('material_basic_select');
        $codebaseObj = app::get('material')->model('codebase');
        $data           = array();
        $product_ids    = array();

        $wfsObj    = app::get('pos')->model('syncproduct');
        $temp_data    = $wfsObj->getList('bm_id');

        if($temp_data)
        {
            foreach ($temp_data as $key => $val)
            {
                $product_ids[]    = $val['bm_id'];
            }
        }

        $data    = $basicMaterialSelect->getlist('bm_id, material_bn, material_name', array('bm_id|notin'=>$product_ids));

        foreach ($data as &$v){
            $codebase = $codebaseObj->dump(array('bm_id'=>$v['product_id']),'code');

            $v['barcode'] = $codebase['code'];
        }
        return $data;
    }

    /**
    *通过wms_id获取未分派的商品
    *@params $wms_id wms_id
    *@params $search_key 搜索的键
    *@params $search_value 搜索的值
    *return array 未分派的商品
    **/
    public function get_data_by_search($search_key,$search_value)
    {
        $basicMaterialSelect    = kernel::single('material_basic_select');

        $data['search_key'] = $search_key;
        $data['search_value'] = $search_value;
        $data['wms_id'] = $wms_id;
        $product_ids = $this->get_filter($data);
        $limt = 10;
        $product_ids_tmp = array_chunk($product_ids,$limt);
        $db = kernel::database();
        for($i=0;$i<(count($product_ids) / $limt);$i++)
        {
            $res[]    = $basicMaterialSelect->getlist('bm_id, material_bn, material_name,type', array('bm_id'=>$product_ids_tmp[$i]));
        }
        $result = array();
        foreach($res as $key=>$value){
            foreach($value as $v){
                $result[] = $v;
            }
        }
        return $result;
    }


    /**
    *通过wms_id获取未分派的商品的数量
    *@params $wms_id wms_id
    *return array 未分派的商品
    **/
    public function get_goods_count_by_wms()
    {
        $basicMaterialSelect    = kernel::single('material_basic_select');
        $data           = array();
        $product_ids    = array();

        $wfsObj    = app::get('pos')->model('syncproduct');
        $temp_data    = $wfsObj->getList('bm_id', array());
       
        if($temp_data)
        {
            foreach ($temp_data as $key => $val)
            {
                $product_ids[]    = $val['bm_id'];
            }
        }

        $data    = $basicMaterialSelect->getlist('bm_id, material_bn, material_name,type', array('bm_id|notin'=>$product_ids));

        $tmp[0]['count'] = count($data);
        return $tmp;
    }

    /**
    *通过product_id获取未分派的商品的数量
    *@params array $product_id
    *return array 未分派的商品
    **/
    public function get_wms_goods($product_id)
    {
        $basicMaterialSelect    = kernel::single('material_basic_select');

        $data    = $basicMaterialSelect->getlist('bm_id, material_bn, material_name,type', array('bm_id'=>$product_id));
     
        return $data;
    }

    /**
    *获取自定义搜素选项
    *return array
    **/
    public function get_search_options(){
        $options = array(
            'bn'=>'货品编码',
            'name'=>'货品名称',
            'brand'=>'商品品牌',
            'barcode'=>'条码',
        );
        return $options;
    }

    /**
    *获取自定义搜素选项
    *return array
    **/
    public function get_search_list(){
        $brandObj = app::get('ome')->model('brand');
        //$packObj = app::get('ome')->model('pack');//
        $brand_tmp =$brandObj->getList('brand_name,brand_id');
        $brand = array();
        foreach($brand_tmp as $branddata){
            $brand[$branddata['brand_id']] = $branddata['brand_name'];
        }
        //$pack_tmp = $packObj->getList('pack_name');//
        $pack = array();
        //foreach($pack_tmp as $packdata){
           // $pack[] = $packdata['pack_name'];
        //}
        $list = array(
            'brand'=>$brand,
            //'pack'=>$pack,
        );
        return $list;
    }

    /**
    *组织filter条件
    *return array
    **/
    public function get_filter($data,$offset='0',$limit='999999'){
        if(empty($data['search_key']) || empty($data['search_value'])){
            return false;
        }
     
        $db = kernel::database();

        $wfsObj    = app::get('pos')->model('syncproduct');
        $temp_data    = $wfsObj->getList('bm_id', array());

        $bm_ids    = 0;
        if($temp_data)
        {
            foreach ($temp_data as $key => $val)
            {
                $product_ids[]    = $val['bm_id'];
            }
            $bm_ids    = implode(',', $product_ids);
        }

        switch ($data['search_key']) {
            case 'bn':
                $sql    = "SELECT bm_id AS product_id, material_name AS name, material_bn AS bn
                           FROM sdb_material_basic_material WHERE material_bn like '".$data['search_value']."%'
                           AND bm_id not in(". $bm_ids .") AND visibled=1 limit ". $offset .",". $limit;
                $row = $db->select($sql);
                break;

            case 'name':
                $sql    = "SELECT bm_id AS product_id, material_name AS name, material_bn AS bn
                           FROM sdb_material_basic_material WHERE material_name like '".$data['search_value']."%'
                           AND bm_id not in(". $bm_ids .") AND visibled=1 limit ". $offset .",". $limit;
                $row = $db->select($sql);
                break;

            case 'brand':
                $sql    = "SELECT bm_id AS product_id, material_name AS name, material_bn AS bn
                           FROM sdb_material_basic_material WHERE bm_id not in(". $bm_ids .")
                           AND visibled=1 limit ". $offset .",". $limit;
                $row = $db->select($sql);
                break;

            case 'pack':

                break;
            case 'barcode':
                $sql    = "SELECT m.bm_id AS product_id, m.material_name AS name, m.material_bn AS bn
                           FROM sdb_material_basic_material as m LEFT JOIN sdb_material_codebase as b ON m.bm_id=b.bm_id WHERE b.code like '".$data['search_value']."%'
                           AND m.bm_id not in(". $bm_ids .") AND m.visibled=1 limit ". $offset .",". $limit;

                $row = $db->select($sql);
                break;
        }

        $data = array();
        foreach($row as $v){
            $data[] = $v['product_id'];
        }
        return $data;
    }

    /*通过搜索条件获取未分派的商品
    *@params $filter 搜索条件
    *return array 未分派的商品
    **/
    public function get_goods_by_product_ids($product_ids)
    {
        $basicMaterialSelect    = kernel::single('material_basic_select');
        $db      = kernel::database();
        $data    = array();
        $codebaseObj = app::get('material')->model('codebase');
        if($product_ids)
        {
            $data    = $basicMaterialSelect->getlist('bm_id, material_bn, material_name', array('bm_id'=>$product_ids));
            foreach ($data as &$v){
                $codebase = $codebaseObj->dump(array('bm_id'=>$v['product_id']),'code');

                $v['barcode'] = $codebase['code'];
            }
        }

        return $data;
    }

  

    

    public function getOrglist(){
        $orgMdl = app::get('organization')->model('organization');
        $orglist = $orgMdl->getlist('*',array('org_type'=>1,'org_level_num'=>1,'source'=>'system'));
        return $orglist;
    }

    /**
    * 商品同步发起通知数据
    */
    function syncProduct_notifydata($product_sdf) {
       
        if (is_array($product_sdf)) {
            $org_id = $wms['org_id'];

            foreach($product_sdf as $key=>$bm_id ){

                kernel::single('pos_event_trigger_goods')->add($bm_id);

            }


        }
        return true;
    }

}
