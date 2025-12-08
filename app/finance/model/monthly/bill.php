<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class finance_mdl_monthly_bill extends dbeav_model{

    var $defaultOrder = array('order_bn DESC');
    public $filter_use_like = true;

    var $export_name = '账期报表';

    /**
     * table_name
     * @param mixed $real real
     * @return mixed 返回值
     */
    public function table_name($real=false){
        $tableName = 'bill';
        return $real ? kernel::database()->prefix.'finance_'.$tableName : $tableName;

    }

    /**
     * modifier_shop_id
     * @param mixed $val val
     * @return mixed 返回值
     */
    public function modifier_shop_id($val)
    {
        if(!isset($this->shop_name[$val])){
            $row = app::get('ome')->model('shop')->getList('name',array('shop_id'=>$val),0,1);
            if($row){
                $this->shop_name[$val] = $row[0]['name'];
            }else{
                return '';
            }
            
        }
        return $this->shop_name[$val];
    }

    /**
     * 搜索Options
     * @return mixed 返回值
     */
    public function searchOptions(){
        return array(
            'order_bn' => '订单号'
        );
    }


    /**
     * count
     * @param mixed $filter filter
     * @return mixed 返回值
     */
    public function count($filter=null){

        $where = " `monthly_id` = {$filter['monthly_id']} and charge_status = 1 ";

        isset($filter['status'])   and $where .= " and status = {$filter['status']} ";
        isset($filter['order_bn']) and $where .= " and order_bn = '{$filter['order_bn']}' ";

        $sql = " select count(*) as _count from (
            select * from ( 
                (select `order_bn` from ".kernel::database()->prefix."finance_ar where {$where}  group by order_bn) 
                union
                (select `order_bn` from ".kernel::database()->prefix."finance_bill where {$where}  group by order_bn) 
            ) as monthly_bill 
            group by order_bn order by order_bn ) as monthly_bill_count
        ";
        $row = $this->db->select($sql);
        return intval($row[0]['_count']);
    }

    public function getList($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null){

        $monthlyId = isset($filter['monthly_id']) ? $filter['monthly_id'] : 0;

        $where = " `monthly_id` = {$monthlyId} and charge_status = 1  ";

        isset($filter['status']) and $where .= " and status = {$filter['status']} ";
        isset($filter['order_bn']) and $where .= " and order_bn = '{$filter['order_bn']}' ";

        $sql = "
            select order_bn from ( 
                (select `order_bn` from ".kernel::database()->prefix."finance_ar where $where  group by order_bn) 
                union
                (select `order_bn` from ".kernel::database()->prefix."finance_bill where $where  group by order_bn) 
            ) as monthly_bill 
            group by order_bn order by order_bn
        ";

        $data = $this->db->selectLimit($sql,$limit,$offset);


        if($data)
        {

            $orderBnKey = array_column($data,'order_bn');
            $yingshou = $yingtui = $shishou = $shitui = array();

            // 获取销售出库
            $sql = "select order_bn,sum(money) as amount, premonthly_id, gap_type,memo from ".kernel::database()->prefix."finance_ar where order_bn in ('".implode("','", $orderBnKey)."') and {$where} and `ar_type`='0'  group by order_bn ";
            $yingshou = $this->db->select($sql);
            $yingshou and $yingshou = array_column($yingshou,null,'order_bn');


            // 获取销售退货
            $sql = "select order_bn,sum(money) as amount, premonthly_id, gap_type from ".kernel::database()->prefix."finance_ar where order_bn in ('".implode("','", $orderBnKey)."') and {$where} and `ar_type`='1'  group by order_bn ";
            $yingtui = $this->db->select($sql);
            $yingtui and $yingtui = array_column($yingtui,null,'order_bn');

            $monthly_report = array ();
            if ($premonthly_id = array_merge (array_column((array)$yingshou, 'premonthly_id'), array_column((array) $yingtui, 'premonthly_id')) ) {
                $monthly_report = app::get('finance')->model('monthly_report')->getList('monthly_id, monthly_date', array ('monthly_id' => $premonthly_id));

                $monthly_report = array_column($monthly_report, null, 'monthly_id');
            }

            // 获取平台收入
            $sql = "select order_bn,sum(money) as amount, gap_type from ".kernel::database()->prefix."finance_bill where order_bn in ('".implode("','", $orderBnKey)."') and {$where} and `bill_type`='0'  group by order_bn ";
            $shishou = $this->db->select($sql);
            $shishou and $shishou = array_column($shishou,null,'order_bn');

            // 获取平台支出
            $sql = "select order_bn,sum(money) as amount, gap_type from ".kernel::database()->prefix."finance_bill where order_bn in ('".implode("','", $orderBnKey)."') and {$where} and `bill_type`='1'  group by order_bn ";
            $shitui = $this->db->select($sql);
            $shitui and $shitui = array_column($shitui,null,'order_bn');


            foreach ($data as &$v) {
                $order_yingshou = $yingshou[$v['order_bn']];
                $order_yingtui  = $yingtui[$v['order_bn']];
                $order_shishou  = $shishou[$v['order_bn']];
                $order_shitui   = $shitui[$v['order_bn']];


                $premonthly_id = $order_yingshou['premonthly_id']?$order_yingshou['premonthly_id']: $order_yingtui['premonthly_id'];

                if (!$premonthly_id) $premonthly_id = $monthlyId;

                $monthly_date = str_replace('账期', '', $monthly_report[$premonthly_id]['monthly_date']);
                if ($premonthly_id && $premonthly_id != $monthlyId) {
                    $monthly_date .= '未核销';
                }

                $v['yingshou_money'] = (float)$order_yingshou['amount'];
                $v['yingtui_money']  = (float)$order_yingtui['amount'];
                $v['shishou_money']  = (float)$order_shishou['amount'];
                $v['shitui_money']   = (float)$order_shitui['amount'];
                $v['monthly_id']     = $monthlyId;
                
                $v['monthly_date']  = $monthly_date;
                $v['xiaotui_total'] = $v['yingshou_money'] + $v['yingtui_money'];
                $v['shouzhi_total'] = $v['shishou_money'] + $v['shitui_money'];
                $v['GAP']           = $v['shouzhi_total'] - $v['xiaotui_total'];
                $v['gap_type']      = max($order_yingshou['gap_type'],$order_yingtui['gap_type'],$order_shishou['gap_type'],$order_shitui['gap_type']);
                $v['memo']          = $order_yingshou['memo'];
            }

        }

        $this->tidy_data($data, $cols);
        
        return $data;
    }


    /**
     * 获取_schema
     * @return mixed 返回结果
     */
    public function get_schema(){

        $columns = array();

        $columns['monthly_date']   = array('type'=>'varchar(32)','label'=>'帐期','comment'=>'帐期','width'=>200,'order'=>9);
        $columns['order_bn']       = array('type'=>'varchar(32)','label'=>'订单号','comment'=>'订单号','searchtype'=>'nequal','width'=>200,'order'=>10);
        $columns['yingshou_money'] = array('type'=>'money','label'=>'销售出库','comment'=>'所有销售出库','width'=>150,'order'=>20);
        $columns['yingtui_money']  = array('type'=>'money','label'=>'销售退货','comment'=>'所有销售退货','width'=>150,'order'=>30);
        $columns['xiaotui_total']  = array('type'=>'money','label'=>'销退合计','comment'=>'所有销退合计','width'=>150,'order'=>31);

        $columns['shishou_money']  = array('type'=>'money','label'=>'平台收入','comment'=>'所有平台收入','width'=>150,'order'=>40);
        $columns['shitui_money']   = array('type'=>'money','label'=>'平台支出','comment'=>'所有平台支出','width'=>150,'order'=>50);
        $columns['shouzhi_total']  = array('type'=>'money','label'=>'收支合计','comment'=>'所有收支合计','width'=>150,'order'=>60);
        $columns['GAP']            = array('type'=>'money','label'=>'GAP','comment'=>'GAP','width'=>150,'order'=>70);
        $columns['gap_type']     = array('type'=>'varchar(32)','label'=>'差异类型','comment'=>'销退收支差异类型','width'=>150,'order'=>80);
        $columns['memo']     = array('type'=>'longtext','label'=>'核销备注','comment'=>'核销备注','width'=>150,'order'=>90);

        // $columns['total_ar_money'] = array('type'=>'money','label'=>'应收应退总费用','comment'=>'所有应收应退','width'=>150,'order'=>30);
        // $columns['bill_money'] = array('type'=>'money','label'=>'本期流水费用','comment'=>'本期实收实退','width'=>150,'order'=>40);
        // $columns['ar_money'] = array('type'=>'money','label'=>'本期应收应退费用','comment'=>'本期应收应退','width'=>150,'order'=>50);
        // $columns['shop_id'] = array('type'=>'varchar(32)','label'=>'所属店铺','comment'=>'所属店铺','width'=>150,'order'=>60);

        $schema['columns'] = $columns;
        $schema['idColumn'] = 'order_bn';
        $schema['in_list'] = array_keys($columns);
        $schema['default_in_list'] = array_keys($columns);

        return $schema;
    }


    /**
     * _filter
     * @param mixed $filter filter
     * @param mixed $tableAlias tableAlias
     * @param mixed $baseWhere baseWhere
     * @return mixed 返回值
     */
    public function _filter($filter, $tableAlias = NULL, $baseWhere = NULL){
        $where = '';
        if(isset($filter['status']) ){
            $where .= ' AND `status` =\''.$filter['status']."'";
        }
        unset($filter['status']);

        return parent::_filter($filter, $tableAlias, $baseWhere).$where;
    }

    /**
     * exportName
     * @param mixed $filename filename
     * @param mixed $filter filter
     * @return mixed 返回值
     */
    public function exportName(&$filename,$filter)
    {
        return $filename['name'] = "账期报表";
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
            $data['content']['main'][] = $this->getExportTitle('*');
        }

        if(!$list = $this->getList('*', $filter, $start, $end)){
            return false;
        }

        // $report = app::get('finance')->model('monthly_report')->db_dump($list[0]['monthly_id'],'monthly_date');

        foreach($list as $l){
            $content = array (
                'monthly_date'   => $l['monthly_date'],
                'order_bn'       => "\t".$l['order_bn'],
                'yingshou_money' => $l['yingshou_money'],
                'yingtui_money'  => $l['yingtui_money'],
                'shishou_money'  => $l['shishou_money'],
                'shitui_money'   => $l['shitui_money'],
                'xiaotui_total'  => $l['xiaotui_total'],
                'shouzhi_total'  => $l['shouzhi_total'],
                'GAP'            => $l['GAP'],
                'gap_type'       => $l['gap_type'],
                'memo'           => $l['memo'],
            );

            $data['content']['main'][] = mb_convert_encoding(implode(',',$content), 'GBK', 'UTF-8');
        }

        return $data;
    }

    /**
     * 获取ExportTitle
     * @param mixed $fields fields
     * @return mixed 返回结果
     */
    public function getExportTitle($fields){
        $title = array(
            'monthly_date'   => '*:账期名称',
            'order_bn'       => '*:订单号',
            'yingshou_money' => '*:销售出库',
            'yingtui_money'  => '*:销售退货',
            'shishou_money'  => '*:平台收入',
            'shitui_money'   => '*:平台支出',
            'xiaotui_total'  => '*:销退合计',
            'shouzhi_total'  => '*:收支合计',
            'GAP'            => '*:GAP',
            'gap_type'       => '*:gap_type',
            'memo'           => '*:备注',
        );

        return mb_convert_encoding(implode(',',$title), 'GBK', 'UTF-8');
    }


}
