<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class tgkpi_mdl_pick extends dbeav_model{

    function searchOptions(){
        return array(
            'pick_owner' =>'拣货员工号',
            'check_op_id' => '校验员名称',
            'product_bn' => '货号',
            'delivery_bn' => '发货单',
            'logi_no' => '物流单号',
        );
    }

    function modifier_cost_time($row){
        $hour = floor($row/3600);
        $min = floor(($row-$hour*3600)/60);
        $sec = $row-$hour*3600-$min*60;

        $str ='';
        $str = $hour>0 ? $hour."小时" : '';
        $str.= $min>0 ? $min."分" : '';
        $str.= $sec>0 ? $sec."秒" : '';
        return $str;
    }

    function modifier_check_cost_time($row){
        $hour = floor($row/3600);
        $min = floor(($row-$hour*3600)/60);
        $sec = $row-$hour*3600-$min*60;

        $str ='';
        $str = $hour>0 ? $hour."小时" : '';
        $str.= $min>0 ? $min."分" : '';
        $str.= $sec>0 ? $sec."秒" : '';
        return $str;
    }

    // 记录校验完成的时间
    /**
     * finish_pick
     * @param mixed $delivery_id ID
     * @return mixed 返回值
     */
    public function finish_pick($delivery_id){
        $pick_end_time = time();
        $pick_status = 'finish';
        $sql = "UPDATE sdb_tgkpi_pick SET pick_status='$pick_status',pick_end_time=$pick_end_time,check_cost_time=$pick_end_time-check_start_time WHERE delivery_id=$delivery_id";
        $this->db->exec($sql);
    }

    //记录校验开始时间
    /**
     * begin_check
     * @param mixed $delivery_id ID
     * @return mixed 返回值
     */
    public function begin_check($delivery_id){
        $opInfo = kernel::single('ome_func')->getDesktopUser();
        $check_start_time = time();
        $sql = "UPDATE sdb_tgkpi_pick SET check_start_time=".$check_start_time.",check_op_id='".$opInfo['op_id']."',check_op_name='".$opInfo['op_name']."',cost_time=".$check_start_time."-pick_start_time WHERE delivery_id=".$delivery_id."";
        $this->db->exec($sql);
    }

    /**
     * @description 检验错误
     * @access public
     * @param BigInt $delivery_id 发货单号
     * @return void
     */
    public function pick_error($delivery_id,$product_bn)
    {
        $sql = 'UPDATE '.$this->table_name(true).' SET pick_error_num=pick_error_num+1 WHERE delivery_id='.$delivery_id.' AND product_bn='.$product_bn;
        $this->db->exec($sql);

        //增加发货单捡货完成日志
        $opObj = app::get('ome')->model('operation_log');
        $opObj->write_log('delivery_pick@ome', $delivery_id, '错拣发货单'.$delivery_id.'中'.$product_bn.'货品');
    }

    function get_picker(){
        $sql = "SELECT distinct(pick_owner) FROM sdb_tgkpi_pick";
        $rs = $this->db->select($sql);
        foreach((array)$rs as $v){
            $res[] = array(
                'type_id'=>$v['pick_owner'],
                'name'=>$v['pick_owner']
            );
        }
        //var_dump($res);
        return $res;
    }

    function get_deliverys($filter){
        //$filter = parent::_filter($filter);
        $sql = "select count(distinct delivery_id) as total from sdb_tgkpi_pick
        where (pick_status='finish' or pick_status='deliveryed') AND pick_start_time between ".strtotime($filter['time_from'])." AND ".strtotime($filter['time_to'])."
        ";
        //if($filter['pick_nums']) $sql .= ' AND pick_num>100';
        //echo('<pre>');var_dump($sql);
        $rs = $this->db->select($sql);
        return $rs[0]['total'];
    }
    #获取已经完成发货单量
    function get_deliveryed($filter){
        $sql = "select 
                    count(distinct delivery_id) as total from sdb_tgkpi_pick
                where pick_status='deliveryed' AND pick_start_time between ".strtotime($filter['time_from'])." AND ".strtotime($filter['time_to'])."";
        $rs = $this->db->selectRow($sql);
        return $rs['total'];
    }

    function get_pick_nums($filter){
        $sql = "select sum(pick_num) as total from sdb_tgkpi_pick
        where (pick_status='finish' or pick_status='deliveryed') AND check_start_time  between ".strtotime($filter['time_from'])." AND ".strtotime($filter['time_to'])."
        ";
        $rs = $this->db->select($sql);
        return $rs[0]['total'];
    }

    /**
     * @description 获取检货图表数据
     * @access public
     * @param Array $filter
     * @return Array
     */
    public function getChartData($filter=array())
    {
        $chartData = array();

        $where = array(1);
        if (isset($filter['start_time'])) {
            $where[] = 'pick_start_time>='.$filter['start_time'];
        }

        if (isset($filter['end_time'])) {
            $where[] = 'pick_start_time<='.$filter['end_time'];
        }

        // 获取检货员工 as categories
        $sql = 'SELECT DISTINCT pick_owner FROM '.$this->table_name(true).' WHERE 1 AND '.implode(' AND ',$where);
        $pickers = $this->db->select($sql);
        if (empty($pickers)) {
            return $chartData;
        }

        $pickers = array_map('current',$pickers);

        //获取拣货员姓名
        $sql = 'SELECT name,op_no FROM sdb_desktop_users WHERE op_no in(\''.implode('\',\'',$pickers).'\')';
        $pickers = $this->db->select($sql);

        // 获取拣货剩余件数、获取剩余单数
        $sql = 'SELECT COUNT(DISTINCT delivery_id) AS _ldeliveryId , SUM(pick_num) AS _lpickNum,pick_owner FROM '.$this->table_name(true).' WHERE pick_status=\'running\' AND '.implode(' AND ',$where).' GROUP BY pick_owner';
        $lData = $this->db->select($sql);
        $_lData = array();
        foreach ($lData as $key=>$value) {
            $value['pick_owner'] = strtoupper(trim($value['pick_owner']));
            $_lData[$value['pick_owner']] = $value;
        }
        unset($lData);

        // 获取拣货完成货品数数、完成单数
        $sql = 'SELECT COUNT(DISTINCT delivery_id) AS _fdeliveryId , SUM(pick_num) AS _fpickNum , pick_owner FROM '.$this->table_name(true). ' WHERE  (pick_status=\'finish\'  or pick_status=\'deliveryed\') AND '.implode(' AND ',$where).' GROUP BY pick_owner';
        $fData = $this->db->select($sql);
        $_fData = array();
        foreach ($fData as $key=>$value) {
            $value['pick_owner'] = strtoupper(trim($value['pick_owner']));
            $_fData[$value['pick_owner']] = $value;
        }
        unset($fData);


        foreach ($pickers as $key=>$value) {
            $value['op_no'] = strtoupper(trim($value['op_no']));
            $chartData['categories'][] = $value['name'];
            $chartData['series']['fpickNum'][] = $_fData[$value['op_no']]['_fpickNum'] ?  intval($_fData[$value['op_no']]['_fpickNum']) : 0;
            $chartData['series']['lpickNum'][] = $_lData[$value['op_no']]['_lpickNum'] ?  intval($_lData[$value['op_no']]['_lpickNum']) : 0;
            $chartData['series']['fdeliveryId'][] = $_fData[$value['op_no']]['_fdeliveryId'] ?  intval($_fData[$value['op_no']]['_fdeliveryId']) : 0;
            $chartData['series']['ldeliveryId'][] = $_lData[$value['op_no']]['_ldeliveryId'] ?  intval($_lData[$value['op_no']]['_ldeliveryId']) : 0;
        }

        return $chartData;
    }

    /**
     * @description 获取校验图表数据
     * @access public
     * @param Array $filter
     * @return Array
     */
    public function getCheckChartData($filter=array())
    {
        $chartData = array();
        $checkers = array();

        $where = array(1);
        if (isset($filter['start_time'])) {
            $where[] = 'pick_start_time>='.$filter['start_time'];
        }

        if (isset($filter['end_time'])) {
            $where[] = 'pick_start_time<='.$filter['end_time'];
        }

        // 获取校验剩余件数、获取剩余单数
        $sql = 'SELECT COUNT(DISTINCT delivery_id) AS _ldeliveryId , SUM(pick_num) AS _lpickNum , check_op_name FROM '.$this->table_name(true).' WHERE pick_status=\'checking\' AND check_op_id >0 AND '.implode(' AND ',$where).' GROUP BY check_op_id';
        $lData = $this->db->select($sql);
        $_lData = array();
        foreach ($lData as $key=>$value) {
            $value['check_op_name'] = strtoupper(trim($value['check_op_name']));
            $_lData[$value['check_op_name']] = $value;
            if(!in_array($value['check_op_name'], $checkers)){
                $checkers[] = $value['check_op_name'];
            }
        }
        unset($lData);

        // 获取校验完成货品数、完成单数
        $sql = 'SELECT COUNT(DISTINCT delivery_id) AS _fdeliveryId , SUM(pick_num) AS _fpickNum , check_op_name FROM '.$this->table_name(true). ' WHERE (pick_status=\'finish\'  or pick_status=\'deliveryed\') AND check_op_id >0 AND '.implode(' AND ',$where).' GROUP BY check_op_id';
        $fData = $this->db->select($sql);
        $_fData = array();
        foreach ($fData as $key=>$value) {
            $value['check_op_name'] = strtoupper(trim($value['check_op_name']));
            $_fData[$value['check_op_name']] = $value;
            if(!in_array($value['check_op_name'], $checkers)){
                $checkers[] = $value['check_op_name'];
            }
        }
        unset($fData);

        foreach ($checkers as $checker) {
            $chartData['categories'][] = $checker;
            $chartData['series']['fpickNum'][] = $_fData[$checker]['_fpickNum'] ?  intval($_fData[$checker]['_fpickNum']) : 0;
            $chartData['series']['lpickNum'][] = $_lData[$checker]['_lpickNum'] ?  intval($_lData[$checker]['_lpickNum']) : 0;
            $chartData['series']['fdeliveryId'][] = $_fData[$checker]['_fdeliveryId'] ?  intval($_fData[$checker]['_fdeliveryId']) : 0;
            $chartData['series']['ldeliveryId'][] = $_lData[$checker]['_ldeliveryId'] ?  intval($_lData[$checker]['_ldeliveryId']) : 0;
        }

        return $chartData;
    }

    /**
     * _filter
     * @param mixed $filter filter
     * @param mixed $tableAlias tableAlias
     * @param mixed $baseWhere baseWhere
     * @return mixed 返回值
     */
    public function _filter($filter,$tableAlias=null,$baseWhere=null){
        if (isset($filter['delivery_bn']) && !empty($filter['delivery_bn'])){
            $deliveryObj = app::get('ome')->model("delivery");
            $rows = $deliveryObj->getList('delivery_id',array('delivery_bn'=>$filter['delivery_bn']));
            foreach($rows as $row){
                $deliveryId[] = $row['delivery_id'];
            }
            $where .= '  AND delivery_id IN ('.implode(',', $deliveryId).')';
            unset($filter['delivery_bn']);
        }
        if(isset($filter['logi_no']) && !empty($filter['logi_no'])){
            $deliveryObj = app::get('ome')->model("delivery");
            $rows = $deliveryObj->getList('delivery_id',array('logi_no'=>$filter['logi_no']));
            foreach($rows as $row){
                $deliveryId[] = $row['delivery_id'];
            }
            $where .= '  AND delivery_id IN ('.implode(',', $deliveryId).')';
            unset($filter['logi_no']);
        }
        return parent::_filter($filter,$tableAlias,$baseWhere).$where;
    }

}
