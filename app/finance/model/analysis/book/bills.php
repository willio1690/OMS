<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class finance_mdl_analysis_book_bills extends dbeav_model{

    var $has_export_cnf = true;

    var $export_name = '固定费用';

    var $defaultOrder = array('gmt_create',' desc');
    
    public $journal_types = array(
        101 => '可用金充值',
        102 => '可用金扣除',
        103 => '冻结',
        104 => '解冻',
        105 => '冻结金充值',
        106 => '冻结金扣除',
    );

    /**
     * modifier_journal_type
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function modifier_journal_type($row){
        return $this->journal_types[$row];
    }

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
            $filter['book_time|between'][0] =  strtotime($filter['time_from']);
            $filter['book_time|between'][1] =  strtotime($filter['time_to']);
            unset($filter['time_from'],$filter['time_to']);
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
                    0 => '*:虚拟账户流水编号',
                    1 => '*:流水类型',
                    2 => '*:操作金额',
                    3 => '*:记账时间',
                    4 => '*:备注',
                    5 => '*:创建时间',
                    6 => '*:店铺名称',
                    7 => '*:科目名称',
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
            $shop = kernel::single('finance_func')->getShopByShopID($v['shop_id']);
            $content = array(
                0 => $v['bid'],
                1 => $this->journal_types[$v['journal_type']],
                2 => $v['amount'],
                3 => date('Y-m-d H:i:s',$v['book_time']),
                4 => $v['description'],
                5 => date('Y-m-d H:i:s',$v['gmt_create']),
                6 => $shop['name'],
                7 => $fee_item['fee_item'],
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
        $filename = '财务非订单费用'.$filter['time_from'].'_'.$filter['time_to'];
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
            $shop = kernel::single('finance_func')->getShopByShopID($v['shop_id']);

            $content = array(
                0 => $v['bid'],
                1 => $this->journal_types[$v['journal_type']],
                2 => $v['amount'],
                3 => date('Y-m-d H:i:s',$v['book_time']),
                4 => $v['description'],
                5 => date('Y-m-d H:i:s',$v['gmt_create']),
                6 => $shop['name'],
                7 => $fee_item['fee_item'],
            );

            $data['1'][] = $content;
          }

          return true;
        }

        return false;
    }

    //根据查询条件获取导出数据
    /**
     * 获取ExportDataByCustom
     * @param mixed $fields fields
     * @param mixed $filter filter
     * @param mixed $has_detail has_detail
     * @param mixed $curr_sheet curr_sheet
     * @param mixed $start start
     * @param mixed $end end
     * @return mixed 返回结果
     */
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
            $billdataRow['journal_type'] = $this->journal_types[$v['journal_type']];
            $billdataRow['amount'] = $v['amount'];
            $billdataRow['gmt_create'] = date('Y-m-d H:i:s',$v['gmt_create']);
            $billdataRow['book_time'] = date('Y-m-d H:i:s',$v['book_time']);
            $billdataRow['bid'] = $v['bid'];
            $billdataRow['shop_type'] = $v['shop_type'];
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
            'journal_type' => '*:流水类型',
            'amount' => '*:操作金额',
            'gmt_create' => '*:创建时间',
            'book_time' => '*:记账时间',
            'bid' => '*:费用流水编号',
            'shop_type' => '*:店铺类型',
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
