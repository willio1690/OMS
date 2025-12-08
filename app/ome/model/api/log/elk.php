<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_mdl_api_log_elk extends dbeav_model
{
    public $filter_use_like = true;

    private $keyMapping = array(
        'method' => 'worker',
        'step'   => 'api_type',
        'title'  => 'task_name',
    );

    /**
     * table_name
     * @param mixed $real real
     * @return mixed 返回值
     */
    public function table_name($real = false)
    {
        $tableName = 'api_log';
        return $real ? kernel::database()->prefix . 'ome_' . $tableName : $tableName;
    }
    /**
     * 获取_schema
     * @return mixed 返回结果
     */
    public function get_schema()
    {
        $schema = app::get('ome')->model('api_log')->get_schema();
        return $schema;
    }

    /**
     * gen_id
     * @return mixed 返回值
     */
    public function gen_id()
    {
        return uniqid();
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
        $time = time();

        if (isset($params[1]['msg_id'])) {
            $msg_id = $params[1]['msg_id'];
        }
        $log_sdf = array(
            'log_id'        => $log_id,
            'task_name'     => $task_name,
            'status'        => $status,
            'worker'        => $class . ':' . $method,
            'params'        => serialize($params),
            'msg'           => $msg,
            'log_type'      => $log_type,
            'api_type'      => $api_type,
            'memo'          => $memo,
            'original_bn'   => $bn,
            'createtime'    => $time,
            'last_modified' => $time,
        );
        if ($msg_id) {
            $log_sdf['msg_id'] = $msg_id;
        }
        if (is_array($addon)) {
            $log_sdf['marking_value'] = $addon['marking_value'];
            $log_sdf['marking_type']  = $addon['marking_type'];
        }

        return $this->insert($log_sdf);
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
        $log_sdf = array(
            'log_id'        => '',
            'task_name'     => '',
            'status'        => $status,
            'worker'        => '',
            'msg'           => $msg,
            'log_type'      => '',
            'api_type'      => 'callback',
            'memo'          => '',
            'original_bn'   => (string) $kaf['obj_bn'],
            'createtime'    => time(),
            'last_modified' => time(),
            'msg_id'        => (string) $kaf['msg_id'],
            'method'        => '',
            'spendtime'     => $kaf['spendtime'],
            // 'data'          => (array) $kaf['data'],
            'transfer'      => $kaf['transfer'],
            'response'      => $kaf['response'],
            'params'        => $kaf['params'],
        );

        return $this->insert($log_sdf);
    }

    /**
     * retry
     * @param mixed $log_id ID
     * @param mixed $retry_type retry_type
     * @param mixed $isSelectedAll isSelectedAll
     * @param mixed $cursor cursor
     * @return mixed 返回值
     */
    public function retry($log_id = '', $retry_type = '', $isSelectedAll = '', $cursor = '0')
    {
        $filter = array(
            'status' => 'fail',
            'step'   => 'request',
        );
        if ($retry_type == 'batch' and (strstr($log_id, "|") or $isSelectedAll == '_ALL_')) {
            //批量重试
            $limit = 1;
            if ($isSelectedAll != '_ALL_') {
                $log_ids          = explode('|', $log_id);
                $filter['log_id'] = $log_ids[$cursor];
                $lim              = 0;
            } else {
                $lim = $cursor * $limit;
            }
            $row = $this->getList('*', $filter, $lim, $limit, ' createtime asc ');
            if ($row) {
                foreach ($row as $k => $v) {
                    $detail = $this->dump(array('log_id' => $v['log_id'], 'status' => 'fail'));

                    if (!$detail) {
                        continue;
                    }

                    return $this->start_api_retry($detail);
                }

                return array('task_name' => '全部批量重试', 'status' => 'complete');
            } else {
                return array('task_name' => '全部批量重试', 'status' => 'complete');
            }
        } else {
            //单个按钮重试
            $filter['log_id'] = $log_id;
            $row              = $this->dump($filter);
            return $this->start_api_retry($row);
        }
    }

    /*
     * 发起API同步重试
     * @param array $row 发起重试数据
     */

    public function start_api_retry($row)
    {
        return array('task_name' => $row['task_name'], 'status' => 'fail');
    }

    /**
     * insert
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function insert(&$params)
    {
        // if ($params['data']) {
        //     $data = $params['data'];
        // } else {
        //     $data = array('result' => $params['msg']);
        //     $arr  = unserialize($params['params']);
        //     if ($arr) {
        //         if (!$params['method']) {
        //             $params['method'] = $arr[0];
        //         }
        //         $data['params'] = $arr;
        //     }
        // }
        $kafkaData = array(
            'title'       => (string) $params['task_name'],
            'method'      => (string) $params['method'],
            'original_bn' => (string) $params['original_bn'],
            'msg_id'      => (string) $params['msg_id'],
            'status'      => $params['status'],
            'createtime'  => intval($params['createtime']),
            'spendtime'   => $params['spendtime'] ? $params['spendtime'] : 0,
            'data'        => [
                'params'    => json_decode($params['params'],true),
                'transfer'  => json_decode($params['transfer'],true),
                'response'  => json_decode($params['response'],true),
            ],
            // 'params'      => $params['params'],
            // 'transfer'    => $params['transfer'],
            // 'response'    => $params['response'],
        );
        kernel::single('erpapi_log_elk')->write_log($kafkaData, $params['api_type']);
    }

    /**
     * batchInsert
     * @param mixed $data 数据
     * @return mixed 返回值
     */
    public function batchInsert($data)
    {
        foreach($data as $v) {
            $this->insert($v);
        }
    }
    
    public function update($data, $filter = array(), $mustUpdate = null)
    {
        return true;
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
        $filtered = array(
            'query' => array(
                'bool' => array(
                    'must' => array(
                        array('match' => array('node_id' => base_shopnode::node_id('ome'))),
                    ),
                ),
            ),
        );

        $directField = array(
            'msg_id'     => 'msg_id',
            'status'     => 'status',
            'step'       => 'step',
            'log_id'     => '_id',
            'api_method' => 'method',
            'api_type'   => 'step',
        );
        foreach ($filter as $k => $val) {
            if (isset($directField[$k])) {
                $term = array('match' => array($directField[$k] => $val));
                array_push($filtered['query']['bool']['must'], $term);unset($filter[$k]);
            }
        }

        if ($filter['task_name']) {
            $filtered['query']['bool']['must'][] = array(
                'match' => array(
                    'title' => array('query' => $filter['task_name'], "minimum_should_match" => "100%"),
                ),
            );

            unset($filter['task_name']);
        }

        if ($filter['original_bn']) {
            $filtered['query']['bool']['must'][] = array(
                'match' => array(
                    'original_bn' => array('query' => $filter['original_bn'], "minimum_should_match" => "100%"),
                ),
            );

            unset($filter['original_bn']);
        }

        if ($filter['params']) {
            $filtered['query']['bool']['must'][] = array(
                'match' => array(
                    'data' => array('query' => $filter['params'], "minimum_should_match" => "100%"),
                ),
            );

            unset($filter['params']);
        }

        if ($filter['createtime_from']) {
            $createTime['range']['@timestamp']['gte'] = strtotime($filter['createtime_from']);
        }
        if ($filter['createtime_to']) {
            $createTime['range']['@timestamp']['lt'] = strtotime($filter['createtime_to'] . ' 23:59:59');
        }

        if ($createTime) {
            $filtered['query']['bool']['must'][] = $createTime;
        }

        return $filtered;
    }

    private function _get_elk_url($date = null)
    {
        $url = ELK_APILOG_URL;

        if (!$date || strtotime($date) < strtotime('2022-07-27')) {
            return $url;
        }

        $topic = defined('API_RAKAFKA_TOPIC') ? constant('API_RAKAFKA_TOPIC') : 'erp';

        if ($date && $timestamp = strtotime($date)) {
            $index = '';
            do {
                $index .= sprintf('%s-%s', $topic, date('Y.m.d', $timestamp)) . ',';

                if (strlen($index) > 300) {
                    $index = '';
                    break;
                }

                $timestamp += 86400;
            } while ($timestamp < time());

            if ($index) {
                $url = str_replace($topic . '-*', rtrim($index, ','), $url);
            }
        }

        return $url;
    }

    /**
     * count
     * @param mixed $filter filter
     * @return mixed 返回值
     */
    public function count($filter = null)
    {
        if (empty($filter)) {
            return ' ';
        }
        $url               = $this->_get_elk_url($filter['createtime_from']);
        $params            = $this->_filter($filter);
        $params['_source'] = array('original_bn');
        $params['size']    = 1;
        $params['from']    = 0;

        $data = $this->elk_search($url, $params);
        return $data['hits'] ? $data['hits']['total']['value'] > 9000 ? 9000 : $data['hits']['total']['value'] : ' ';
    }

    public function getList($cols = '*', $filter = array(), $offset = 0, $limit = -1, $orderType = null)
    {
//        if (count($filter) < 3) {
//            return array();
//        }
        
        //empty
        if(empty($filter)) {
            return array();
        }
        
        $params            = $this->_filter($filter);
        $params['_source'] = array('createtime', 'method', 'msg_id', 'original_bn', 'status', 'step', 'title', 'type');
        $params['sort']    = array('@timestamp' => array('order' => 'desc'));

        $url = $this->_get_elk_url($filter['createtime_from']);
        if ($limit != -1) {
            $params['size'] = $limit;
            $params['from'] = $offset;
        }

        $data = $this->elk_search($url, $params);

        if ($data && $data['hits'] && $data['hits']['total']) {
            return $this->_formatElkData($data['hits']['hits']);
        } else {
            return array();
        }
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
        if (is_array($filter)) {
            $params = $this->_filter($filter);
        } else {
            $params = $this->_filter(array('log_id' => $filter));
        }

        $params['_source'] = array('spendtime', 'title', 'method', 'original_bn', 'msg_id', 'status', 'createtime', 'step', 'node_id', 'domain', 'type', 'data');
        $params['size']    = 1;
        $params['from']    = 0;

        $url  = $this->_get_elk_url();
        $data = $this->elk_search($url, $params);
        $rs   = array();
        if ($data['hits']['hits'][0]) {
            $rows = $this->_formatElkData($data['hits']['hits']);
            $rs   = $this->_formatRowElkData($rows[0]);
        }
        return $rs;
    }

    /**
     * is_repeat
     * @param mixed $key key
     * @return mixed 返回值
     */
    public function is_repeat($key = '')
    {
        $paramsCacheLib = kernel::single('taoexlib_params_cache');
        $paramsCacheLib->fetch($key, $value);
        return $value;
    }

    /**
     * 设置_repeat
     * @param mixed $key key
     * @param mixed $log_id ID
     * @return mixed 返回操作结果
     */
    public function set_repeat($key = '', $log_id = '')
    {
        $paramsCacheLib = kernel::single('taoexlib_params_cache');
        return $paramsCacheLib->store($key, $log_id, $expries_time = 1200);
    }

    private function elk_search($url, $params)
    {
        $headers = array('Content-Type' => 'application/json');

        if (defined('ELK_APILOG_AUTH') && constant('ELK_APILOG_AUTH')) {
            $headers['Authorization'] = 'Basic ' . ELK_APILOG_AUTH;
        }
        $rs = kernel::single('base_httpclient')->post($url, json_encode($params), $headers);

        return json_decode($rs, true);
    }

    private function _formatElkData($data)
    {
        $arrData = array();
        foreach ($data as $row) {
            $tmp = array();
            foreach ($row['_source'] as $k => $val) {
                if ($k === 'createtime') {
                    list($val) = explode('.', $val);
                }

                $index       = $this->keyMapping[$k] ? $this->keyMapping[$k] : $k;
                $tmp[$index] = $val;
            }
            $tmp['log_id']        = $row['_id'];
            $tmp['retry']         = 0;
            $tmp['last_modified'] = $tmp['createtime'];
            $arrData[]            = $tmp;
        }
        return $arrData;
    }

    private function _formatRowElkData($row)
    {
        // $tmp     = array();
        // $msg     = array();
        // $tmp[0]  = $row['method'];

        $data = json_decode($row['data'], true);
        $row['params']   = $data['params'];
        $row['transfer'] = $data['transfer'];
        $row['response'] = $data['response'];
        $row['msg']      = $data && $data['response'] && is_array($data['response']) ? $data['response']['msg'] : '';

        // foreach ($allData as $k => $val) {
        //     if (in_array($k, array('result', 'response'))) {
        //         $msg = $val;
        //     } elseif ($k == 'callback') {
        //         continue;
        //     } elseif($val) {
        //         $tmp[1][$k] = $val;
        //     }
        // }
        // $row['params'] = serialize($tmp);
        // if (is_array($msg)) {
        //     $row['msg'] = json_encode($msg);
        // } else {
        //     $row['msg'] = $msg;
        // }

        return $row;
    }
}
