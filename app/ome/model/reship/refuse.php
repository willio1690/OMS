<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_mdl_reship_refuse extends ome_mdl_reship{
    
    /**
     * table_name
     * @param mixed $real real
     * @return mixed 返回值
     */
    public function table_name($real=false){
        
        $table_name = 'reship';
       
    if($real){
           
        return kernel::database()->prefix.'ome_'.$table_name;
        
    }else{
            
        return $table_name;
        }
    
    }
    
   /*
     * 拒收单,增加导出。
    */
    function io_title( $filter=null,$ioType='csv' ){
        switch( $ioType ){
            case 'refuse':
                $this->oSchema['csv']['refuse'] = array(
                '*:订单号'=>'order_id',
                '*:来源店铺' => 'shop_id',
                '*:收货地区' => 'ship_area',
                '*:收货地址' => 'ship_addr',
                '*:收货人' => 'ship_name',
                '*:收货人手机' => 'ship_tel',
                '*:备注' => 'memo',
                '*:退回物流公司' => 'return_logi_name',
                '*:退回物流单号' => 'return_logi_no',
                '*:退换货状态' => 'return_type',
                '*:退换货单号' => 'reship_bn',
                '*:操作员' => 'op_id',
                '*:单据创建时间'=>'t_begin'
                        );
              break;
            case 'items':
                $this->oSchema['csv']['items'] = array(
                '*:订单号' => 'order_id',
                '*:货号' => 'bn',
                '*:货品名称' => 'name',
                
                '*:数量' => 'num',
                    '*:货位'=>'store_position',
                '*:良品' => 'normal_num',
                '*:不良品' => 'defective_num',
                );
              break;
            default:
        }
        $this->ioTitle['csv'][$ioType] = array_keys( $this->oSchema['csv'][$ioType] );
        return $this->ioTitle['csv'][$ioType];
    }
    
    function fgetlist_csv( &$data,$filter,$offset,$exportType = 1 ){
        if( !$data['title']['refuse'] ){
            $title = array();
            foreach( $this->io_title('refuse') as $k => $v ){
                $title[] = $this->charset->utf2local($v);
            }
            $data['title']['refuse'] = '"'.implode('","',$title).'"';
        }
        if( !$data['title']['items'] ){
            $title = array();
            foreach( $this->io_title('items') as $k => $v ){
                $title[] = $this->charset->utf2local($v);
            }
            $data['title']['items'] = '"'.implode('","',$title).'"';
        }
       $branch_product_posObj =  app::get('ome')->model('branch_product_pos');
       $oReship = app::get('ome')->model('reship');
        $limit = 100;
        if( !$list = $this->getList('reship_id',$filter,$offset*$limit,$limit) )return false;
        foreach( $list as $aFilter ){
            $refuse_Order = $this->dump($aFilter['reship_id'],'reship_id,order_id,shop_id,ship_area,ship_addr,ship_name,ship_tel,memo,return_logi_name,return_logi_name,return_logi_no,return_type,reship_bn,op_id,t_begin');
            #订单号
            $po = app::get('ome')->model('orders')->dump($refuse_Order['order_id']);
            $refuse_Order['return_logi_no'] = "=\"\"".$refuse_Order['return_logi_no']."\"\"";
            $refuse_Order['order_id'] = "=\"\"".$po['order_bn']."\"\"";//"\t".$po['order_bn'];
            #处理明细
            $reship_item = $oReship->getItemList($refuse_Order['reship_id']);
            foreach ($reship_item as $key => $value) {
                
                $items['order_id'] = $refuse_Order['order_id'];
                
                $items['bn'] = $value['bn'];
                
                $items['name'] = $value['product_name'];
                
                $items['num'] = $value['num'];
            
                $pos_string ='';
                $posLists = $branch_product_posObj->get_pos($value['product_id'], $value['branch_id']);
                if(count($posLists) > 0){
                    foreach($posLists as $pos){
                        $pos_string[]= trim($pos['store_position']);
                    }
                    $items['store_position'] = implode(',',$pos_string);
                }else{
                    $items['store_position'] = '-';
                }
 
                    $items['normal_num'] = $value['normal_num'];
            $items['defective_num'] = $value['defective_num'];
                    $data['content']['items'][] = $this->charset->utf2local('"'.implode( '","', $items).'"');
            }
            #退换货状态
            switch ($refuse_Order['return_type']){
                case 'return':
                    $refuse_Order['return_type'] = '退货';
                    break;
                case 'change':
                    $refuse_Order['return_type'] = '换货';
                    break;
                case 'refuse':
                    $refuse_Order['return_type'] = '拒收退货';
                    break;
            }
            $refuse_Order['reship_bn'] =  "=\"\"".$refuse_Order['reship_bn']."\"\"";//"\t".$refuse_Order['reship_bn'];
            #操作员
            $po = app::get('pam')->model('account')->dump($refuse_Order['op_id']);
            $refuse_Order['op_id'] = $po['login_name'];
    
            #处理备注
             $refuse_Order['memo'] = kernel::single('ome_func')->format_memo($refuse_Order['memo']);
             if(!empty($refuse_Order['memo'])){
               foreach($refuse_Order['memo'] as $k => $v){
                   $arr[]= $v['op_content'];
                 }
                 $refuse_Order['memo'] = implode(',',$arr);
             }
            $shop = app::get('ome')->model('shop')->dump($refuse_Order['shop_id'],'name');
            $refuse_Order['shop_id'] = $shop['name'];
            $refuse_Order['t_begin'] = date('Y-m-d H:i:s',$refuse_Order['t_begin']);
            
            foreach( $this->oSchema['csv']['refuse'] as $k => $v ){
                $orderRow[$k] = $this->charset->utf2local(utils::apath( $refuse_Order,explode('/',$v) ));
            }
            
            $data['content']['refuse'][] = '"'.implode('","',$orderRow).'"';
        }
        $data['name'] = '拒收单'.date("Ymd");
        return true;
    }
    
    function export_csv($data,$exportType = 1 ){
        $output = array();
        foreach( $data['title'] as $k => $val ){
            $output[] = $val."\n".implode("\n",(array)$data['content'][$k]);
        }
        echo implode("\n",$output);
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
        $type= 'afterSale_reship_refuse_export';
        return $type;
    }
    /**
     * 导入操作日志类型
     * @param Array $logParams 日志参数
     */
    public function importLogType($logParams) {
        $params = $logParams['params'];
        $type = 'order';
        $type .= '_import';
        return $type;
    }

    /**
     * 根据查询条件获取导出数据
     * @Author: xueding
     * @Vsersion: 2022/5/25 上午10:35
     * @param $fields
     * @param $filter
     * @param $has_detail
     * @param $curr_sheet
     * @param $start
     * @param $end
     * @param $op_id
     * @return bool
     */
    public function getExportDataByCustom($fields, $filter, $has_detail, $curr_sheet, $start, $end, $op_id)
    {
        $params = [
            'fields'     => $fields,
            'filter'     => $filter,
            'has_detail' => $has_detail,
            'curr_sheet' => $curr_sheet,
            'op_id'      => $op_id,
        ];
        
        $reshipListData = kernel::single('ome_func')->exportDataMain(__CLASS__, $params);
        if (!$reshipListData) {
            return false;
        }
        
        //根据选择的字段定义导出的第一行标题
        if ($curr_sheet == 1) {
            $data['content']['main'][] = $this->getCustomExportTitle($reshipListData['title']);
        }
        
        $reship_items_columns = array_values($this->reshipItemsExportTitle());
        
        $reship_ids   = array_column($reshipListData['content'], 'reship_id');
        $main_columns = array_values($reshipListData['title']);
        $reshipList   = $reshipListData['content'];
        //所有的子销售数据
        $reship_items = $this->getexportdetail('*', array('reship_id' => $reship_ids));
        foreach ($reshipList as $reshipRow) {
            $objects      = $reship_items[$reshipRow['reship_id']];
            $items_fields = implode(',', $reship_items_columns);
            $all_fields   = implode(',', $main_columns) . ',' . $items_fields;
            if ($objects) {
                foreach ($objects as $obj) {
                    $reshipDataRow = array_merge($reshipRow, $obj);
                    $exptmp_data   = [];
                    foreach (explode(',', $all_fields) as $key => $col) {
                        if (isset($reshipDataRow[$col])) {
                            $reshipDataRow[$col] = mb_convert_encoding($reshipDataRow[$col], 'GBK', 'UTF-8');
                            $exptmp_data[]       = $reshipDataRow[$col];
                        } else {
                            $exptmp_data[] = '';
                        }
                    }
                    $data['content']['main'][] = implode(',', $exptmp_data);
                }
            }
        }
        return $data;
    }
    
    /**
     * 获取CustomExportTitle
     * @param mixed $main_title main_title
     * @return mixed 返回结果
     */
    public function getCustomExportTitle($main_title)
    {
        $main_title        = array_keys($main_title);
        $order_items_title = array_keys($this->reshipItemsExportTitle());
        $title             = array_merge($main_title, $order_items_title);
        return mb_convert_encoding(implode(',', $title), 'GBK', 'UTF-8');
    }
    
    
    /**
     * reshipItemsExportTitle
     * @return mixed 返回值
     */
    public function reshipItemsExportTitle()
    {
        $items_title = array(
            '商品货号' => 'bn',
            '仓库名称' => 'branch_name',
            '商品名称' => 'product_name',
            '明细退货类型' => 'item_return_type',
            '申请数量' => 'num',
            '良品'   => 'normal_num',
            '不良品'  => 'defective_num',
            'GAP'  => 'gap',
        );
        return $items_title;
    } 

    /**
     * 获取exportdetail
     * @param mixed $fields fields
     * @param mixed $filter filter
     * @param mixed $offset offset
     * @param mixed $limit limit
     * @param mixed $has_title has_title
     * @return mixed 返回结果
     */
    public function getexportdetail($fields,$filter,$offset=0,$limit=1,$has_title=false)
    {
        $reship_arr = $this->getList('reship_bn,reship_id,order_id', array('reship_id' => $filter['reship_id']), 0, -1);
        foreach ($reship_arr as $reship) {
            $reship_bn[$reship['reship_id']] = $reship['reship_bn'];
        }
        
        $Obranch = app::get('ome')->model('branch');
        $branchs = $Obranch->getList('branch_id,name');
        foreach ($branchs as $v) {
            $branch[$v['branch_id']] = $v['name'];
        }
        unset($branchs);

        $reshipItemsObj = app::get('ome')->model('reship_items');

        //按升序导出(与列表中排序保持一致)
        $reship_items_arr = $reshipItemsObj->getList('*', array('reship_id'=>$filter['reship_id']), 0, -1, 'reship_id DESC');
        $return_type = [
            'return' => '退货',
            'change' => '换货',
            'refuse' => '拒收退货',
        ];
        $row_num = 1;
        if($reship_items_arr){
            foreach ($reship_items_arr as $key => $reship_item) {
                $reshipItemRow['bn']       = $reship_item['bn'];
                $reshipItemRow['branch_name']       = isset($branch[$reship_item['branch_id']]) ? $branch[$reship_item['branch_id']] : '-';
                $reshipItemRow['product_name']   = $reship_item['product_name'];
                $reshipItemRow['item_return_type'] = $return_type[$reship_item['return_type']] ?? '';
                $reshipItemRow['num']   = $reship_item['num'];
                $reshipItemRow['normal_num']   = $reship_item['normal_num'];
                $reshipItemRow['defective_num']   = $reship_item['defective_num'];
                $reshipItemRow['gap']   = $reship_item['return_type'] == 'return' ? $reship_item['num'] - $reship_item['normal_num'] - $reship_item['defective_num'] : 0;
                
                $data[$reship_item['reship_id']][] = $reshipItemRow;
                $row_num++;
            }
        }

        //明细标题处理
        if($data && $has_title){
            $title = array(
                '*:商品货号',
                '*:仓库名称',
                '*:商品名称',
                '*:明细退货类型',
                '*:申请数量',
                '*:良品',
                '*:不良品',
                '*:GAP',
            );

            foreach ((array)$title as $key => $value) {
                $title[$key] = mb_convert_encoding($value, 'GBK', 'UTF-8');
            }

            $data[0] = implode(',', $title);
        }

        ksort($data);
        return $data;
    }

   
}
?>