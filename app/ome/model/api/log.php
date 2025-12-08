<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_mdl_api_log extends dbeav_model
{
    /**
     * 因为分区表，所以需要重写获取schema方法
     * 
     */
    public function get_schema()
    {
        $schema = parent::get_schema();
        $schema['idColumn'] = 'log_id';

        return $schema;
    }

    public function getClass()
    {
        $class = array(
            'local' => 'ome_mdl_api_log_local',
            'elk'   => 'ome_mdl_api_log_elk',
        );

        $switch = $class_name = '';
        if (!defined('APILOG_SWITCH')) {
            $switch = 'local';
        } else {
            $switch = APILOG_SWITCH;
        }

        if (isset($class[$switch]) && $class[$switch]) {
            $class_name = $class[$switch];
        } else {
            $class_name = 'ome_mdl_api_log_local';
        }
        $object = kernel::single($class_name);

        $object->filter_use_like = $this->filter_use_like;

        return $object;
    }

    /**
     * gen_id
     * @return mixed 返回值
     */
    public function gen_id()
    {
        $microtime  = utils::microtime();
        $unique_key = str_replace('.', '', strval($microtime));
        $randval    = uniqid('', true);
        $unique_key .= strval($randval);
        return md5($unique_key);
    }

    /**
     * _filter
     * @param mixed $filter filter
     * @param mixed $tableAlias tableAlias
     * @param mixed $baseWhere baseWhere
     * @return mixed 返回值
     */
    public function _filter($filter, $tableAlias = null, $baseWhere = null)
    {
        return $this->getClass()->_filter($filter, $tableAlias, $baseWhere);
    }

    /*
     * 写日志
     * @param int $log_id 日志id
     * @param string $task_name 操作名称
     * @param string $class 调用这次api请求方法的类
     * @param string $method 调用这次api请求方法的类函数
     * @param array $params 调用这次api请求方法的参数集合
     * @param string $msg 返回信息
     * @param string $addon[marking_value标识值，marking_type标识类型 ]
     *
     */

    public function write_log($log_id, $task_name, $class, $method, $params, $memo = '', $api_type = 'request', $status = 'running', $msg = '', $addon = '', $log_type = '', $bn = '')
    {
        return $this->getClass()->write_log($log_id, $task_name, $class, $method, $params, $memo, $api_type, $status, $msg, $addon, $log_type, $bn);
    }

    /**
     * 更新_log
     * @param mixed $log_id ID
     * @param mixed $msg msg
     * @param mixed $status status
     * @param mixed $params 参数
     * @param mixed $addon addon
     * @param mixed $kaf kaf
     * @return mixed 返回值
     */
    public function update_log($log_id, $msg = null, $status = null, $params = null, $addon = null, $kaf = [])
    {
        return $this->getClass()->update_log($log_id, $msg, $status, $params, $addon, $kaf);
    }

    /**
     * is_repeat
     * @param mixed $key key
     * @return mixed 返回值
     */
    public function is_repeat($key)
    {
        return $this->getClass()->is_repeat($key);
    }

    /**
     * 设置_repeat
     * @param mixed $key key
     * @param mixed $log_id ID
     * @return mixed 返回操作结果
     */
    public function set_repeat($key, $log_id = '')
    {
        return $this->getClass()->set_repeat($key, $log_id);
    }

    /*
     * 同步重试
     * 有单个重试与批量重试
     * @param array or int $log_id
     * @param string $retry_type 默认为单个重试，btach:为批量重试
     * @param string $isSelectedAll 是否全选
     * @param string $cursor 当前游标，用于循环选中重试
     */

    public function retry($log_id = '', $retry_type = '', $isSelectedAll = '', $cursor = '0')
    {
        return $this->getClass()->retry($log_id, $retry_type, $isSelectedAll, $cursor);
    }

    /*
     * 发起API同步重试
     * @param array $row 发起重试数据
     */

    public function start_api_retry($row)
    {
        return $this->getClass()->start_api_retry($row);
    }

    /**
     * count
     * @param mixed $filter filter
     * @return mixed 返回值
     */
    public function count($filter = null)
    {
        return $this->getClass()->count($filter);
    }

    public function getList($cols = '*', $filter = array(), $offset = 0, $limit = -1, $orderType = null)
    {
        return $this->getClass()->getList($cols, $filter, $offset, $limit, $orderType);
    }

    /**
     * insert
     * @param mixed $data 数据
     * @return mixed 返回值
     */
    public function insert(&$data)
    {
        return $this->getClass()->insert($data);
    }

    /**
     * batchInsert
     * @param mixed $data 数据
     * @return mixed 返回值
     */
    public function batchInsert($data)
    {
        return $this->getClass()->batchInsert($data);
    }

    public function update($data, $filter = array(), $mustUpdate = null)
    {
        return $this->getClass()->update($data, $filter);
    }

    /**
     * dump
     * @param mixed $filter filter
     * @param mixed $field field
     * @param mixed $subSdf subSdf
     * @return mixed 返回值
     */
    public function dump($filter, $field = '*', $subSdf = null)
    {
        return $this->getClass()->dump($filter, $field, $subSdf);
    }

    /**
     * modifier_msg
     * @param mixed $msg msg
     * @return mixed 返回值
     */
    public function modifier_msg($msg){
        return htmlentities($msg);
    }

    /**
     * modifier_response
     * @param mixed $response response
     * @return mixed 返回值
     */
    public function modifier_response($response){
        if (is_array($response)) {
            $response = json_encode($response, JSON_UNESCAPED_UNICODE);
        }
        return htmlentities($response);
    }
}
