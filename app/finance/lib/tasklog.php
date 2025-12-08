<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class finance_tasklog{

    /**
     * 添加任务日志记录
     * @access public
     * @param String $log_title 日志标题
     * @param String $log_type 日志分类 trade_search:交易记录 taskid:任务号  taskresult:任务结果
     * @param Array  $params 日志参数
     * @param String $status 任务状态
     * @param string $msg 任务消息
     * @param string $node_id 节点ID
     * @param Array $addon 扩展字段
     * @return Int 日志ID
     */
    public function write_log($log_title,$log_type,$params,$status,$msg='',$node_id='',$addon=Array()){
        if (empty($params)) return NULL;

        $tasklogModel = &app::get('finance')->model('tasklog');
        $time = time();
        $log_sdf = array(
            'log_title' => $log_title,
            'params' => serialize($params),
            'log_type' => $log_type,
            'crc32_log_type' => sprintf('%u',crc32(md5($log_type))),
            'status' => $status ? $status : 'fail',// 默认值:失败
            'msg' => $msg,
            'node_id' => $node_id,
            'createtime' => $time,
            'last_modified' => $time,
        );
        if (is_array($addon)){
            $log_sdf = array_merge($log_sdf, $addon);
        }
        if ($tasklogModel->save($log_sdf)){
            return $log_sdf['log_id'];
        }else{
            return NULL;
        }
    }

    /**
     * 更新任务日志
     * @access public
     * @param String $log_id 日志ID
     * @param String $msg 任务消息
     * @param String $status 任务状态
     * @param Array $params 任务参数(覆盖)
     * @param Array $addon 扩展字段
     * @return boolean
     */
    function update_log($log_id,$msg='',$status=NULL,$params=array(),$addon=array()){
        if (empty($log_id)) return false;
        
        $update_field = array('status','params','msg');
        $log_sdf = array();
        foreach ($update_field as $fields){
            if (!empty(${$fields})){
                $log_sdf[$fields] = ${$fields};
            }
        }
        if(isset($log_sdf['params'])){
            $log_sdf['params'] = serialize($params);
        }
        if (is_array($addon)){
            $log_sdf = array_merge($log_sdf, (array)$addon);
        }
        
        $tasklogModel = &app::get('finance')->model('tasklog');
        $filter = array('log_id'=>$log_id);
        return $tasklogModel->update($log_sdf, $filter);
    }

    /**
     * 获取日志信息
     * @access public
     * @param String $log_id 日志ID
     * @param String $col 字段信息
     * @return bool
     */
    function detail($log_id,$col='*'){
        if (empty($log_id)) return true;

        $tasklogModel = &app::get('finance')->model('tasklog');
        $filter = array('log_id'=>$log_id);
        $detail = $tasklogModel->getList($col,$filter,0,1);
        if (isset($detail[0]['params']) && $detail[0]['params']){
            $detail[0]['params'] = unserialize($detail[0]['params']);
        }
        return $detail[0];
    }

    /**
     * 获取任务日志数据
     * @access public
     * @param Array $filter 过滤条件
     * @return Array
     */
    function getList($filter=''){
        $tasklogModel = &app::get('finance')->model('tasklog');
        $filter = !empty($filter) ? $filter : array();
        return $tasklogModel->getList('*',$filter,0,-1);
    }

    /**
     * 删除日志
     * @access public
     * @param String $log_id 日志ID
     * @return bool
     */
    function delete($log_id){
        if (empty($log_id)) return true;

        $tasklogModel = &app::get('finance')->model('tasklog');
        $filter = array('log_id'=>$log_id);
        return $tasklogModel->delete($filter);
    }

    /**
     * 强制失败
     * @access public
     * @param Array $log_ids 任务日志ID
     * @return bool
     */
    function abort_fail($log_ids){
        $tasklogModel = &app::get('finance')->model('tasklog');
        return $tasklogModel->abort_fail($log_ids);
    }

}