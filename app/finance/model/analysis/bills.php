<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class finance_mdl_analysis_bills extends dbeav_model{

    var $has_export_cnf = true;

    var $export_name = '交易费用';

    var $defaultOrder = array('gmt_create',' desc');

    /**
     * _filter
     * @param mixed $filter filter
     * @param mixed $tableAlias tableAlias
     * @param mixed $baseWhere baseWhere
     * @return mixed 返回值
     */
    public function _filter($filter,$tableAlias=null,$baseWhere=null)
    {
        if ($filter['time_from'] && $filter['time_to']) {
            $time_type = 'book_time';
            if($filter['time_type'] == '1'){
                $time_type = 'book_time';
            } elseif ($filter['time_type'] == '2'){
                $time_type = 'biz_time';
            }
            $filter["{$time_type}|between"][0] =  strtotime($filter['time_from']);
            $filter["{$time_type}|between"][1] =  strtotime($filter['time_to']);
            unset($filter['time_from'],$filter['time_to'],$filter['time_type']);
        }
        
        return parent::_filter($filter,$tableAlias,$baseWhere);
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
     * io_title
     * @param mixed $filter filter
     * @param mixed $ioType ioType
     * @return mixed 返回值
     */
    public function io_title( $filter=null,$ioType='csv' ){
        switch( $ioType ){
            case 'csv':
            default:
                $title = array(
                    0 => '*:科目名称',
                    1 => '*:流水账户类型',
                    2 => '*:流水类型',
                    3 => '*:流水编号',
                    4 => '*:账单编号',
                    5 => '*:交易订单编号',
                    6 => '*:交易子订单编号',
                    7 => '*:商品数字编码',
                    8 => '*:交易金额',
                    9 => '*:账单金额',
                    10 => '*:业务时间',
                    11 => '*:支付账户',
                    12 => '*:目标支付账户',
                    13 => '*:支付交易号',
                    14 => '*:支付商户订单号',
                    15 => '*:支付备注',
                    16 => '*:支付状态',
                    17 => '*:支付时间',
                    18 => '*:创建时间',
                    19 => '*:修改时间',
                );
            break;
        }
        return $title;
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
            foreach( $this->io_title() as $k => $v ){
                $title[] = $this->charset->utf2local($v);
            }
            $data['title']['bill'] = implode(',',$title);
        }

        $feeItemModel = app::get('finance')->model('bill_fee_item');
        $limit = 100;
        $billdata = $this->getList('*',$filter,$offset*$limit,$limit);
        if($billdata){
          foreach($billdata as $v){
            // 获取科目
            $fee_item = $feeItemModel->dump($v['fee_item_id']);
            $content = array(
                0 => $v['fee_item'],
                1 => '支付宝',
                2 => $this->schema['columns']['finance_type']['type'][$v['finance_type']],
                3 => '',
                4 => $v['bid'],
                5 => $v['tid'],
                6 => $v['oid'],
                7 => $v['num_iid'],
                8 => $v['total_amount'],
                9 => $v['amount'],
                10 => date('Y-m-d H:i:s',$v['biz_time']),
                11 => $v['alipay_mail'],
                12 => $v['obj_alipay_mail'],
                13 => $v['alipay_no'],
                14 => $v['alipay_outno'],
                15 => $v['alipay_notice'],
                16 => $this->schema['columns']['status']['type'][$v['status']],
                17 => date('Y-m-d H:i:s',$v['pay_time']),
                18 => date('Y-m-d H:i:s',$v['gmt_create']),
                19 => date('Y-m-d H:i:s',$v['gmt_modified']),
            );

            $data['content']['bill'][] = $this->charset->utf2local(implode( ',', $content ));
          }

          return true;
        }

        return false;
    }

    /**
     * export_csv
     * @param mixed $data 数据
     * @param mixed $exportType exportType
     * @return mixed 返回值
     */
    public function export_csv($data,$exportType = 1 ){
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
        $filename = '财务费用'.$filter['time_from'].'_'.$filter['time_to'];
        $this->export_name = $filename;
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
        $max_offset = 1000; // 最多一次导出10w条记录
        if ($offset>$max_offset) return false;// 限制导出的最大页码数

        if( $offset == 0 ){
            // $title = array();
            // foreach( $this->io_title() as $k => $v ){
            //     $title[] = $this->charset->utf2local($v);
            // }
            $data['1'][] = $this->io_title();
        }

        $feeItemModel = app::get('finance')->model('bill_fee_item');
        $limit = 100;
        $billdata = $this->getList('*',$filter,$offset*$limit,$limit);
        if($billdata){
          foreach($billdata as $v){
            // 获取科目
            $fee_item = $feeItemModel->dump($v['fee_item_id']);
            $content = array(
                0 => $fee_item['fee_item'],
                1 => '支付宝',
                2 => $this->schema['columns']['finance_type']['type'][$v['finance_type']],
                3 => '',
                4 => $v['bid'],
                5 => $v['tid'],
                6 => $v['oid'],
                7 => $v['num_iid'],
                8 => $v['total_amount'],
                9 => $v['amount'],
                10 => date('Y-m-d H:i:s',$v['biz_time']),
                11 => $v['alipay_mail'],
                12 => $v['obj_alipay_mail'],
                13 => $v['alipay_no'],
                14 => $v['alipay_outno'],
                15 => $v['alipay_notice'],
                16 => $this->schema['columns']['status']['type'][$v['status']],
                17 => date('Y-m-d H:i:s',$v['pay_time']),
                18 => date('Y-m-d H:i:s',$v['gmt_create']),
                19 => date('Y-m-d H:i:s',$v['gmt_modified']),
            );

            $data['1'][] = $content;
          }

          return true;
        }

        return false;
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
        $type = 'report';
        if ($logParams['app'] == 'omecsv' && $logParams['ctl'] == 'admin_export') {
            $type .= '_purchaseReport_costAnalysis';
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
        $type = 'report';
        if ($logParams['app'] == 'omecsv' && $logParams['ctl'] == 'admin_export') {
            $type .= '_purchaseReport_costAnalysis';
        }
        $type .= '_import';
        return $type;
    }

    //根据查询条件获取导出数据
    public function getExportDataByCustom($fields, $filter, $has_detail, $curr_sheet, $start, $end){
        
        //根据选择的字段定义导出的第一行标题
        if($curr_sheet == 1){
            $data['content']['main'][] = $this->getExportTitle($fields);
        }

        $feeItemModel = app::get('finance')->model('bill_fee_item');

        if(!$billdata = $this->getList('*', $filter, $start, $end)){
            return false;
        }

        $oShop = &app::get('ome')->model('shop');
        $rs = $oShop->getList('shop_id,name');
        foreach($rs as $v) {
            $shops[$v['shop_id']] = $v;
        }

        foreach($billdata as $v){
            // 获取科目
            $fee_item = $feeItemModel->dump($v['fee_item_id']);
            $billdataRow['fee_item_id'] = $fee_item['fee_item'];
            $billdataRow['tid'] = $v['tid'];
            $billdataRow['oid'] = $v['oid'];
            $billdataRow['biz_time'] = date('Y-m-d H:i:s',$v['biz_time']);
            $billdataRow['total_amount'] = $v['total_amount'];
            $billdataRow['amount'] = $v['amount'];
            $billdataRow['pay_time'] = date('Y-m-d H:i:s',$v['pay_time']);
            $billdataRow['obj_alipay_mail'] = $v['obj_alipay_mail'];
            $billdataRow['book_time'] = date('Y-m-d H:i:s',$v['book_time']);
            $billdataRow['status'] = $this->schema['columns']['status']['type'][$v['status']];
            $billdataRow['shop_id'] = $shops[$v['shop_id']]['name'];

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
            'fee_item_id' => '*:科目名称',
            'tid' => '*:交易订单编号',
            'oid' => '*:交易子订单编号',
            'biz_time' => '*:订单交易完成的时间',
            'total_amount' => '*:订单交易金额',
            'amount' => '*:费用账单金额',
            'pay_time' => '*:费用支付时间',
            'obj_alipay_mail' => '*:目标支付宝账户名称',
            'book_time' => '*:费用记账时间',
            'status' => '*:状态',
            'shop_id' => '*:店铺名称',
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
