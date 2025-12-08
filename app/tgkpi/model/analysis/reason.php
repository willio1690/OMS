<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class tgkpi_mdl_analysis_reason extends dbeav_model{

    private $realTableName = 'sdb_tgkpi_check_memo';

    /**
     * count
     * @param mixed $filter filter
     * @return mixed 返回值
     */
    public function count($filter=null){
        $f = kernel::single('tgkpi_analysis_reason')->dealTime($filter);
        $filter['time_from'] = sprintf('%s 00:00:00',$f['time_from']);
        $filter['time_to'] = sprintf('%s 23:59:59',$f['time_to']);
        $filter['time_from'] = strtotime($filter['time_from']);
        $filter['time_to'] = strtotime($filter['time_to']);
        $sql = "SELECT count(distinct memo) as total FROM sdb_tgkpi_check_memo where ";
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
        $data['name'] = $this->app->_('校验失败原因');

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
            $where[] = ' addtime between '.$filter['time_from'].' AND '.$filter['time_to'];
        }

        return implode(' AND ',$where);
    }

    public function getlist($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null){
        $f = kernel::single('tgkpi_analysis_reason')->dealTime($filter);
        $filter['time_from'] = sprintf('%s 00:00:00',$f['time_from']);
        $filter['time_to'] = sprintf('%s 23:59:59',$f['time_to']);
        $filter['time_from'] = strtotime($filter['time_from']);
        $filter['time_to'] = strtotime($filter['time_to']);

        $sql = "SELECT memo as reason , COUNT(memo) as mCount from ".$this->realTableName." where ";
        $sql .= $this->pickFilter($filter);
        $sql .= ' GROUP BY memo ';

        if($orderType){
            if (is_string($orderType)) {
                $sql .= ' order by '.$orderType;
            }
        }

        $rs = $this->db->selectLimit($sql,$limit,$offset);
        if (empty($rs)) {
            return array();
        }
        $all = $rs; unset($rs);

        $data = array();
        foreach ($all as $key=>$value) {
            $data[$key]['reason'] = $value['reason'];
            $data[$key]['mCount'] = $value['mCount'];
        }
        unset($all);

        $this->tidy_data($data, $cols);
        return $data;
    }

    /**
     * 获取_schema
     * @return mixed 返回结果
     */
    public function get_schema(){
        $schema = array (
            'columns' => array (
                'reason' => array (
                    'type' => 'varchar(10)',
                    'pkey' => true,
                    'label' => '校验错误原因',
                    'width' => 200,
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                    'order' => 20,
                ),
                'mCount' => array (
                    'type' => 'number',
                    'label' => '出现次数',
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
            ),
            'idColumn' => 'picker',
            'in_list' => array (
                1 => 'reason',
                2 => 'mCount',
            ),
            'default_in_list' => array (
                1 => 'reason',
                2 => 'mCount',
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
        $time_format = '%Y-%m-%d';

        $reasonObj = app::get('tgkpi')->model('reason');
        $reasonList = $reasonObj->getList('*',null,0,-1);

        $r_id = array();
        foreach($reasonList as $reason){
            $r_id[] = $reason['reason_id'];
            $r_arr[$reason['reason_id']] = $reason['reason_memo'];
        }

        if($params['target'] == 1){
            $sql = "SELECT DATE_FORMAT(FROM_UNIXTIME(addtime),'$time_format') as date,memo as reason , COUNT(memo) as mCount from ".$this->realTableName."
            where addtime between ".strtotime($params['time_from'])." AND ".strtotime($params['time_to'])."
            GROUP BY memo
            ";
            $rs = $this->db->selectLimit($sql,100,0);
            foreach((array)$rs as $v) {
                if(!in_array($v['date'],$time_range)) $time_range[] = $v['date'];
                $data[$v['reason']][$v['date']] = $v['mCount'];
            }
        }elseif (in_array(($params['target']-1), $r_id)){
            $memoObj = $this->app->model('check_memo');
            $params['target'] = $params['target'] -1;

            $sql ="select DATE_FORMAT(FROM_UNIXTIME(addtime),'$time_format') as date,delivery_id from sdb_tgkpi_check_memo where reason_id =".$params['target']." and addtime between ".strtotime($params['time_from'])." AND ".strtotime($params['time_to'])."";
            $rs = $this->db->selectLimit($sql,-1,0);

            $deliveryIds = array();
            foreach ($rs as $v){
                if(!in_array($v['delivery_id'], $deliveryIds)){
                    $deliveryIds[] = $v['delivery_id'];
                }
            }

            $sql = "select pick_owner,delivery_id from sdb_tgkpi_pick where delivery_id in (".implode(',', $deliveryIds).")";
            $rs2 = $this->db->selectLimit($sql,-1,0);

            $pick_owners = array();
            foreach($rs2 as $v){
                if(!in_array($v['pick_owner'], $pick_owners)){
                    $pick_owners[] = $v['pick_owner'];
                    $deliveryUser[$v['delivery_id']] = $v['pick_owner'];
                }
            }
            unset($rs2);
            $filter['pick_owner'] = $pick_owners;

            $userModel = app::get('desktop')->model('users');
            $temusers = $userModel->getList('op_no,name',array('op_no'=>$filter['pick_owner']));
            foreach ($temusers as $v) {
                $v['op_no'] = trim($v['op_no']);
                $user[$v['op_no']] = $v['name'];
            }

            foreach ($rs as $v){
                if(!in_array($v['date'],$time_range)) $time_range[] = $v['date'];
                if(isset($data[$user[$deliveryUser[$v['delivery_id']]]][$v['date']])){
                    $data[$user[$deliveryUser[$v['delivery_id']]]][$v['date']] = $data[$user[$deliveryUser[$v['delivery_id']]]][$v['date']]+1;
                }else{
                    $data[$user[$deliveryUser[$v['delivery_id']]]][$v['date']] = 1;
                }

            }
        }

        return array('categories'=>$time_range, 'data'=>$data);
    }
}
