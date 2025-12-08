<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class tgkpi_mdl_analysis_check extends dbeav_model{

    private $realTableName = 'sdb_tgkpi_pick';

    /**
     * count
     * @param mixed $filter filter
     * @return mixed 返回值
     */
    public function count($filter=null){
        $f = kernel::single('tgkpi_analysis_pick')->dealTime($filter);
        $filter['time_from'] = sprintf('%s 00:00:00',$f['time_from']);
        $filter['time_to'] = sprintf('%s 23:59:59',$f['time_to']);
        $filter['time_from'] = strtotime($filter['time_from']);
        $filter['time_to'] = strtotime($filter['time_to']);
        $sql = "SELECT count(distinct pick_owner) as total FROM sdb_tgkpi_pick where ";
        $sql .= $this->pickFilter($filter);
        $rs = $this->db->select($sql);
        return intval($rs[0]['total']);
    }

    /**
     * @description 导出名
     * @access public
     * @param Array $data
     * @return void
     */
    public function exportName(&$data)
    {  $post = $_POST;
        $data['name'] = $this->app->_('校验');

        $params = kernel::single('tgkpi_analysis_check')->dealTime($post);
        if (!empty($params['time_from'])) {
            $data['name'] .= str_replace('-','',$params['time_from']);
        }
        if (!empty($params['time_to'])) {
            $data['name'] .= '-'.str_replace('-','',$params['time_to']);
        }
    }

    /**
     * @description where过滤条件
     * @access public
     * @param Array $filter
     * @return String
     */
    public function pickFilter($filter)
    {
        $where = array(1);
        if ($filter['time_from'] && $filter['time_to']) {
            $where[] = ' check_start_time between '.$filter['time_from'].' AND '.$filter['time_to'];
        }

        if ($filter['pick_status']) {
            if($filter['pick_status'] == 'finish'){
                $where[] = " (pick_status='finish' or pick_status='deliveryed') ";
            }else{
                $where[] = ' pick_status=\''.$filter['pick_status'].'\'';
            }           
        }

        if ($filter['check_owner']) {
            if (is_array($filter['check_owner'])) {
                $where[] = ' check_op_name in(\''.implode('\',\'',$filter['check_owner']).'\')';
            }elseif(is_string($filter['pick_owner'])){
                $where[] = ' check_op_name=\''.$filter['check_owner'].'\'';
            }
        }


        if ($filter['sku']=='single') {
             $where[] = ' delivery_sku_num=1';
        }elseif($filter['sku']=='multi'){
             $where[] = ' delivery_sku_num>1';
        }


        return implode(' AND ',$where);
    }

    /**
     * @description 获取单商品单数
     * @access public
     * @param Array $filter
     * @return Array $single
     */
    public function getSingleSku($filter)
    {
        $filter['sku'] = 'single';
        $sql = 'SELECT COUNT(DISTINCT delivery_id) as singleSkuTotal , check_op_name FROM '.$this->realTableName.' WHERE ';
        $sql .= $this->pickFilter($filter);
        $sql .= ' GROUP BY pick_owner';

        $single = array();
        $rs = $this->db->select($sql);
        foreach($rs as $v) {
            $v['check_op_name'] = trim($v['check_op_name']);
            $single[$v['check_op_name']] = $v;
        }
        unset($rs);

        return $single;
    }

    /**
     * @description 获取多商品单数
     * @access public
     * @param Array $filter
     * @return Array $multi
     */
    public function getMultiSku($filter)
    {
        $filter['sku'] = 'multi';
        $sql = 'SELECT COUNT(DISTINCT delivery_id) as multiSkuTotal,check_op_name,SUM(pick_num) as pickBnTotal FROM '.$this->realTableName.' WHERE ';
        $sql .= $this->pickFilter($filter);
        $sql .= ' GROUP BY check_op_id';

        $multi = array();
        $rs = $this->db->select($sql);
        foreach($rs as $v) {
            $v['check_op_name'] = trim($v['check_op_name']);
            $multi[$v['check_op_name']] = $v;
        }
        unset($rs);

        return $multi;
    }

    /**
     * @description 获取完成的单数
     * @access public
     * @param Array $filter
     * @return Array $finish
     */
    public function getFinishSku($filter)
    {
        $filter['pick_status'] = 'finish';
        // 完成单数统计
        $sql = 'SELECT COUNT(DISTINCT delivery_id) as _cdelivery , SUM(pick_num) as _cpick , check_op_name FROM '.$this->realTableName.' WHERE ';
        $sql .= $this->pickFilter($filter);
        $sql .= ' GROUP BY check_op_id';

        $finish = array();
        $rs = $this->db->select($sql);
        foreach ($rs as $key=>$value) {
            $value['check_op_name'] = trim($value['check_op_name']);
            $finish[$value['check_op_name']] = $value;
        }

        return $finish;
    }

    public function getlist($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null){
        $f = kernel::single('tgkpi_analysis_check')->dealTime($filter);
        $filter['time_from'] = sprintf('%s 00:00:00',$f['time_from']);
        $filter['time_to'] = sprintf('%s 23:59:59',$f['time_to']);
        $filter['time_from'] = strtotime($filter['time_from']);
        $filter['time_to'] = strtotime($filter['time_to']);

        $sql = "SELECT check_op_name as checker , COUNT(DISTINCT delivery_id) as deliveryTotal , SUM(pick_num) as pickTotal from ".$this->realTableName." where ";
        $sql .= $this->pickFilter($filter);
        $sql .= ' GROUP BY check_op_id';
        if($orderType){
            if (is_string($orderType)) {
                $ot = $orderType;
                $this->ot = array_filter(explode(' ',trim($ot)));
                list($columns,$taxis) = $this->ot;
                if ($columns == 'checker') {
                    $sql.=' ORDER BY '.(is_array($orderType) ? implode(' ',$orderType) : $orderType);
                }
            }
        }


        $rs = $this->db->selectLimit($sql,$limit,$offset);
        if (empty($rs)) {
            return array();
        }
        $all = $rs; unset($rs);

        foreach ($all as $value) {
            $checkOwners[] = $value['checker'];
        }
        $filter['check_owner'] = $checkOwners;unset($checkOwners);

        //单商品单数
        $single = $this->getSingleSku($filter);

        //多商品单数
        $multi = $this->getMultiSku($filter);

        // 完成单数统计
        $finish = $this->getFinishSku($filter);

        $data = array();
        foreach ($all as $key=>$value) {
            $checker = trim($value['checker']);
            $data[$key]['name']                          = $checker;
            $data[$key]['deliverys']                      = $finish[$checker]['_cdelivery'] ? $finish[$checker]['_cdelivery'] : 0;
            $data[$key]['pick_nums']                   = $finish[$checker]['_cpick'] ? $finish[$checker]['_cpick'] : 0;
            $data[$key]['single_nums']                 = $single[$checker]['singleSkuTotal'] ? $single[$checker]['singleSkuTotal'] : 0;
            $data[$key]['multi_deliverys']              = $multi[$checker]['multiSkuTotal'] ? $multi[$checker]['multiSkuTotal'] : 0;
            $data[$key]['multi_nums']                  = $multi[$checker]['pickBnTotal'] ? $multi[$checker]['pickBnTotal'] : 0;
            $data[$key]['rate_of_single']               = $this->rate_format($data[$key]['single_nums'],$value['deliveryTotal']);
            $data[$key]['rate_of_singlecomplete']  = $this->rate_format($data[$key]['deliverys'],$value['deliveryTotal']);
            $data[$key]['rate_of_pickscomplete']   = $this->rate_format($data[$key]['pick_nums'],$value['pickTotal']);
        }
        unset($all);

        $this->tidy_data($data, $cols);
        return $data;
    }

    /**
     * @description 比率格式化
     * @access public
     * @param Float $numerator 分子
     * @param Float $denominator 分母
     * @return String
     */
    public function rate_format($numerator,$denominator)
    {
        $numerator = (float)$numerator;$denominator = (float)$denominator;
        try{
            $quotient = round($numerator/$denominator*100,2);
            $quotient = ''.$quotient;
            $l = strlen($quotient);
            if (false==strpos($quotient,'.')) {
                $f = '%.0f%%';
            }elseif(true==strpos($quotient,'.') && $quotient[$l-1]==0){
                $f = '%.1f%%';
            }else{
                $f = '%.2f%%';
            }
            return sprintf($f,$quotient);
        } catch (Exception $e) {
            //分母不能为零
            return sprintf('%d%%',0);
        }
    }

    /**
     * _filter
     * @param mixed $filter filter
     * @param mixed $tableAlias tableAlias
     * @param mixed $baseWhere baseWhere
     * @return mixed 返回值
     */
    public function _filter($filter,$tableAlias=null,$baseWhere=null){
        $config = app::get('eccommon')->getConf('analysis_config');
        $filter['order_status'] = $config['filter']['order_status'];
        $where = array(1);
        if(isset($filter['time_from']) && $filter['time_from']){
            $where[] = ' createtime >='.strtotime($filter['time_from']);
        }
        if(isset($filter['time_to']) && $filter['time_to']){
            $where[] = ' createtime <'.(strtotime($filter['time_to'])+86400);
        }
        return implode($where,' AND ');
    }

    /**
     * 获取_schema
     * @return mixed 返回结果
     */
    public function get_schema(){
        $schema = array (
            'columns' => array (
                'name' => array (
                    'type' => 'varchar(10)',
                    'pkey' => true,
                    'label' => '姓名',
                    'width' => 100,
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                    'order' => 20,
                ),
                'deliverys' => array (
                    'type' => 'number',
                    'label' => '总完成单数',
                    'width' => 80,
                    'editable' => true,
                    'filtertype' => 'normal',
                    'filterdefault' => 'true',
                    'in_list' => true,
                    'is_title' => true,
                    'default_in_list' => true,
                    'realtype' => 'varchar(50)',
                    'order' => 30,
                ),
                'pick_nums' => array (
                    'type' => 'number',
                    'label' => '总完成件数',
                    'width' => 80,
                    'editable' => true,
                    'filtertype' => 'normal',
                    'filterdefault' => 'true',
                    'in_list' => true,
                    'is_title' => true,
                    'default_in_list' => true,
                    'realtype' => 'varchar(50)',
                    'order' => 40,
                ),
                'single_nums' => array (
                    'type' => 'number',
                    'label' => '单商品单数',
                    'width' => 120,
                    'editable' => true,
                    'filtertype' => 'normal',
                    'filterdefault' => 'true',
                    'in_list' => true,
                    'is_title' => true,
                    'default_in_list' => true,
                    'realtype' => 'varchar(50)',
                    'order' => 50,
                ),
                'multi_deliverys' => array (
                    'type' => 'number',
                    'label' => '多商品单数',
                    'width' => 120,
                    'editable' => true,
                    'filtertype' => 'normal',
                    'filterdefault' => 'true',
                    'in_list' => true,
                    'is_title' => true,
                    'default_in_list' => true,
                    'realtype' => 'varchar(50)',
                    'order' => 60,
                ),
                'multi_nums' => array (
                    'type' => 'number',
                    'label' => '多商品件数',
                    'width' => 120,
                    'editable' => true,
                    'filtertype' => 'normal',
                    'filterdefault' => 'true',
                    'in_list' => true,
                    'is_title' => true,
                    'default_in_list' => true,
                    'realtype' => 'varchar(50)',
                    'order' => 70,
                ),
                'rate_of_single' => array(
                    'type' => 'float',
                    'label' => '单商品占比',
                    'width' => 'auto',
                    'editable' => false,
                    'filtertype' => 'normal',
                    'default' => 0,
                    'in_list' => true,
                    'default_in_list' => true,
                    'order' => 79,
                ),
                'rate_of_singlecomplete' => array(
                    'type' => 'float',
                    'label' => '单数完成率',
                    'width' => 'auto',
                    'editable' => false,
                    'filtertype' => 'normal',
                    'default' => 0,
                    'in_list' => true,
                    'default_in_list' => true,
                    'order' => 80,
                ),
                'rate_of_pickscomplete' => array(
                    'type' => 'float',
                    'label' => '件数完成率',
                    'width' => 'auto',
                    'editable' => false,
                    'filtertype' => 'normal',
                    'default' => 0,
                    'in_list' => true,
                    'default_in_list' => true,
                    'order' => 81,
                ),
            ),
            'idColumn' => 'picker',
            'in_list' => array (
                1 => 'name',
                2 => 'deliverys',
                3 => 'pick_nums',
                4 => 'single_nums',
                5 => 'multi_deliverys',
                6 => 'multi_nums',
                7 => 'rate_of_single',
                8 => 'rate_of_singlecomplete',
                9 => 'rate_of_pickscomplete',
            ),
            'default_in_list' => array (
                1 => 'name',
                2 => 'deliverys',
                3 => 'pick_nums',
                4 => 'single_nums',
                5 => 'multi_deliverys',
                6 => 'multi_nums',
                7 => 'rate_of_single',
                8 => 'rate_of_singlecomplete',
                9 => 'rate_of_pickscomplete',
            ),
        );
        return $schema;
    }

    /**
     * fetch_graph_data
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function fetch_graph_data($params){
        $params['time_from'] = sprintf('%s 00:00:00',$params['time_from']);
        $params['time_to'] = sprintf('%s 23:59:59',$params['time_to']);
        $time_range = array();

        if($params['report'] == 'month'):
            $time_format = '%Y-%m';
        else:
            $time_format = '%Y-%m-%d';
        endif;

        if($params['target'] == 2):
            $sql = "SELECT DATE_FORMAT(FROM_UNIXTIME(check_start_time),'$time_format') as date,check_op_name,sum(pick_num) as pick_nums
            FROM sdb_tgkpi_pick
            WHERE (pick_status='finish'  or pick_status='deliveryed') AND check_start_time between ".strtotime($params['time_from'])." AND ".strtotime($params['time_to'])."
            GROUP BY DATE_FORMAT(FROM_UNIXTIME(check_start_time),'$time_format'),check_op_id
            ";
            $rs = $this->db->selectLimit($sql,100,0);
            foreach((array)$rs as $v) {
                if(!in_array($v['date'],$time_range)) $time_range[] = $v['date'];
                $data[$v['check_op_name']][$v['date']] = $v['pick_nums'];
            }
        else:
            $sql = "SELECT DATE_FORMAT(FROM_UNIXTIME(check_start_time),'$time_format') as date,check_op_name,count(distinct delivery_id) as deliverys
            FROM sdb_tgkpi_pick
            WHERE (pick_status='finish'  or pick_status='deliveryed') AND check_start_time between ".strtotime($params['time_from'])." AND ".strtotime($params['time_to'])."
            GROUP BY DATE_FORMAT(FROM_UNIXTIME(check_start_time),'$time_format'),check_op_id
            ";
            $rs = $this->db->selectLimit($sql,100,0);
            foreach((array)$rs as $v) {
                if(!in_array($v['date'],$time_range)) $time_range[] = $v['date'];
                $data[$v['check_op_name']][$v['date']] = $v['deliverys'];
            }
        endif;

        return array('categories'=>$time_range, 'data'=>$data);
    }
}
