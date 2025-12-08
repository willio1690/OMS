<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_data_original_stock
{
    /**
     * 获取库存列表
     * 
     * @param array $filter
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function getBnBranchStore($filter, $offset, $limit=100)
    {
        $basicMaterialObj = app::get('material')->model('basic_material');
        $branchProductModel = app::get('ome')->model('branch_product');
        
        $formatFilter = kernel::single('openapi_format_abstract');
        
        $where = array(1);
        $dataList = array();
        
        //销售物料编码
        if($filter['goods_bn']) {
            $salesMaterialObj = app::get('material')->model('sales_material');
            $salesBasicMaterialMdl = app::get('material')->model('sales_basic_material');
            
            //list
            $bm_ids = array();
            $tempList = $salesMaterialObj->getlist('sm_id', array('sales_material_bn'=>$filter['goods_bn']));
            $sm_ids = array_column($tempList, 'sm_id');
            if($sm_ids){
                $tempList = $salesBasicMaterialMdl->getlist('bm_id', array('sm_id'=>$sm_ids));
                $bm_ids = array_column($tempList, 'bm_id');
            }
            
            if($bm_ids){
                $where[] = "bm_id IN(". implode(',', $bm_ids) .")";
            }else{
                $where[] = "bm_id=-1";
            }
        }
        
        //基础物料编码
        if(!empty($filter['product_bn'])) {
            $tempList = $basicMaterialObj->getlist('bm_id', array('material_bn'=>$filter['product_bn']));
            
            $bm_ids = array_column($tempList, 'bm_id');
            if($bm_ids){
                $where[] = "bm_id IN(". implode(',', $bm_ids) .")";
            }else{
                $where[] = "bm_id=-1";
            }
        }
        
        //count
        $countSql = "SELECT count(*) AS nums FROM sdb_material_basic_material_stock WHERE ". implode(' AND ', $where);
        $count = $basicMaterialObj->db->selectrow($countSql);
        $count = $count['nums'];
        if(empty($count)){
            return array('lists'=>$dataList, 'count'=>0);
        }
        
        //list
        $sql = "SELECT * FROM sdb_material_basic_material_stock WHERE ". implode(' AND ', $where) ." ORDER BY bm_id ASC LIMIT ". $offset .",". $limit;
        $productList = $basicMaterialObj->db->select($sql);
        
        $bm_ids = array_column($productList, 'bm_id');
        
        //基础物料列表
        $materialList = $basicMaterialObj->getlist('bm_id,material_bn,material_name', array('bm_id'=>$bm_ids));
        $materialList = array_column($materialList, null, 'bm_id');
        
        //仓库列表
        $sql = "SELECT branch_id,branch_bn,name FROM sdb_ome_branch WHERE 1";
        $tempList = $basicMaterialObj->db->select($sql);
        $branchList = array_column($tempList, null, 'branch_id');
        
        //仓库库存列表
        $branchProductList = array();
        $tempList = $branchProductModel->getList('*', array('product_id'=>$bm_ids));
        foreach ((array)$tempList as $stockKey => $stockVal)
        {
            $product_id = $stockVal['product_id'];
            $branch_id = $stockVal['branch_id'];
            
            //仓库信息
            $branchInfo = $branchList[$branch_id];
            
            //info
            $stockInfo = array(
                'branch_bn'    => $formatFilter->charFilter($branchInfo['branch_bn']),
                'branch_name'  => $formatFilter->charFilter($branchInfo['name']),
                'store'        => $stockVal['store'],
                'store_freeze' => $stockVal['store_freeze'],
                'arrive_store' => $stockVal['arrive_store'],
                //'store_position' => implode(',', $this->_getPos($product_id, $branch_id, $tempList)), //代码写的耗费性能
            );
            
            $branchProductList[$product_id][$branch_id] = $stockInfo;
        }
        
        //format
        foreach ($productList as $key => $val)
        {
            $bm_id = $val['bm_id'];
            
            //基础物料信息
            $materialInfo = $materialList[$bm_id];
            
            //仓库库存信息
            $branchstore = $branchProductList[$bm_id];
            
            //data
            $dataList[$key] = array(
                'bn' => $formatFilter->charFilter($materialInfo['material_bn']),
                'name' => $formatFilter->charFilter($materialInfo['material_name']),
                'store' => $val['store'],
                'store_freeze' => $val['store_freeze'],
                'branchstore' => $branchstore,
            );
        }
        
        //unset
        unset($where, $countSql, $sql, $tempList, $materialList, $branchProductList, $productList);
        
        return array('lists'=>$dataList, 'count'=>$count);
    }

    /**
     * 查询货位
     * 
     * @return void
     * @author 
     * */
    private function _getPos($pid, $bid, $branch_products)
    {
        static $bppList;

        if (isset($bppList)) return (array) $bppList[$pid][$bid];

        $bppList = array();

        $product_id = $branch_id = $pos_id = array();
        foreach ($branch_products as $key => $value) {
            $product_id[] = $value['product_id'];
            $branch_id[]  = $value['branch_id'];
        }

        $bppMdl = app::get('ome')->model('branch_product_pos');

        foreach ($bppMdl->getList('branch_id,product_id,pos_id', array('branch_id'=>$branch_id,'product_id'=>$product_id)) as $value) {
            $bppList[$value['product_id']][$value['branch_id']][$value['pos_id']] = &$posList[$value['branch_id']][$value['pos_id']];

            $pos_id[] = $value['pos_id'];
        }

        if (!$bppList) return array();

        $branchPosMdl = app::get('ome')->model('branch_pos');

        foreach ($branchPosMdl->getList('pos_id,store_position,branch_id',array('branch_id'=>$branch_id,'pos_id'=>$pos_id)) as $value) {
            $posList[$value['branch_id']][$value['pos_id']] = $value['store_position'];
        }

        return (array) $bppList[$pid][$bid];
    }

    private function getBranch($branch_id)
    {

        static $branchList;

        if ($branchList[$branch_id]) return $branchList[$branch_id];

        $branchModel = app::get('ome')->model('branch');

        $branchList[$branch_id] = $branchModel->dump($branch_id,'branch_bn,name');

        return $branchList[$branch_id];
    }


        /**
     * 获取DetailList
     * @param mixed $filter filter
     * @param mixed $offset offset
     * @param mixed $limit limit
     * @return mixed 返回结果
     */
    public function getDetailList($filter,$offset,$limit = 100)
    {
        $basicMStockFreezeLib  = kernel::single('material_basic_material_stock_freeze');
        $where = array();

        $basicMaterialObj = app::get('material')->model('basic_material');
        if ($filter['product_bn']) {
            $product = $basicMaterialObj->dump(array('material_bn' => $filter['product_bn']),'bm_id');
            $where['product_id'] = $product['bm_id'];
        }

        if ($filter['branch_name'] || $filter['branch_bn']) {
            $branchModel = app::get('ome')->model('branch');

            if(!empty($filter['branch_name'])){
                $branchFilter = array_unique( array('branch_bn' => $filter['branch_bn'],'name' => $filter['branch_name']) );
            }else{
                $branchFilter = array('branch_bn' => $filter['branch_bn']);
            }

            $branch = $branchModel->dump($branchFilter,'branch_id');

            $where['branch_id'] = $branch['branch_id'];
        }

        // 货位查询
        if ($filter['store_position']) {
            $posMdl = app::get('ome')->model('branch_pos');

            $pos = $posMdl->db_dump(array ('store_position' => $filter['store_position']), 'pos_id,store_position');

            $where['pos_id'] = $pos['pos_id'] ? $pos['pos_id'] : 0;

            $bppMdl = app::get('ome')->model('branch_product_pos');

            // $count = $bppMdl->count($where);

            $rows = $bppMdl->getList('*',$where);

            $where['product_id'] = $where['branch_id'] = array (0);
            foreach ($rows as $row) {
                $where['product_id'][] = $row['product_id'];
                $where['branch_id'][] = $row['branch_id'];
            }
        }
        
        $filter_sql = [];
        if($filter['modified_start']) {
            $filter_sql[] = 'up_time >="'.date('Y-m-d H:i:s', strtotime($filter['modified_start'])) .'"';
        }
        if($filter['modified_end']) {
            $filter_sql[] = 'up_time <"'.date('Y-m-d H:i:s', strtotime($filter['modified_end'])) .'"';
        }
        if($filter_sql) {
            $where['filter_sql'] = implode(' and ', $filter_sql);
        }
        $branchProductModel = app::get('ome')->model('branch_product');

        $count = $branchProductModel->count($where);

        $branchProductList = $branchProductModel->getList('*',$where,$offset,$limit);

        $data = array();
        $formatFilter=kernel::single('openapi_format_abstract');
        if ($branchProductList) {
            $productIdArr = array();
            $i = '1';
            foreach ($branchProductList as $key => $bp) {
                $product_info = $basicMaterialObj->dump($bp['product_id'],'material_bn,material_name');
                $productIdArr[] = $bp['product_id'];
                
                //根据仓库ID、基础物料ID获取该物料仓库级的预占
                $bp['store_freeze']    = $basicMStockFreezeLib->getBranchFreeze($bp['product_id'], $bp['branch_id']);
                
                $data[$i]['store']            = $bp['store'];
                $data[$i]['store_freeze']     = $bp['store_freeze'];
                $data[$i]['store_in_transit'] = $bp['arrive_store'];
                $data[$i]['product_bn']   = $formatFilter->charFilter($product_info['material_bn']);
                $data[$i]['product_name'] = $formatFilter->charFilter($product_info['material_name']);
                $data[$i]['product_spec'] = '';

                $branch = $this->getBranch($bp['branch_id']);
                $data[$i]['branch_bn']   = $formatFilter->charFilter($branch['branch_bn']);
                $data[$i]['branch_name'] = $formatFilter->charFilter($branch['name']);

                $i++;
            }
        }

        return array('lists' => $data,'count' => (int) $count);
        
    }
    
    /**
     * 查询货品对应的总库存、冻结库存
     * todo: 现在是根据传入的barcode条形码指定获取数据,$offset和 $limit两个参数不用;
     * 
     * @param array $filter
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function getProductBnStock($filter, $offset=0, $limit=50){
        $basicMaterialObj = app::get('material')->model('basic_material');
        $where = "WHERE 1 ";
        
        //check
        if(empty($filter['barcode']) && empty($filter['material_bn'])){
            //条件barcode和bn都没有传,直接返回false
            return false;
        }
        
        //barcode
        if($filter['barcode']){
            $where .= " AND b.code IN('". implode("','", $filter['barcode']) ."') AND b.type=1 "; //条形码type类型为1
        }
        
        //material_bn
        if($filter['material_bn']){
            $where .= " AND a.material_bn IN('". implode("','", $filter['material_bn']) ."')";
        }
        
        //getList
        $sql = "SELECT a.bm_id, a.material_bn, b.code FROM sdb_material_basic_material AS a LEFT JOIN sdb_material_codebase AS b ON a.bm_id=b.bm_id ". $where;
        $dataList = $basicMaterialObj->db->select($sql);
        if(empty($dataList)){
            return array('lists'=>array(), 'count'=>0); //返回空数据
        }
        
        //data
        $data = array();
        $bm_ids = array();
        foreach ($dataList as $key => $val){
            $bm_id = $val['bm_id'];
            
            $data[$bm_id] = array(
                    'bn' => $val['material_bn'],
                    'barcode' => $val['code'],
            );
            
            $bm_ids[] = $bm_id;
        }
        
        //store
        $sql = "SELECT bm_id, store, store_freeze FROM sdb_material_basic_material_stock WHERE bm_id IN(". implode(',', $bm_ids) .")";
        $dataList = $basicMaterialObj->db->select($sql);
        foreach ($dataList as $key => $val){
            $bm_id = $val['bm_id'];
            
            $data[$bm_id]['store'] = $val['store'];
            $data[$bm_id]['store_freeze'] = $val['store_freeze'];
        }
        
        $count = count($data);
        
        unset($filter, $dataList, $bm_ids);
        
        return array('lists'=>$data,'count'=>$count);
    }
}