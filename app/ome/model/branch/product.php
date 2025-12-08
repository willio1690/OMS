<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_mdl_branch_product extends dbeav_model{
     var $export_name = '仓库库存';
     
    function getlists($cols='*', $filter=array(), $offset=0, $limit=-1, $orderby=null){
        
        $strWhere = '';
        if($cols){
            $cols = str_replace('Array,','branch_id,product_id,',$cols);
            $cols = trim($cols);
        }
        if(!$cols){
            $cols = $this->defaultCols;
        }
        if(!empty($this->appendCols)){
            $cols.=','.$this->appendCols;
        }
        $col_tmp = explode(",",$cols);
        foreach($col_tmp as $k=>$v){
            $tmp = explode(" ",$v);
            if(!is_numeric($tmp[0])){
                $col_tmp[$k] = 'bp.'.$v;
            }
        }
     //仓库号
        if(isset($filter['branch_id']) && $filter['branch_id']){
            if (is_array($filter['branch_id'])){
                $strWhere = ' AND bp.branch_id IN ('.implode(',', $filter['branch_id']).') ';
            }else {
                $strWhere = ' AND bp.branch_id = '.$filter['branch_id'];
            }
        }else{
            if ($filter['branch_ids']) {
                if (is_array($filter['branch_ids'])){
                    $strWhere = ' AND bp.branch_id IN ('.implode(',', $filter['branch_ids']).') ';
                }else {
                    $strWhere = ' AND bp.branch_id = '.$filter['branch_ids'];
                }
            }
        }
        //货号
        if(isset($filter['bn']) && $filter['bn']!=''){
            $strWhere.=' AND a.material_bn like \''.$filter['bn'].'%\'';
        }
        //真实库存
        if(isset($filter['actual_store']) && $filter['actual_store']!=''){
            $strWhere.=' AND bp.store';
            if($filter['_actual_store_search']=='nequal'){
                $strWhere.=' =';
            }else if($filter['_actual_store_search']=='than'){
                $strWhere.=' >';
            }else if($filter['_actual_store_search']=='lthan'){
                $strWhere.=' <';
            }
            $strWhere.=$filter['actual_store'];
        }
        //可用库存
        if(isset($filter['enum_store']) && $filter['enum_store']!=''){
            if($filter['_enum_store_search']=='nequal'){
                $strWhere.=' AND bp.store-bp.store_freeze='.$filter['enum_store'];
            }else if($filter['_enum_store_search']=='than'){
                $strWhere.=' AND (if(bp.store_freeze>bp.store,-1,bp.store - bp.store_freeze))>'.$filter['enum_store'];
            }else if($filter['_enum_store_search']=='lthan'){
                $strWhere.=' AND (if(bp.store_freeze>bp.store,-1,bp.store - bp.store_freeze))<'.$filter['enum_store'];
            }
        }
        
        $strWhere .= ' and a.visibled=1';
        
        $col_tmp[]    = 'a.bm_id AS product_id, a.material_name AS name, a.material_bn AS bn, a.visibled AS visibility';
        $col_tmp[]    = 'c.code AS barcode';
        $cols = implode(",",$col_tmp);
        $orderType = $orderby?$orderby:$this->defaultOrder;
        
        $sql = 'SELECT '.$cols.' FROM sdb_ome_branch_product AS bp 
                LEFT join '. DB_PREFIX .'material_basic_material as a on bp.product_id=a.bm_id 
                LEFT join '. DB_PREFIX .'material_codebase as c on c.bm_id=a.bm_id 
                WHERE a.material_bn != \'\' '.$strWhere;
		if($orderType)$sql.=' ORDER BY '.(is_array($orderType)?implode(',', $orderType):$orderType);

		$data = $this->db->selectLimit($sql,$limit,$offset);
        return $data;
    }
    function countlist($filter=null){
        $orderby = FALSE;
        //仓库号
        if(isset($filter['branch_id']) && $filter['branch_id']){
            if (is_array($filter['branch_id'])){
                $strWhere = ' AND bp.branch_id IN ('.implode(',', $filter['branch_id']).') ';
            }else {
                $strWhere = ' AND bp.branch_id = '.$filter['branch_id'];
            }
        }else{
            if ($filter['branch_ids']) {
                if (is_array($filter['branch_ids'])){
                    $strWhere = ' AND bp.branch_id IN ('.implode(',', $filter['branch_ids']).') ';
                }else {
                    $strWhere = ' AND bp.branch_id = '.$filter['branch_ids'];
                }
            }
        }
        //货号
        if(isset($filter['bn']) && $filter['bn']!=''){
            $strWhere.=' AND a.material_bn like \''.$filter['bn'].'%\'';
        }
        //真实库存
        if(isset($filter['actual_store']) && $filter['actual_store'] != ''){
            $strWhere.=' AND bp.store';
            if($filter['_actual_store_search']=='nequal'){
                $strWhere.=' =';
            }else if($filter['_actual_store_search']=='than'){
                $strWhere.=' >';
            }else if($filter['_actual_store_search']=='lthan'){
                $strWhere.=' <';
            }
            $strWhere.=$filter['actual_store'];
        }
        
        $strWhere .= ' and a.visibled=1';
        
        //可用库存
        if(isset($filter['enum_store']) && $filter['enum_store']!=''){
            if($filter['_enum_store_search']=='nequal'){
                $strWhere.=' AND bp.store-bp.store_freeze='.$filter['enum_store'];
            }else if($filter['_enum_store_search']=='than'){
                $strWhere.=' AND (if(bp.store_freeze>bp.store,-1,bp.store - bp.store_freeze))>'.$filter['enum_store'];
            }else if($filter['_enum_store_search']=='lthan'){
                $strWhere.=' AND bp.store_freeze>bp.store';
            }
        }
        
        $col_tmp[]    = 'a.bm_id AS product_id, a.material_name AS name, a.material_bn AS bn, a.visibled AS visibility';
        
        $cols = implode(",",$col_tmp);
        $orderType = $orderby?$orderby:$this->defaultOrder;
        
        $sql = 'SELECT count(branch_id) as _count FROM sdb_ome_branch_product AS bp 
                LEFT join '. DB_PREFIX .'material_basic_material as a on bp.product_id=a.bm_id 
                WHERE a.material_bn != \'\' '.$strWhere;
        
        if($orderType){
            $sql.=' ORDER BY '.(is_array($orderType)?implode(',', $orderType):$orderType);
        }

        $row = $this->db->selectrow($sql);
        return intval($row['_count']);
    }
     function fgetlist_csv( &$data,$filter,$offset,$exportType = 1 ) {

        $branchObj = $this->app->model('branch');
        $basicMStockFreezeLib  = kernel::single('material_basic_material_stock_freeze');
        
        if( !$data['title']){
            $title = array();

            foreach($this->io_title('branch_product') as $k => $v ){
                $title[] = $this->charset->utf2local($v);
            }
            $data['title']['branch_product'] = '"'.implode('","',$title).'"';
            $data['content']['main'][]= '"'.implode('","',$title).'"';
        }
        //$limit =100;

        if( !$list=$this->getlists('*',$filter,0,-1) )return false;
        foreach( $list as $aFilter ){
            $branch = $branchObj->dump($aFilter['branch_id'],'name');
            $pRow = array();
            
            //根据仓库ID、基础物料ID获取该物料仓库级的预占
            $aFilter['store_freeze']    = $basicMStockFreezeLib->getBranchFreeze($aFilter['product_id'], $aFilter['branch_id']);
            
            $detail['store'] = $aFilter['store'];
            $detail['store_freeze'] = $aFilter['store_freeze'];
            $detail['barcode'] = $this->charset->utf2local($aFilter['barcode'])."\t";
            $detail['name'] = $this->charset->utf2local($aFilter['name']);
            $detail['bn'] = $this->charset->utf2local($aFilter['bn'])."\t";
            $detail['spec_info'] = $this->charset->utf2local($aFilter['spec_info']);
            $detail['branch_name'] = $this->charset->utf2local($branch['name']);
            $detail['arrive_store'] = $aFilter['arrive_store'];
            foreach( $this->oSchema['csv']['branch_product'] as $k => $v ){

                $pRow[$k] =  utils::apath( $detail,explode('/',$v) );
            }
            $data['content']['branch_product'][] = '"'.implode('","',$pRow).'"';
            $data['content']['main'][] = '"'.implode('","',$pRow).'"';
        }
        //$data['export_name'] = '仓库'.date("YmdHis");
        return false;
    }

    /**
     * fcount_csv
     * @param mixed $filter filter
     * @return mixed 返回值
     */
    public function fcount_csv($filter = NULL)
    {
        return $this->countlist($filter);
    }

    function io_title( $filter, $ioType='csv' ){

        switch( $filter ){
            case 'branch_product':
                $this->oSchema['csv'][$filter] = array(
                '*:仓库' => 'branch_name',
                '*:货号' => 'bn',
                '*:条形码' => 'barcode',
                '*:货品名称' => 'name',
                '*:规格' => 'spec_info',
                '*:库存' => 'store',
                '*:冻结库存' => 'store_freeze',
                '*:在途库存'=>'arrive_store'
                );
                break;
        }
        $this->ioTitle[$ioType][$filter] = array_keys( $this->oSchema[$ioType][$filter] );
        return $this->ioTitle[$ioType][$filter];
     }

     function export_csv($data,$exportType = 1 ){
        $output = array();
        foreach( $data['title'] as $k => $val ){

            $output[] = $val."\n".implode("\n",(array)$data['content'][$k]);
        }

        echo implode("\n",$output);
    }
    /**
     * exportName
     * @param mixed $data 数据
     * @return mixed 返回值
     */
    public function exportName(&$data){
        $branch_id = $_POST['branch_id'];

        $branchObj = app::get('ome')->model('branch');
        if(isset($branch_id) && trim($branch_id)){
            $branch = $branchObj->getlist('name',array('branch_id'=>$branch_id));
            $export_name = $branch[0]['name'];
        }else{
            $export_name='全部仓库';
        }
        $data['name'] = $export_name.'库存'.date('Ymd');
    }

    /**
     * 获得日志类型(non-PHPdoc)
     * @see dbeav_model::getLogType()
     */
    public function getLogType($logParams) {
        $type = $logParams['type'];
        $logType = 'none';
        if ($type == 'export') {
            $logType = $this->exportLogType($logParams);
        }
        elseif ($type == 'import') {
            $logType = $this->importLogType($logParams);
        }
        return $logType;
    }
    /**
     * 导出日志类型
     * @param Array $logParams 日志参数
     */
    public function exportLogType($logParams) {
        $params = $logParams['params'];
        $type = 'warehouse';
        if ($logParams['app'] == 'ome' && $logParams['ctl'] == 'admin_branch_product') {
            $type .= '_stockManager_stockList';
        }
        $type .= '_export';
        return $type;
    }
    /**
     * 导入操作日志类型
     * @param Array $logParams 日志参数
     */
    public function importLogType($logParams) {
        $params = $logParams['params'];
        $type = 'warehouse';
        if ($logParams['app'] == 'ome' && $logParams['ctl'] == 'admin_branch_product') {
            $type .= '_stockManager_stockList';
        }
        $type .= '_import';
        return $type;
    }

    function getStoreByBasic($cols='*', $filter=array(), $offset=0, $limit=-1, $orderby=null){
        $where = '';
        if (!empty($filter) && is_array($filter)) {
            foreach ($filter as $val) {
                if (is_array($val)) {
                    $where .= 'in ('.implode(',', array_unique($val)).')';
                } else {
                    $where .= '= '.$val;
                }
            }
        }
        $orderType = $orderby?$orderby:$this->defaultOrder;
        $sql = 'SELECT '.$cols.' FROM sdb_ome_branch_product
                WHERE product_id '.$where;
        if($orderType)$sql.=' ORDER BY '.(is_array($orderType)?implode(',', $orderType):$orderType);
        return $this->db->selectLimit($sql,$limit,$offset);
    }

    function getBranchByBasic($cols='*', $filter=array(), $offset=0, $limit=-1, $orderby=null){
        $where = '';
        if (!empty($filter) && is_array($filter)) {
            foreach ($filter as $val) {
                if (is_array($val)) {
                    $where .= 'in ('.implode(',', array_unique($val)).')';
                } else {
                    $where .= '= '.$val;
                }
            }
        }
        $orderType = $orderby?$orderby:$this->defaultOrder;
        $sql = 'SELECT '.$cols.' FROM sdb_ome_branch_product AS bp 
                LEFT join '. DB_PREFIX .'ome_branch as b on bp.branch_id=b.branch_id 
                WHERE bp.product_id '.$where;
        if($orderType)$sql.=' ORDER BY '.(is_array($orderType)?implode(',', $orderType):$orderType);
        return $this->db->selectLimit($sql,$limit,$offset);
    }
}
?>
