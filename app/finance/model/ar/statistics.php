<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class finance_mdl_ar_statistics extends dbeav_model{

    var $has_export_cnf = true;

    var $export_name = '销售到账明细';

    public $filter_use_like = true;

    /**
     * table_name
     * @param mixed $real real
     * @return mixed 返回值
     */
    public function table_name($real=false){
        $tableName = 'ar';
        return $real ? kernel::database()->prefix.'finance_'.$tableName : $tableName;
    }

    /**
     * 获取_schema
     * @return mixed 返回结果
     */
    public function get_schema(){
        $schema = app::get('finance')->model('ar')->get_schema();
        $schema['columns']['order_bn']['label'] = app::get('finance')->_('业务订单号');
        $schema['columns']['channel_name']['label'] = app::get('finance')->_('渠道名称');
        $schema['columns']['money']['label'] = app::get('finance')->_('商品成交金额');

        foreach($schema['in_list'] as $k=>$v){
            if(in_array($v, array('charge_status','status','confirm_money','unconfirm_money','monthly_status'))){
                unset($schema['in_list'][$k]);
            }
        }

        foreach($schema['deafult_in_list'] as $k1=>$v1){
            if(in_array($v1, array('charge_status','status','confirm_money','unconfirm_money','monthly_status'))){
                unset($schema['deafult_in_list'][$k1]);
            }
        }
        return $schema;
    }

    /**
     * 搜索Options
     * @return mixed 返回值
     */
    public function searchOptions(){
        return array();
    }

    /**
     * _filter
     * @param mixed $filter filter
     * @param mixed $tableAlias tableAlias
     * @param mixed $baseWhere baseWhere
     * @return mixed 返回值
     */
    public function _filter($filter, $tableAlias=null, $baseWhere=null){
        if(isset($filter['shop_id']) && $filter['shop_id']!='0'){
            $where .= " AND channel_id = '".$filter['shop_id']."'";
        }
        unset($filter['shop_id']);

  /*       if(isset($filter['time_from']) && $filter['time_from']!='' && isset($filter['time_to']) && $filter['time_to']!=''){
            $where .= " AND (";
            $where .= "(trade_time >= ".strtotime($filter['time_from'].' 00:00:00')." AND trade_time <= ".strtotime($filter['time_to'].' 23:59:59').")";
            $where .= " OR ";
            $where .= "(trade_time < ".strtotime($filter['time_from'].' 00:00:00')." AND (verification_time >".strtotime($filter['time_from'].' 00:00:00')." OR verification_time = 0))";
            $where .= ")";
        } */
        if(isset($filter['time_from']) && $filter['time_from']!='' && isset($filter['time_to']) && $filter['time_to']!=''){
            $where .= " AND (";
            $where .= "(trade_time >= ".strtotime($filter['time_from'].' 00:00:00')." AND trade_time <= ".strtotime($filter['time_to'].' 23:59:59').")";
            $where .= ")";
        }
        unset($filter['time_from'],$filter['time_to']);

        if (!$filter['channel_id']) {
            unset($filter['channel_id']);
        }

        $filter['charge_status'] = 1;
        return parent::_filter($filter, $tableAlias, $baseWhere).$where;
    }

    /**
     * statistics_filter
     * @param mixed $filter filter
     * @param mixed $tableAlias tableAlias
     * @param mixed $baseWhere baseWhere
     * @return mixed 返回值
     */
    public function statistics_filter($filter, $tableAlias=null, $baseWhere=null){
        if(isset($filter['shop_id']) && $filter['shop_id']!='0'){
            $where .= " AND channel_id = '".$filter['shop_id']."'";
        }
        unset($filter['shop_id']);

        if(isset($filter['time_from']) && $filter['time_from']!='' && isset($filter['time_to']) && $filter['time_to']!=''){
            $where .= " AND (";
            $where .= "trade_time >= ".strtotime($filter['time_from'].' 00:00:00')." AND trade_time <= ".strtotime($filter['time_to'].' 23:59:59').")";
        }
        unset($filter['time_from'],$filter['time_to']);
        
        if (!$filter['channel_id']) {
            unset($filter['channel_id']);
        }

        $filter['charge_status'] = 1;
        return parent::_filter($filter, $tableAlias, $baseWhere).$where;
    }
    
    /**
     * export_params
     * @return mixed 返回值
     */
    public function export_params(){
        return kernel::single('finance_io_ar_statistics_export')->export_params($this);
    }
    /**
     * 获取_ar_statistics_title
     * @return mixed 返回结果
     */
    public function get_ar_statistics_title(){
        return kernel::single('finance_io_ar_statistics_export')->get_ar_statistics_title();
    }
    /**
     * 获取_ar_statistics
     * @param mixed $filter filter
     * @param mixed $offset offset
     * @param mixed $limit limit
     * @param mixed $data 数据
     * @return mixed 返回结果
     */
    public function get_ar_statistics($filter,$offset,$limit,&$data){
        kernel::single('finance_io_ar_statistics_export')->get_ar_statistics($this,$filter,$offset,$limit,$data);
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
                0 => '*:单据编号',
                1 => '*:账单日期',
                2 => '*:客户/会员',
                3 => '*:业务类型',
                4 => '*:订单号',
                5 => '*:店铺',
                6 => '*:明细数量',
                7 => '*:货款',
                8 => '*:运费',
                9 => '*:期初应收',
                10 => '*:本期应收',
                11 => '*:本期实收',
                12 => '*:期末应收',
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

        if( $offset == 0 ){
            $title = array();
            $data['1'][] = $this->io_title('main');
        }
        $time_from = strtotime($filter['time_from'].' 00:00:00');
        $limit = 100;
        $ar_statistics = $this->getList('*',$filter,$offset*$limit,$limit);
        if($ar_statistics){
            foreach($ar_statistics as $v){
                $addon = unserialize($v['addon']);
                $fee_money = number_format($addon['fee_money'],2,'.','0');
                $sql = "SELECT SUM(num) as nums FROM sdb_finance_ar_items WHERE ar_id =".$v['ar_id'];
                $nums = $this->db->select($sql);
                $itemNums = is_null($nums[0]['nums'])? 0 : $nums[0]['nums'];
                $content = array(
                    0 => $v['ar_bn'],
                    1 => date('Y-m-d H:i:s',$v['trade_time']),
                    2 => $v['member'],
                    3 => finance_ar::get_name_by_type($v['type']),
                    4 => $v['order_bn'],
                    5 => $v['channel_name'],
                    6 => $itemNums,
                    7 => $v['money'],
                    8 => $fee_money,
                    9 => $this->get_qcys($v['ar_id'],$time_from),
                    10 => $this->get_bqys($v['ar_id']),
                    11 => $this->get_bqss($v['ar_id']),
                    12 => $this->get_qmys($v['ar_id']),
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

        if( !$data['title']['ar'] ){
            $title = array();
            foreach( $this->io_title('main') as $k => $v ){
                $title[] = $this->charset->utf2local($v);
            }
            $data['title']['ar'] = implode(',',$title);
        }

        $time_from = strtotime($filter['time_from'].' 00:00:00');
        $limit = 100;
        $ar_statistics = $this->getList('*',$filter,$offset*$limit,$limit);
        if($ar_statistics){
            foreach($ar_statistics as $v){
                $addon = unserialize($v['addon']);
                $fee_money = number_format($addon['fee_money'],2,'.','0');
                $sql = "SELECT SUM(num) as nums FROM sdb_finance_ar_items WHERE ar_id =".$v['ar_id'];
                $nums = $this->db->select($sql);
                $itemNums = is_null($nums[0]['nums'])? 0 : $nums[0]['nums'];
                $content = array(
                    0 => $v['ar_bn'],
                    1 => date('Y-m-d H:i:s',$v['trade_time']),
                    2 => $v['member'],
                    3 => finance_ar::get_name_by_type($v['type']),
                    4 => $v['order_bn'],
                    5 => $v['channel_name'],
                    6 => $itemNums,
                    7 => $v['money'],
                    8 => $fee_money,
                    9 => $this->get_qcys($v['ar_id'],$time_from),
                    10 => $this->get_bqys($v['ar_id']),
                    11 => $this->get_bqss($v['ar_id']),
                    12 => $this->get_qmys($v['ar_id']),
                );
                $data['content']['ar'][] = $this->charset->utf2local(implode( ',', $content ));
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
     * 期初应收金额
     * 根据应收单据ID获取期初应收金额
     * @access public
     * @param Number $ar_id 应收单据ID
     * @return mixed decimal(10,2)
     */
    public function get_qcys($ar_id = '',$time_from = ''){
        $result = '0.00';
        if($ar_id == '') return $result;
        $ar_mdl = app::get('finance')->model('ar');
        $ar = $ar_mdl->getlist('*',array('ar_id'=>$ar_id),0,1);
        if($ar[0]['trade_time'] < $time_from){
            $arlist = app::get('finance')->model('ar')->getList('order_bn',array('ar_id' => $ar_id));
            $order_bn_arr = array();
            foreach($arlist as $v){
                $order_bn_arr[] = $v['order_bn'];
            }
            $billList = app::get('finance')->model('bill')->getList('bill_id',array('order_bn' => $order_bn_arr));
            $bill_id_arr = array();
            foreach ($billList as $v){
                $bill_id_arr[] = $v['bill_id'];
            }

            if($bill_id_arr){
                $sql = "SELECT SUM(money) as money FROM sdb_finance_verification_items WHERE bill_id in(".implode(',',$bill_id_arr).") AND type = 0 AND trade_time <".$time_from;
                $verification_item = kernel::database()->select($sql);
                $vimoney = $verification_item[0]['money'] >= 0 ? $verification_item[0]['money'] : 0;
                $result = sprintf("%01.2f",$ar[0]['money'] - $vimoney);
            }
        }
        return $result;
    }

    /**
     * 本期应收金额
     * 根据应收单据ID获取本期应收金额
     * @access public
     * @param Number $ar_id 应收单据ID
     * @return mixed decimal(10,2)
     */
    public function get_bqys($ar_id = ''){
        $result = '0.00';
        if($ar_id == '') return $result;
        $filter = $_POST;
        $filter['ar_id'] = $ar_id;
        $sql = "SELECT money FROM sdb_finance_ar WHERE ".$this->statistics_filter($filter);
        $ar = kernel::database()->select($sql);
        $result = $ar[0]['money'] ? $ar[0]['money'] : 0;
        return $result;
    }

    /**
     * 期末应收金额
     * 根据应收单据ID获取期末应收金额
     * @access public
     * @param Number $ar_id 应收单据ID
     * @return mixed decimal(10,2)
     */
    public function get_qmys($ar_id = ''){
        $result = '0.00';
        if($ar_id == '') return $result;
        $qcys = $this->get_qcys($ar_id);
        $bqys = $this->get_bqys($ar_id);
        $bqss = $this->get_bqss($ar_id);
        $result = sprintf("%01.2f",($qcys+$bqys)-$bqss);
        return $result;
    }

    /**
     * 本期实收金额
     * 根据应收单据ID获取本期实收金额
     * @access public
     * @param Number $ar_id 应收单据ID
     * @return mixed decimal(10,2)
     */
    public function get_bqss($ar_id = ''){
        $result = '0.00';
        if($ar_id == '') return $result;
        
        $arlist = app::get('finance')->model('ar')->getList('order_bn',array('ar_id' => $ar_id));
        $order_bn_arr = array();
        foreach($arlist as $v){
            $order_bn_arr[] = $v['order_bn'];
        }
        $billList = app::get('finance')->model('bill')->getList('bill_id',array('order_bn' => $order_bn_arr));
        $bill_id_arr = array();
        foreach ($billList as $v){
            $bill_id_arr[] = $v['bill_id'];
        }

        if($bill_id_arr){
            $sql = "SELECT SUM(money) as money FROM sdb_finance_verification_items WHERE bill_id in (".implode(',',$bill_id_arr).") AND type = 0";
            $verification_item = kernel::database()->select($sql);
            $vimoney = $verification_item[0]['money'] >= 0 ? $verification_item[0]['money'] : 0;
            $result = sprintf("%01.2f",$vimoney);
        }
        return $result;
    }

    function modifier_type($type){
        return kernel::single('finance_ar')->get_name_by_type($type);
    }

    function modifier_status($status){
        return kernel::single('finance_ar')->get_name_by_status($status);
    }

    function modifier_charge_status($charge_status){
        return kernel::single('finance_ar')->get_name_by_charge_status($charge_status);
    }

    function modifier_monthly_status($monthly_status){
        return kernel::single('finance_ar')->get_name_by_monthly_status($monthly_status);
    }

    /**
     * exportName
     * @param mixed $filename filename
     * @param mixed $filter filter
     * @return mixed 返回值
     */
    public function exportName(&$filename,$filter='')
    {
        $filename = '销售到账明细'.$filter['time_from'].'_'.$filter['time_to'];

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
            $type .= '_financeReport_saleToAccount_detail';
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

        $time_from = strtotime($filter['time_from'].' 00:00:00');

        if(!$ar_statistics = $this->getList('*', $filter, $start, $end)){
            return false;
        }

        foreach($ar_statistics as $v){
            $addon = unserialize($v['addon']);
            $fee_money = number_format($addon['fee_money'],2,'.','0');
            $sql = "SELECT SUM(num) as nums FROM sdb_finance_ar_items WHERE ar_id =".$v['ar_id'];
            $nums = $this->db->select($sql);
            $itemNums = is_null($nums[0]['nums'])? 0 : $nums[0]['nums'];

            $arstatisticsRow['ar_bn'] = $v['ar_bn'];
            $arstatisticsRow['channel_name'] = $v['channel_name'];
            $arstatisticsRow['trade_time'] = date('Y-m-d H:i:s',$v['trade_time']);
            $arstatisticsRow['member'] = $v['member'];
            $arstatisticsRow['type'] = finance_ar::get_name_by_type($v['type']);
            $arstatisticsRow['order_bn'] = $v['order_bn'];
            $arstatisticsRow['relate_order_bn'] = $v['relate_order_bn'];
            $arstatisticsRow['money'] = $v['money'];
            $arstatisticsRow['charge_time'] = date('Y-m-d H:i:s',$v['charge_time']);
            $arstatisticsRow['serial_number'] = $v['serial_number'];
            $arstatisticsRow['column_items_nums'] = $itemNums;
            $arstatisticsRow['column_qcys'] = $this->get_qcys($v['ar_id'],$time_from);
            $arstatisticsRow['column_qmys'] = $this->get_qmys($v['ar_id']);
            $arstatisticsRow['column_bqss'] = $this->get_bqss($v['ar_id']);
            $arstatisticsRow['column_bqys'] = $this->get_bqys($v['ar_id']);
            $arstatisticsRow['column_fee_money'] = $fee_money;

            $exptmp_data = array();
            foreach (explode(',', $fields) as $key => $col) {
                if(isset($arstatisticsRow[$col])){
                    $arstatisticsRow[$col] = mb_convert_encoding($arstatisticsRow[$col], 'GBK', 'UTF-8');
                    $exptmp_data[] = $arstatisticsRow[$col];
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
            'ar_bn' => '*:单据编号',
            'channel_name' => '*:渠道名称',
            'trade_time' => '*:账单日期',
            'member' => '*:客户/会员',
            'type' => '*:业务类型',
            'order_bn' => '*:业务订单号',
            'relate_order_bn' => '*:关联订单号',
            'money' => '*:商品成交金额',
            'charge_time' => '*:记账日期',
            'serial_number' => '*:业务流水号',
            'column_items_nums' => '*:商品总数量',
            'column_qcys' => '*:期初应收',
            'column_qmys' => '*:期末应收',
            'column_bqss' => '*:本期实收',
            'column_bqys' => '*:本期应收',
            'column_fee_money' => '*:运费收入',
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
