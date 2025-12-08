<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class taoguanallocate_mdl_appropriation extends dbeav_model{
    
        var $export_name = '调拔单';
        public function exportName(&$data, $filter = array())
        {
            $data['name'] = $this->export_name . '-' . date('Y-m-d H:i:s', time());
        }
        
        function searchOptions(){
            return array(

                );
        }
	
        function _filter($filter,$tableAlias=null,$baseWhere=null){
            $where = "1";

            if(isset($filter['product_bn'])){
                $itemsObj = $this->app->model("appropriation_items");
                $rows = $itemsObj->getOrderIdByPbn($filter['product_bn']);
                $appropriationId[] = 0;
                foreach($rows as $row){
                    $appropriationId[] = $row['appropriation_id'];
                }
                $where .= '  AND appropriation_id IN ('.implode(',', $appropriationId).')';
                unset($filter['product_bn']);
            }

            if(isset($filter['product_barcode'])){
                $itemsObj = $this->app->model("appropriation_items");
                $rows = $itemsObj->getOrderIdByPbarcode($filter['product_barcode']);
                $appropriationId[] = 0;
                foreach($rows as $row){
                    $appropriationId[] = $row['appropriation_id'];
                }
                $where .= '  AND appropriation_id IN ('.implode(',', $appropriationId).')';
                unset($filter['product_barcode']);
            }

            return parent::_filter($filter,$tableAlias,$baseWhere)." AND ".$where;
        }
		
	   function prepared_import_csv_row($row,$title,&$Tmpl,&$mark,&$newObjFlag,&$msg){
            $fileData = $this->import_data;
            if( !$fileData ){
                $fileData = array();
            }
            if(!empty($row)){
                $fileData[ $this->key++] = $row;
                #获取所有csv导入数据数组
                $this->import_data = $fileData;
            }
            return null;
        }
        function finish_import_csv(){

            #获取所有已读取的csv导入数据
            $fileData = $this->import_data;
            #判断是否值导入了一笔调拨单
            if( substr($fileData[2][0],0,1) != '*' ){
                echo "<script>alert('每次只能导入一笔调拨单！')</script>";exit;
            }
            
            $basicMaterialObj = app::get('material')->model('basic_material');
            
            $oBranch = app::get('ome')->model('branch');
            $oCorp = app::get('ome')->model('dly_corp');
            $oBranchProduct = app::get('ome')->model('branch_product');
            $oStock = kernel::single('console_stock_products');
            $branchLib = kernel::single('ome_branch');
            $channelLib = kernel::single('channel_func');
            #判断经办人是否填写       
            if(!$fileData[1][2]){
                echo "<script>alert('经办人必须填写完整！')</script>";exit;
            }
            #物流公司
            if($fileData[1][3]){
                $corp_id = $oCorp->dump(array('name'=>$fileData[1][3]),'corp_id');
                if(!$corp_id){
                    echo "<script>alert('物流公司不存在！')</script>";exit;
                }               
            }
            
            #调出仓库
            if(!$fileData[1][4]){
                echo "<script>alert('调出仓库必须填写完整！')</script>";exit;
            }
            $from_branch_id = $oBranch->dump(array('name'=>$fileData[1][4]),'branch_id'); 
            if(!$from_branch_id){
                echo "<script>alert('调出仓库不存在！')</script>";exit;
            }
            $from_wms_id = $branchLib->getWmsIdById($from_branch_id['branch_id']);
            $from_is_selfWms = $channelLib->isSelfWms($from_wms_id);//调出仓库是否自有仓储
            //调入仓库
            if(!$fileData[1][5]){
                echo "<script>alert('调入仓库必须填写完整！')</script>";exit;
            }
            $to_branch_id = $oBranch->dump(array('name'=>$fileData[1][5]),'branch_id');
            if(!$to_branch_id){
                echo "<script>alert('调入仓库不存在!')</script>";exit;
            }
            $to_wms_id = $branchLib->getWmsIdById($to_branch_id['branch_id']);
            $to_is_selfWms = $channelLib->isSelfWms($to_wms_id);//调入仓库是否自有仓储
            $appropriationType = app::get('ome')->getConf('taoguanallocate.appropriation_type');
            $appropriation_type = $appropriationType=='directly'?1:2;
            if($appropriation_type==1&&(!$from_is_selfWms||!$to_is_selfWms)){
                echo "<script>alert('第三方仓库只能使用出入库调拨!')</script>";exit;
            }
            #经办人
            $operator = $fileData[1][2];
            #备注
            $memo = $fileData[1][6];
            #错误信息
            $msg = array();
            #去掉标题信息和调拨单信息
            unset($fileData['']);unset($fileData[1]);unset($fileData[2]);
            $arr_product_bns = array();
            foreach ($fileData as $key => $value) {
                $product_bn = trim($value[1]);
                
                #货品货号
                if(!$product_bn){
                    echo "<script>alert('第".($key+1)."行调拨货品货号必须填写完整！')</script>";exit;
                }
                if (!empty($arr_product_bns) && in_array($product_bn,$arr_product_bns)){
                    echo "<script>alert('第".($key+1)."行调拨货品货号已重复！')</script>";exit;
                }
                $arr_product_bns[] = $product_bn;
                
                #调拨数量
                $nums = intval($value[6]);
                if( $nums <= 0 ){
                    echo "<script>alert('第".($key+1)."行调出数量无效！')</script>";exit;
                }

                $product = $basicMaterialObj->dump(array('material_bn'=>$product_bn), '*');
                
                if(!$product){               
                    echo "<script>alert('第".($key+1)."行调出货品不存在！')</script>";exit;
                }

                $from_branch_product = $oBranchProduct->dump(array('branch_id'=>$from_branch_id['branch_id'],'product_id'=>$product['bm_id']),'*');
                if( !$from_branch_product ){
                    echo "<script>alert('第".($key+1)."行调出仓库和商品关系未建立,不可以调拔！')</script>";exit;
                }
                $from_branch_num = $oStock->get_branch_usable_store($from_branch_id['branch_id'],$product['bm_id']);
                $to_branch_num = $oStock->get_branch_usable_store($to_branch_id['branch_id'],$product['bm_id']);
                if( $from_branch_num < $nums){
                    echo "<script>alert('第".($key+1)."行调出仓库所剩数量不足以本次调拔!')</script>";exit;
                }

                if((!kernel::single('taoguaninventory_inventorylist')->checkproductoper($product['bm_id'],$from_branch_id['branch_id']))||(!kernel::single('taoguaninventory_inventorylist')->checkproductoper($product['bm_id'],$to_branch_id['branch_id']))){
                    echo "<script>alert('第".($key+1)."行调出货品正在盘点中，不可以调拔!')</script>";exit;
                }
                #组织数据
                $data['from_pos_id'] = 0;
                $data['to_pos_id'] = 0;
                $data['from_branch_id'] = $from_branch_id['branch_id'];
                $data['to_branch_id'] = $to_branch_id['branch_id'];
                $data['product_id'] = $product['bm_id'];
                $data['from_branch_num'] = $from_branch_num;
                $data['to_branch_num'] = $to_branch_num;
                $data['num'] = $nums;

                $adata[] = $data;
            }
            #日志数据
            $log_data['adata'] = $adata;
            $log_data['appropriation_type'] = $appropriation_type;
            $log_data['memo'] = $memo;
            $log_data['operator'] = $operator;
            $log_data['msg'] = $msg;
            $allocateObj = kernel::single('console_receipt_allocate');
            $result = $allocateObj->to_savestore($adata,$appropriation_type,$memo,$operator,$msg);
            if($result){
                #调拔出库通知已修改为审核时才发起通知
                #$iostockObj->notify_otherstock(1,$result,'create');
                ome_operation_log::insert('taoguanallocate_appropriation_addtransfer_allocation', $log_data, '调拨单新建成功');
                echo "<script>alert('导入调拨单新建成功!')</script>";exit;
            }else{
                ome_operation_log::insert('taoguanallocate_appropriation_addtransfer_allocation', $log_data, '调拨单新建失败');
                echo "<script>alert('导入调拨单新建失败!')</script>";exit;
            }

        }
	
        function prepared_import_csv_obj($data,$mark,$goodsTmpl,&$msg = ''){
            return null;
        } 
        function exportTemplate($filter){
            foreach ($this->io_title($filter) as $v){
                $title[] = kernel::single('base_charset')->utf2local($v);
            }
            return $title;
        }
        function io_title( $filter=null,$ioType='csv' ){
            switch( $ioType ){
                case 'csv':
                    default:
                    $this->oSchema['csv']['appropriation'] = array(
                        '*:调拨单号' => 'appropriation_no',
                        '*:建单日期' => 'create_time',
                        '*:经办人' => 'operator_name',
                        '*:物流公司' => 'logi_name',
                        '*:调出仓库名称' => 'from_branch',
                        '*:调入仓库名称' => 'to_branch',
                        '*:备注' => 'memo',
                    );                    
                    $this->oSchema['csv']['items'] = array( 
                        '*:调拨单号' => 'appropriation_no',
                        '*:货号' => 'bn',
                        '*:货品名称' => 'product_name',
                        '*:条形码' => 'barcode',
                        '*:调出仓库数量' => 'from_branch_num',
                        '*:调入仓库数量' => 'to_branch_num',
                        '*:调拨数量' => 'num',
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
        function fgetlist_csv( &$data,$filter,$offset,$exportType = 1 )
        {
            $basicMaterialLib    = kernel::single('material_basic_material');
            $basicMaterialBarcode    = kernel::single('material_basic_material_barcode');
            
            @ini_set('memory_limit','1024M'); set_time_limit(0); 
            $this->export_flag = true;
            $max_offset = 1000; // 最多一次导出10w条记录
            if ($offset>$max_offset) return false;// 限制导出的最大页码数
            if( !$data['title']['appropriation'] ){
                $title = array();
                foreach( $this->io_title('appropriation') as $k => $v ){
                    $title[] = $this->charset->utf2local($v);
                }
                $data['title']['appropriation'] = '"'.implode('","',$title).'"';
            }
            if( !$data['title']['items'] ){
                $title = array();
                foreach( $this->io_title('items') as $k => $v )
                    $title[] = $this->charset->utf2local($v);
                $data['title']['items'] = '"'.implode('","',$title).'"';
            }
            $limit = 100;
            if( !$list=$this->getList('appropriation_id',$filter,$offset*$limit,$limit) ) return false;
            foreach( $list as $aFilter ){
                if( !$appropriation = $this->dump($aFilter['appropriation_id'])){
                    return false;
                }
                if( !$items = $this->db->select("SELECT * FROM sdb_taoguanallocate_appropriation_items WHERE appropriation_id=".$appropriation['appropriation_id'])){
                    return false;
                }
                $from_branch = $this->db->select("SELECT name FROM sdb_ome_branch WHERE branch_id=".$items[0]['from_branch_id']);
                $to_branch = $this->db->select("SELECT name FROM sdb_ome_branch WHERE branch_id=".$items[0]['to_branch_id']);
                $corp_info = $this->db->select("SELECT name FROM sdb_ome_dly_corp WHERE corp_id=".$appropriation['corp_id']);
                $appropriationRow = array();
                $appropriationRow['*:调拨单号'] = $appropriation['appropriation_no'];
                $appropriationRow['*:生成日期'] = date('Y-m-d H:i:s', $appropriation['create_time']); 
                $appropriationRow['*:经办人'] = $appropriation['operator_name'];
                $appropriationRow['*:物流公司'] = $corp_info[0]['name'];
                $appropriationRow['*:调出仓库名称'] = $from_branch[0]['name'];
                $appropriationRow['*:调入仓库名称'] = $to_branch[0]['name'];
                $appropriationRow['*:备注'] = $appropriation['memo'];
                $data['content']['appropriation'][] = $this->charset->utf2local('"'.implode( '","', $appropriationRow ).'"');
                foreach ($items as $item)
                {
                    //基础物料信息
                    $product    = $basicMaterialLib->getBasicMaterialBybn($item['bn']);
                    
                    $product['barcode']    = $basicMaterialBarcode->getBarcodeById($product['bm_id']);
                    
                    $itemsRow = array();
                    
                    $itemsRow['*:调拨单号'] = $appropriation['appropriation_no'];
                    $itemsRow['*:货号'] = $item['bn'];
                    $itemsRow['*:货品名称'] = $item['product_name'];
                    
                    //$itemsRow['*:规格'] = $product['specifications'];
                    
                    $itemsRow['*:条形码'] = $product['barcode'];
                    $itemsRow['*:调出仓数量'] = $item['from_branch_num'];
                    $itemsRow['*:调入仓数量'] = $item['to_branch_num'];
                    $itemsRow['*:调拨数量'] = $item['num'];

                    $data['content']['items'][] = $this->charset->utf2local('"'.implode( '","', $itemsRow ).'"');
                }
            }
            return true;
        }
    /**
     * 获取DataByBranch
     * @param mixed $op_name op_name
     * @param mixed $branch branch
     * @return mixed 返回结果
     */
    public function getDataByBranch($op_name,$branch){
        $time = strtotime(date('Y-m-d',time()));
        $sql = 'SELECT A.appropriation_id FROM '.
            kernel::database()->prefix.'taoguanallocate_appropriation as A LEFT JOIN '.
            kernel::database()->prefix.'taoguanallocate_appropriation_items as I ON A.appropriation_id=I.appropriation_id '.
            'WHERE A.type=\'1\' and  A.operator_name=\''.$op_name.'\' and I.from_branch_id=\''.$branch.
            '\' and A.create_time>=\''.$time.'\' and A.create_time<\''.($time+86400).'\'';
        $row = $this->db->select($sql);
        if($row){
            return intval($row[0]['appropriation_id']);
        }else{
            return 0;
        }
    }

    /**
     * 删除调拔单
     * 
     */
    function deleteAppropriation($appropriation_id) {
        $db = kernel::database();
        $db->beginTransaction();
        $db->exec('delete from sdb_taoguanallocate_appropriation WHERE appropriation_id='.$appropriation_id);
        $db->exec('delete from sdb_taoguanallocate_appropriation_items WHERE appropriation_id='.$appropriation_id);
        $iostockorder = app::get('taoguaniostockorder')->model('iso')->dump(array('original_id'=>$appropriation_id,'type_id'=>40),'confirm,iso_id');
        if ($iostockorder['confirm']=='N'){
            $db->exec('delete from sdb_taoguaniostockorder_iso WHERE iso_id='.$iostockorder['iso_id']);
            $db->exec('delete from sdb_taoguaniostockorder_iso_items WHERE iso_id='.$iostockorder['iso_id']);
            $db->commit();
            return true;
        }else{
            $db->rollBack();
            return false;
        }
        
    }
    #获取调拨单号
    function getAppropriatioInfo($iso_id= null){
        $sql = 'select
                    appropriation_no
                from  sdb_taoguanallocate_appropriation appropriation
                left join sdb_taoguaniostockorder_iso  iso
                on iso.original_id=appropriation.appropriation_id
                where iso.iso_id='.$iso_id;
        $appropriation_no = $this->db->selectRow($sql);
        return $appropriation_no;
    }

    /**
     * 一键审核调拔单
     * @param int $appropriation_id 调拨单id
     * @param string $err_msg 提示信息
     * @return bool
     */
    public function oneClickCheck($appropriation_id,&$err_msg)
    {
        $filter['appropriation_id'] = $appropriation_id;
        $filter['process_status']   = array('1');
        $appropriation_id           = $filter['appropriation_id'];
        $is_have                    = $this->getlist('*', $filter);
    
        if (empty($is_have)) {
            $err_msg = '此单据不需要审核';
            return false;
        }
        kernel::database()->beginTransaction();
        
        $approMdl   = app::get('taoguanallocate')->model('appropriation');
        $appros = $approMdl->db_dump(array('appropriation_id'=>$appropriation_id),'bill_type');
        $affect_rows = $this->update(array('process_status' => 2, 'confirm_time' => time()),
            $filter);
    
        if (is_bool($affect_rows)) {
            kernel::database()->rollBack();
            $err_msg = '此单据审核失败!';
            return false;
        }
        if(in_array($appros['bill_type'] ,array('returnnormal'))){
           
        }
        
        $iso_id = kernel::single('console_receipt_allocate')->do_out_iostockorder($appropriation_id, $err_msg);
        if (!$iso_id) {
            kernel::database()->rollBack();

            return false;
        }
        kernel::database()->commit();

        kernel::single('console_iostockorder')->doCkeck($iso_id, 0);
        
        return true;
    }
}

?>
