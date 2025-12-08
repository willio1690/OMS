<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_mdl_branch_product_pos extends dbeav_model{
    var $export_flag = false;
    
    function finder_list($cols='*', $filter=array(), $offset=0, $limit=-1, $orderby=null)
    {
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
                   $col_tmp[$k] = 'bpp.'.$v;
                }
            }
            $col_tmp[] = 'a.bm_id AS product_id,a.material_name AS name,a.material_bn AS bn,a.visibled AS visibility,bp.store_position,bpp.branch_id,bpp.product_id';
            $col_tmp[] = 'c.code AS barcode';
            $cols = implode(",",$col_tmp);

            $orderType = $orderby?$orderby:$this->defaultOrder;
            
            $sql = 'SELECT '.$cols.' FROM sdb_ome_branch_product_pos AS bpp 
                    LEFT JOIN sdb_ome_branch_pos AS bp ON(bpp.pos_id=bp.pos_id) 
                    LEFT JOIN sdb_material_basic_material AS a ON(bpp.product_id=a.bm_id) 
                    LEFT JOIN sdb_material_codebase as c on c.bm_id=a.bm_id 
                    WHERE '.$this->_filter($filter,'bpp').' and c.type=1';
            
            if($orderType)$sql.=' ORDER BY '.(is_array($orderType)?implode($orderType,' '):$orderType);
            $data = $this->db->selectLimit($sql,$limit,$offset);

            return $data;
    }

    function finder_count($filter=null){
        if ($_GET['act'] != 'index'){
            return parent::count($filter);
        }else {
            $sql = 'SELECT COUNT(*) AS _count FROM sdb_ome_branch_product_pos AS bpp
                    LEFT JOIN sdb_ome_branch_pos AS bp ON(bpp.pos_id=bp.pos_id)
                    LEFT JOIN sdb_material_basic_material AS a ON(bpp.product_id=a.bm_id) 
                    LEFT JOIN sdb_material_codebase as c on c.bm_id=a.bm_id 
                    WHERE '.$this->_filter($filter,'bpp').' and c.type=1';
            $row = $this->db->select($sql);
            return intval($row[0]['_count']);
        }
    }

  /*
     * 导出模板标题
     */
    function exportTemplate($filter){
        foreach ($this->io_title($filter) as $v){
            $title[] = kernel::single('base_charset')->utf2local($v);
        }
        return $title;
    }

     function io_title( $filter, $ioType='csv' ){
        switch( $filter ){
            case 'branch_pos':
                $this->oSchema['csv'][$filter] = array(
                    '*:货位名称' => 'pos_name',
                    '*:所属仓库' => 'branch_name',
                );
                break;
            case 'export_branch_pos':
                $this->oSchema['csv'][$filter] = array(
                    '*:货位名称' => 'pos_name',
                    '*:货品名称' => 'product_name',
                    '*:条形码' => 'barcode',
                    '*:货号' => 'product_bn',
                    '*:所属仓库' => 'branch_name',
                );
                break;
        }
        #新增导出列
        if($this->export_flag){
            $title = array(
                    '*:规格'=>'spec_info'
            );
            $this->oSchema['csv']['export_branch_pos'] = array_merge($this->oSchema['csv']['export_branch_pos'],$title);
        }
        $this->ioTitle[$ioType][$filter] = array_keys( $this->oSchema[$ioType][$filter] );
        return $this->ioTitle[$ioType][$filter];
     }

     /*
      * 导出货位记录
      */
     function fgetlist_csv( &$data,$filter,$offset,$exportType = 1 )
     {
         $this->export_flag = true;
        if( !$data['title']['export_branch_pos'] ){
            $title = array();
            foreach( $this->io_title('export_branch_pos') as $k => $v ){
                $title[] = $v;
            }
            $data['title']['export_branch_pos'] = '"'.implode('","',$title).'"';
        }
//        $limit = 100;

        $sql = 'SELECT bp.store_position, 
                a.bm_id AS product_id, a.material_name AS product_name, a.material_bn AS product_bn, 
                c.code AS barcode 
                FROM sdb_ome_branch_product_pos AS bpp 
                LEFT JOIN sdb_ome_branch_pos AS bp ON(bp.pos_id=bpp.pos_id) 
                LEFT JOIN sdb_material_basic_material AS a ON(bpp.product_id=a.bm_id) 
                LEFT JOIN sdb_material_codebase as c on c.bm_id=a.bm_id 
                WHERE '.$this->_filter($filter,'bpp').' and c.type=1';
        
        $sql .= ' ORDER BY bpp.pos_id desc ';
        $list = $this->db->select($sql);
        if (!$list) return false;
        foreach( $list as $val ){
            $pRow = array();
            $detail['pos_name'] = $val['store_position'];
            $detail['product_name'] = $val['product_name'];
            $detail['barcode'] = $val['barcode'];
            $detail['product_bn'] = $val['product_bn'];
            $detail['branch_name'] = $val['branch_name'];
            foreach( $this->oSchema['csv']['export_branch_pos'] as $k => $v ){
                $pRow[$k] =  utils::apath( $detail,explode('/',$v)  );
            }
            $data['content']['export_branch_pos'][] = '"'.implode('","',$pRow).'"';
        }
        $data['name'] = '货位表'.date("Ymd",time());

        return false;
    }
    function export_csv($data,$exportType = 1 ){
        $output = array();
        //if( $exportType == 2 ){
            foreach( $data['title'] as $k => $val ){
                $output[] = $val."\n".implode("\n",(array)$data['content'][$k]);
            }
        //}
        return implode("\n",$output);
    }

    /*
     * CSV导入
     */
    function prepared_import_csv(){
        $this->ioObj->cacheTime = time();
    }

    function finish_import_csv(){
        base_kvstore::instance('ome_branch_product_pos')->fetch('branch_product_pos-'.$this->ioObj->cacheTime,$data);
        base_kvstore::instance('ome_branch_product_pos')->store('branch_product_pos-'.$this->ioObj->cacheTime,'');
        $pTitle = array_flip( $this->io_title('branch_pos') );
        $pSchema = $this->oSchema['csv']['export_branch_pos'];
        $oQueue = app::get('base')->model('queue');
        $aP = $data;

        $pSdf = array();

        $count = 0;
        $limit = 50;
        $page = 0;
        $orderSdfs = array();

        foreach ($aP['branch_product_pos']['contents'] as $k => $aPi){
            if($count < $limit){
                $count ++;
            }else{
                $count = 0;
                $page ++;
            }
            $pSdf[$page][] = $aPi;
        }

        foreach($pSdf as $v){
            $queueData = array(
                'queue_title'=>'货位导入',
                'start_time'=>time(),
                'params'=>array(
                    'sdfdata'=>$v,
                    'app' => 'ome',
                    'mdl' => 'branch_product_pos'
                ),
                'status' => 'hibernate',
                'worker'=>'ome_branch_product_pos_to_import.run',
            );
            $oQueue->save($queueData);
        }
        $oQueue->flush();
        return null;
    }

    function prepared_import_csv_obj($data,$mark,$tmpl,&$msg = ''){
        return null;
    }

    //CSV导入业务处理
    function prepared_import_csv_row($row,$title,&$tmpl,&$mark,&$newObjFlag,&$msg)
    {
        $basicMaterialLib    = kernel::single('material_basic_material');
        $basicMaterialObj = app::get('material')->model('basic_material');
        
        if (empty($row)){
            if ($this->branch_error){
                $temp = $this->branch_error;
                $temp = array_unique($temp);
                sort($temp);
                $msg['error'] .= '\n系统中不存在的仓库：';
                $msg['error'] .= implode(',', $temp);
                unset($temp);
                unset($this->branch_error);
                base_kvstore::instance('ome_branch_product_pos')->store('branch_product_pos-'.$this->ioObj->cacheTime,'');
                return false;
            }
            if ($this->branch_pos_error){
                $temp = $this->branch_pos_error;
                $temp = array_unique($temp);
                sort($temp);
                $msg['error'] .= '\n系统中不存在的货位：';
                $msg['error'] .= implode(',', $temp);
                unset($temp);
                unset($this->branch_pos_error);
                base_kvstore::instance('ome_branch_product_pos')->store('branch_product_pos-'.$this->ioObj->cacheTime,'');
                return false;
            }
            return true;
        }
        $mark = false;
        $re = base_kvstore::instance('ome_branch_product_pos')->fetch('branch_product_pos-'.$this->ioObj->cacheTime,$fileData);

        if( !$re ) $fileData = array();

        if( substr($row[0],0,1) == '*' ){
            $titleRs =  array_flip($row);
            $mark = 'title';

            return $titleRs;
        }else{
            $row[3] = trim($row[3]);
            if ($row[4] and $row[0]){
                //判断仓库是否存在
                $branch_pos_obj = app::get('ome')->model('branch_pos');
                
                $branch = app::get('ome')->model('branch')->dump(array('name'=>$row[4]),'branch_id');
                $row[2] = trim($row[2]);
                if(!empty($row[2]) && $row[2]!=''){
                    
                    //通过条形码获取基础物料bm_id
                    $pFilter['bm_id'] = $basicMaterialLib->getMaterialBmidByCode($row[2]);
                }else{
                    $pFilter['material_bn'] = $row[3];
                }
                
                $products    = $basicMaterialObj->dump($pFilter, 'bm_id');
                
                $branch_pos = $branch_pos_obj->dump(array('store_position'=>trim($row[0]), 'branch_id'=>$branch['branch_id']), 'pos_id');
                if(!$branch){
                    $this->branch_error = isset($this->branch_error)?array_merge($this->branch_error,array($row[4])):array($row[4]);
                }
                if(!$branch_pos){
                    $this->branch_pos_error = isset($this->branch_pos_error)?array_merge($this->branch_pos_error,array($row[0])):array($row[0]);
                }

                $branch_product_pos = app::get('ome')->model('branch_product_pos')->dump(array('product_id'=>$products['bm_id'],'pos_id'=>$branch_pos['pos_id']),'pp_id');
                if($branch_product_pos){
                    $row['pp_id'] = $branch_product_pos['pp_id'];
                }
            }
            $fileData['branch_product_pos']['contents'][] = $row;
            base_kvstore::instance('ome_branch_product_pos')->store('branch_product_pos-'.$this->ioObj->cacheTime,$fileData);
        }
        return null;
    }


    function searchOptions(){
        return array(
                'store_position'=>app::get('ome')->_('货位'),
                'barcode'=>app::get('ome')->_('条形码'),
                'bn'=>app::get('ome')->_('货号'),
                'product_name'=>app::get('ome')->_('商品名称'),
            );
    }

    function _filter($filter,$tableAlias=null,$baseWhere=null)
    {
        $basicMaterialObj = app::get('material')->model('basic_material');

        if(isset($filter['store_position'])){
            $where .= " bp.store_position='".addslashes($filter['store_position'])."'";
            unset($filter['store_position']);
        }
        if(isset($filter['barcode'])){
            $where .= " c.code='".addslashes($filter['barcode'])."'";
            unset($filter['barcode']);
        }
        if(isset($filter['bn']))
        {
            $where_info    = $basicMaterialObj->dump(array('material_bn'=>$filter['bn']), 'bm_id');
            
            if($where_info['bm_id']){
                $where .= " bpp.product_id =".$where_info['bm_id'];
                unset($filter['bn']);
            }
        }
        if(isset($filter['product_name']))
        {
            $product    = $basicMaterialObj->getlist('bm_id', array('material_name'=>$filter['product_name']));
            
            if($product){
                $product_id = array();
               foreach($product as $product){
                $product_id[] = $product['bm_id'];
               }
               $product_id = implode(',',$product_id);
                $where .= " bpp.product_id in (".$product_id.")";

                unset($filter['product_name']);
                unset($product);
            }
        }
        if(isset($filter['branch_id']) && $filter['branch_id']==''){
            unset($filter['branch_id']);
        }
        $sWhere = parent::_filter($filter,$tableAlias,$baseWhere);
        if(!empty($where)){
            $sWhere .= " AND ".$where;
        }

        return $sWhere;
    }

    function pre_recycle(&$rows){
        $is_super = kernel::single('desktop_user')->is_super();
        if(!$is_super){
            $oBranch = app::get('ome')->model('branch');
            $branch_ids = $oBranch->getBranchByUser(true);
        }

        if($branch_ids){
            foreach ($rows as $key => $val){
                if(!in_array($val['branch_id'], $branch_ids)){
                    unset($rows[$key]);
                }
            }
        }
        return true;
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
        if ($logParams['app'] == 'ome' && $logParams['ctl'] == 'admin_branch_product_pos') {
            $type .= '_dailyManager_posTidy';
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
        if ($logParams['app'] == 'ome' && $logParams['ctl'] == 'admin_branch_product_pos') {
            $type .= '_dailyManager_posTidy';
        }
        $type .= '_import';
        return $type;
    }

    #获取一个批次的货位
    function get_products_pos($branch_id,$product_ids=array()){
       $str_product_ids = implode(',', $product_ids);
       $sql = "select 
                    pos.branch_id,product_id,store_position 
               from sdb_ome_branch_product_pos ppos
               join sdb_ome_branch_pos  pos on ppos.pos_id=pos.pos_id
               where  pos.branch_id=".$branch_id." and ppos.product_id in( ".$str_product_ids." )";
       $rows = $this->db->select($sql);
       if($rows){
           return $rows;
       }
       return false;
    }
}