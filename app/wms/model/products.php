<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class wms_mdl_products extends ome_mdl_products
{
    public function table_name($real = false){
        if($real){
           $table_name = 'sdb_material_basic_material';
        }else{
           $table_name = 'basic_material';
        }
        return $table_name;
    }

    public function get_schema(){
        $schema = app::get('material')->model('basic_material')->get_schema();
        
        return $schema;
        
    }
    /**
    * 列表
    */
    function getlist($cols='*', $filter=array(), $offset=0, $limit=-1, $orderby=null)
    {
        $strWhere = array();
        if(isset($filter['branch_id']) && $filter['branch_id']){
            if (is_array($filter['branch_id'])){
                $strWhere[] = ' bp.branch_id IN ('.implode(',', $filter['branch_id']).') ';
            }else {
                $strWhere[] = ' bp.branch_id = '.$filter['branch_id'];
            }
        }
        $orderType = $orderby?$orderby:$this->defaultOrder;
        
        $sql = 'SELECT a.*, b.retail_price, b.cost, b.weight, b.unit, sum(bp.store) as store 
                FROM '. DB_PREFIX .'ome_branch_product AS bp LEFT JOIN '. DB_PREFIX .'material_basic_material AS a ON bp.product_id=a.bm_id 
                LEFT JOIN '. DB_PREFIX .'material_basic_material_ext AS b ON b.bm_id=a.bm_id 
                WHERE  '.implode(' AND ',$strWhere).$this->_filter($filter,'p');
        $sql.=" GROUP BY bp.product_id";
        
        $data = $this->db->selectLimit($sql,$limit,$offset);
        
        return $data;
    }

    /**
    * 统计
    */
    function countlist($filter=null){
        $orderby = FALSE;
        $strWhere = array();
        if(isset($filter['branch_id']) && $filter['branch_id']){
            if (is_array($filter['branch_id'])){
                $strWhere[] = ' bp.branch_id IN ('.implode(',', $filter['branch_id']).') ';
            }else {
                $strWhere[] = ' bp.branch_id = '.$filter['branch_id'];
            }
        }
        
        $sql = 'SELECT count(bp.product_id) AS num 
                FROM '. DB_PREFIX .'ome_branch_product AS bp LEFT JOIN '. DB_PREFIX .'material_basic_material AS a ON bp.product_id=a.bm_id 
                WHERE  '.implode(' AND ',$strWhere).$this->_filter($filter,'p');
        
        $row = $this->db->selectrow($sql);
        
        return $row['num'];
    }

   

    /**
    * 库存导出
    */
    function fgetlist_csv( &$data,$filter,$offset,$exportType = 1 ) {
        if( !$data['title']){
            $title = array();

            foreach($this->io_title('products') as $k => $v ){
                $title[] = $this->charset->utf2local($v);
            }
            $data['title']['products'] = '"'.implode('","',$title).'"';
        }
        if( !$list=$this->getlist('*',$filter,0,-1) )return false;
        foreach( $list as $aFilter ){
            $pRow = array();
            
            $detail['bn'] ="\t".$this->charset->utf2local($aFilter['bn']);
            $detail['barcode'] ="\t".$this->charset->utf2local($aFilter['barcode']);
            $detail['name'] = $this->charset->utf2local($aFilter['name']);
            $detail['spec_info'] = $this->charset->utf2local($aFilter['spec_info']);
            $detail['store'] = $aFilter['store'];
            #$detail['store_freeze'] = $aFilter['store_freeze'];
            #$detail['arrive_store'] = $aFilter['arrive_store'];
            foreach( $this->oSchema['csv']['products'] as $k => $v ){

                $pRow[$k] =  utils::apath( $detail,explode('/',$v) );
            }
            $data['contents']['products'][] = implode(',',$pRow);
        }

   
        return false;
    }

    function export_csv($data,$exportType = 1 ){

        $output = array();
        $output[] = $data['title']['products']."\n".implode("\n",(array)$data['contents']['products']);

        echo implode("\n",$output);
    }

    function io_title( $filter, $ioType='csv' ){

        switch( $filter ){
            case 'products':
                $this->oSchema['csv'][$filter] = array(
               
                '*:货号' => 'bn',
                '*:条形码' => 'barcode',
                '*:货品名称' => 'name',
                '*:规格' => 'spec_info',
                '*:库存' => 'store',
                #'*:冻结库存' => 'store_freeze',
                #'*:在途库存'=>'arrive_store'
                );
                break;
        }
        $this->ioTitle[$ioType][$filter] = array_keys( $this->oSchema[$ioType][$filter] );
        return $this->ioTitle[$ioType][$filter];
     }
   
    function _filter($filter,$tableAlias=null,$baseWhere=null){
        
        if (isset($filter['visibility']) && $filter['visibility']=='0') {
            unset($filter['visibility']);
        }
        return $where ." AND ".parent::_filter($filter,$tableAlias,$baseWhere);
    }
    
   #已弃用,现使用基础物料
   public function getProuductInfoById($product_id = false){
       
       return '';
   }



}
?>