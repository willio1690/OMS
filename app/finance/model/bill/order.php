<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class finance_mdl_bill_order extends dbeav_model{

    var $has_export_cnf = true;

    var $export_name = '订单收支明细';

    var $defaultOrder = array('create_time',' desc');
    /**
     * 搜索Options
     * @return mixed 返回值
     */
    public function searchOptions()
    {
        return array(
            'order_bn'=>'业务订单号',
            );
    }

    /**
     * 获取_schema
     * @return mixed 返回结果
     */
    public function get_schema()
    {
        $schema['columns'] = array(
            'bill_id' => 
            array (
                'type' => 'int unsigned',
                'required' => true,
                'pkey' => true,
                'extra' => 'auto_increment',
                'editable' => false,
                ),
            'order_bn' => 
            array (
                'type' => 'varchar(32)',
                'required' => true,
                'label' => '业务订单号',
                'width' => 130,
                'searchtype' => 'nequal',
                'editable' => false,
                'in_list' => true,
                'default_in_list' => true,
                ),
            'channel_name' => 
            array (
                'type' => 'varchar(32)',
                'required' => true,
                'label' => '渠道名称',
                'width' => 130,
                'searchtype' => 'nequal',
                'editable' => false,
                'in_list' => true,
                'default_in_list' => true,
                ),
            'fee_item' => array (
                'type' => 'varchar(255)',
                'label' => '费用项',
                'width' =>130,
                'in_list'=>false,
                'default_in_list'=>false,
                ),
            'fee_obj' => array (
                'type' => 'varchar(255)',
                'label' => '费用对象',
                'width' =>130,
                'in_list'=>false,
                'default_in_list'=>false,
                ),
            'money' => 
            array (
                'type' => 'money',
                'required' => true,
                'comment' => '金额',
                'editable' => false,
                'in_list' => false,
                'default_in_list' => false,
                ),
            'credential_number' => 
            array (
                'type' => 'varchar(64)',
                'label'=>'凭据号',
                'comment' => '凭据号',
                'editable' => false,
                'searchtype' => 'nequal',
                'in_list' => false,
                'default_in_list' => false,
                ),
            'trade_time' => 
            array (
                'type' => 'time',
                'label' => '日期',
                'editable' => false,
                'in_list' => false,
                'default_in_list' => false,
                ),
            'create_time' => 
            array (
                'type' => 'time',
                'comment' => '单据生成时间',
                'required'=>true,
                'editable' => false,
                'in_list' => false,
                'default_in_list' => false,
                ),
            );
        foreach ($schema['columns'] as $col=>$detail){
            if ($detail['in_list']){
                $schema['in_list'][] = $col;
            }
            if ($detail['default_in_list']){
                $schema['default_in_list'][] = $col;
            }
        }
        $schema['idColumn'] = 'bill_id';  
        return $schema;
    }

    /**
     * table_name
     * @param mixed $real real
     * @return mixed 返回值
     */
    public function table_name($real=false)
    {
        $table_name = "bill";
        if($real){
            return kernel::database()->prefix.$this->app->app_id.'_'.$table_name;
        }else{
            return $table_name;
        }
    }
    
    //配置信息
    /**
     * export_params
     * @return mixed 返回值
     */
    public function export_params(){
        $export_filter = $this->export_filter;
        if ($filter = unserialize($_POST['params'])) {
            $dates = $filter['time_from'].'至'.$filter['time_to'];
        }
        if($export_filter['shop_id']) $filter['channel_id'] = $export_filter['shop_id'];
        if($export_filter['order_bn']) $filter['order_bn'] = $export_filter['order_bn'];
        $params = array(
            'filter' => $filter,
            'limit' => 2000,
            'get_data_method' => 'get_bill_data',
            'single'=> array(
                'bill'=> array(
                    'filename' => $dates.'订单明细导出',
                    ),
                ),
            );
        return $params;
    }

    //title
    /**
     * 获取_bill_data_title
     * @return mixed 返回结果
     */
    public function get_bill_data_title(){
        $title['bill'] = array(
            '*:订单号',
            '*:货款',
            '*:平台费用',
            '*:仓储费用',
            '*:物流费用',
            '*:其他费用',
            '*:合计金额',
            );
        return $title;
    }

    //商品销售汇总
    /**
     * 获取_bill_data
     * @param mixed $filter filter
     * @param mixed $offset offset
     * @param mixed $limit limit
     * @param mixed $data 数据
     * @return mixed 返回结果
     */
    public function get_bill_data($filter,$offset,$limit,&$data){
        $billObj = $this->app->model('bill');
        $billdata = $this->getlist_order_bill('*',$filter,$offset,$limit);
        $time_from = strtotime($filter['time_from']." 00:00:00");
        $time_to = strtotime($filter['time_to']." 23:59:59");
        if(!empty($billdata)){
            foreach($billdata as $v){
                $tmp = kernel::single('finance_bill')->get_total_money_order_bn($v['order_bn'],$time_from,$time_to);
                $data['bill'][] = array(
                    '*:订单号' => $v['order_bn'],
                    '*:货款' => $tmp['trade'] ? $tmp['trade'] : 0,
                    '*:平台费用' => $tmp['plat'] ? $tmp['plat'] : 0,
                    '*:仓储费用' => $tmp['branch'] ? $tmp['branch'] : 0,
                    '*:物流费用' => $tmp['delivery'] ? $tmp['delivery'] : 0,
                    '*:其他费用' => $tmp['other'] ? $tmp['other'] : 0,
                    '*:合计金额' => $tmp['total'] ? $tmp['total'] : 0,
                    );
            }
        }
    }

    /*
    **订单账单finder方法重写
    */

    public function count_order_bill($filter=null){
        $sql = 'SELECT count(*) FROM (SELECT `order_bn` FROM '.$this->table_name(1).' where '.$this->_filter_order_bill($filter)." group by order_bn) as c";
        $count = $this->db->count($sql);
        return $count;
    }

    /*
    **订单账单finder方法重写
    */
    public function getlist_order_bill($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null){
        if(!$cols){
            $cols = $this->defaultCols;
        }
        if(!empty($this->appendCols)){
            $cols.=','.$this->appendCols;
        }
        if($this->use_meta){
            $meta_info = $this->prepare_select($cols);
        }
        $orderType = $orderType?$orderType:$this->defaultOrder;
        $sql = 'SELECT '.$cols.' FROM '.$this->table_name(1).' where '.$this->_filter_order_bill($filter)." group by order_bn";
        if($orderType)$sql.=' ORDER BY '.(is_array($orderType)?implode($orderType,' '):$orderType);
        $data = $this->db->selectLimit($sql,$limit,$offset);
        return $data;
    }

    /**
     * _filter_order_bill
     * @param mixed $filter filter
     * @return mixed 返回值
     */
    public function _filter_order_bill($filter){
        if(isset($filter['time_from']) && $filter['time_from']){
            $where .= ' AND `trade_time` >='.strtotime($filter['time_from']);
        }
        unset($filter['time_from']);
        
        if(isset($filter['time_to']) && $filter['time_to']){
            $where .= ' AND `trade_time` <'.(strtotime($filter['time_to'])+86400);
        }
        unset($filter['time_to']);

        if(isset($filter['shop_id']) && $filter['shop_id']){
            $where .= ' AND `channel_id` =\''.$filter['shop_id']."'";
        }
        unset($filter['shop_id']);

        if (!$filter['channel_id']) {
            unset($filter['channel_id']);
        }

        return parent::_filter($filter, $tableAlias, $baseWhere).$where;
    }

    /**
     * io_title
     * @param mixed $filter filter
     * @param mixed $ioType ioType
     * @return mixed 返回值
     */
    public function io_title( $filter=null,$ioType='csv' ){
      switch( $ioType ){
          case 'csv':
          default:
              $this->oSchema['csv']['main'] = array(
                0 => '*:订单号',
                1 => '*:货款',
                2 => '*:平台费用',
                3 => '*:仓储费用',
                4 => '*:物流费用',
                5 => '*:其他费用',
                6 => '*:合计金额',
              );
              break;
      }
      return $this->oSchema[$ioType][$filter];
    }

    /**
     * fgetlist
     * @param mixed $data 数据
     * @param mixed $filter filter
     * @param mixed $offset offset
     * @param mixed $exportType exportType
     * @return mixed 返回值
     */
    public function fgetlist( &$data,$filter,$offset,$exportType = 1 ){
      $max_offset = 1000;                       // 最多一次导出10w条记录
      if ($offset > $max_offset) return false;  // 限制导出的最大页码数

      if( !$data['title'] ){
        $title = $this->io_title('main');
        $data['1'][] = $this->io_title('main');
      }
      $limit = 100;
      $billObj = $this->app->model('bill');
      $billdata = $this->getlist_order_bill('*',$filter,$offset*$limit,$limit);
      if($billdata){
          foreach($billdata as $v){
            $tmp = kernel::single('finance_bill')->get_total_money_order_bn($v['order_bn']);
            $content = array(
                0 => $v['order_bn'],
                1 => $tmp['trade'],
                2 => $tmp['plat'],
                3 => $tmp['branch'],
                4 => $tmp['delivery'],
                5 => $tmp['other'],
                6 => $tmp['total'],
            );

            $data['1'][] = $content;
          }

          return true;
      }

      return false;
    }

    /**
     * support_io
     * @param mixed $ioType ioType
     * @return mixed 返回值
     */
    public function support_io(&$ioType)
    {
        $ioType = array( 'csv' => '.csv');
    }

    /**
     * fcount_csv
     * @param mixed $filter filter
     * @return mixed 返回值
     */
    public function fcount_csv($filter = NULL)
    {
        return $this->count_order_bill($filter);
    }

    /**
     * fgetlist_csv
     * @param mixed $data 数据
     * @param mixed $filter filter
     * @param mixed $offset offset
     * @param mixed $exportType exportType
     * @return mixed 返回值
     */
    public function fgetlist_csv( &$data,$filter,$offset,$exportType = 1 )
    {
        $max_offset = 1000; // 最多一次导出10w条记录
        if ($offset>$max_offset) return false;// 限制导出的最大页码数

        if( !$data['title']['bill'] ){
            $title = array();
            foreach( $this->io_title('main') as $k => $v ){
                $title[] = $this->charset->utf2local($v);
            }
            $data['title']['bill'] = implode(',',$title);
        }

        $limit = 100;
        $billObj = $this->app->model('bill');
        $billdata = $this->getlist_order_bill('*',$filter,$offset*$limit,$limit);
        if($billdata){
          foreach($billdata as $v){
            $tmp = kernel::single('finance_bill')->get_total_money_order_bn($v['order_bn']);
            $content = array(
                0 => $v['order_bn'],
                1 => $tmp['trade'],
                2 => $tmp['plat'],
                3 => $tmp['branch'],
                4 => $tmp['delivery'],
                5 => $tmp['other'],
                6 => $tmp['total'],
            );

            $data['content']['bill'][] = $this->charset->utf2local(implode( ',', $content ));
          }

          return true;
        }

        return false;
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
     * @param mixed $filename filename
     * @param mixed $filter filter
     * @return mixed 返回值
     */
    public function exportName(&$filename,$filter='')
    {
        $filename = '订单收支明细'.$filter['time_from'].'_'.$filter['time_to'];
        $this->export_name = $filename;
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
        $type = 'finance';
        if ($logParams['app'] == 'omecsv' && $logParams['ctl'] == 'admin_export') {
            $type .= '_financeReport_orderRevExpen_detail';
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
        $type = 'finance';
        $type .= '_import';
        return $type;
    }

    //根据查询条件获取导出数据
    public function getExportDataByCustom($fields, $filter, $has_detail, $curr_sheet, $start, $end){
        
        //根据选择的字段定义导出的第一行标题
        if($curr_sheet == 1){
            $data['content']['main'][] = $this->getExportTitle($fields);
        }

        $billObj = $this->app->model('bill');
        if(!$billdata = $this->getlist_order_bill('*', $filter, $start, $end)){
            return false;
        }

        foreach($billdata as $v){
            $tmp = kernel::single('finance_bill')->get_total_money_order_bn($v['order_bn']);

            $billdataRow['order_bn'] = $v['order_bn'];
            $billdataRow['channel_name'] = $v['channel_name'];
            $billdataRow['column_trade'] = $tmp['trade'];
            $billdataRow['column_plat'] = $tmp['plat'];
            $billdataRow['column_branch'] = $tmp['branch'];
            $billdataRow['column_delivery'] = $tmp['delivery'];
            $billdataRow['column_other'] = $tmp['other'];
            $billdataRow['column_total'] = $tmp['total'];

            $exptmp_data = array();
            foreach (explode(',', $fields) as $key => $col) {
                if(isset($billdataRow[$col])){
                    $billdataRow[$col] = mb_convert_encoding($billdataRow[$col], 'GBK', 'UTF-8');
                    $exptmp_data[] = $billdataRow[$col];
                }
            }

            $data['content']['main'][] = implode(',', $exptmp_data);
        }

        return $data;
    }

    /**
     * 获取ExportTitle
     * @param mixed $fields fields
     * @return mixed 返回结果
     */
    public function getExportTitle($fields){
        $export_columns = array(
            'order_bn' => '*:业务订单号',
            'channel_name' => '*:渠道名称',
            'column_trade' => '*:销售收支款',
            'column_plat' => '*:平台费用',
            'column_branch' => '*:仓储费用',
            'column_delivery' => '*:物流费用',
            'column_other' => '*:其他费用',
            'column_total' => '*:合计金额',
        );

        $title = array();
        foreach( explode(',', $fields) as $k => $col ){
            if(isset($export_columns[$col])){
                $title[] = $export_columns[$col];
            }
        }
        
        return mb_convert_encoding(implode(',',$title), 'GBK', 'UTF-8');
    }
}
