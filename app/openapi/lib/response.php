<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_response
{

    /**
     * __construct
     * @return mixed 返回值
     */
    public function __construct()
    {
        $this->format = kernel::single('openapi_format_abstract');
    }

    /**
     * send_result
     * @param mixed $data 数据
     * @param mixed $charset charset
     * @param mixed $type type
     * @param mixed $root root
     * @return mixed 返回值
     */
    public function send_result($data, $charset, $type, $root = 'response')
    {
        $res = array(
            $root => $data,
        );
    
     
        
        $kafkaMsg = '';
//        if (isset($kafkaMsg[512000])) {
//            $kafkaMsg = '数据太大，不存储返回结果';
//        }

        $logsdf = array(
            'status' => 'success',
            'params' => $_POST,
            'response' => ['因rdkafka内存溢出暂不存数据'],
            'msg' => $kafkaMsg,
        );
        $this->_log($logsdf, 'response');

        $this->format->process($res, $charset, $type);
    }

    /**
     * send_error
     * @param mixed $code code
     * @param mixed $charset charset
     * @param mixed $type type
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回值
     */
    public function send_error($code, $charset, $type, $sub_msg=null)
    {
        $error_arr = openapi_errorcode::get($code);
        $res       = array(
            'error_response' => array(
                'code'    => $error_arr['code'],
                'msg'     => $error_arr['msg'],
                'sub_msg' => $sub_msg,
            ),
        );

        $kafkaMsg = '';
        if (isset($kafkaMsg[512000])) {
            $kafkaMsg = '数据太大，不存储返回结果';
        }

        $logsdf = array(
            'status' => 'fail',
            'response' => $res,
            'params' => $_POST,
            'msg' => $kafkaMsg,
        );
        $this->_log($logsdf, 'response');

        $this->format->process($res, $charset, $type);
    }

    /**
     * 获取FormatType
     * @param mixed $type type
     * @return mixed 返回结果
     */
    public function getFormatType($type)
    {
        if (in_array($type, $this->format->type_lists)) {
            return $type;
        } else {
            return '';
        }
    }

    /**
     * 获取FormatCharset
     * @param mixed $charset charset
     * @return mixed 返回结果
     */
    public function getFormatCharset($charset)
    {
        if (in_array($charset, $this->format->charset_lists)) {
            return $charset;
        } else {
            return '';
        }
    }

    /**
     * _log
     * @param mixed $logsdf logsdf
     * @param mixed $step step
     * @return mixed 返回值
     */
    public function _log($logsdf, $step = 'request')
    {
        $apilogModel = app::get('ome')->model('api_log');
        $log_id      = uniqid('openapi');

        $logsdf = array(
            'log_id'        => $log_id,
            'task_name'     => $_POST['method'],
            'status'        => $logsdf['status'],
            'worker'        => '',
            'params'        => json_encode($logsdf['params'], JSON_UNESCAPED_UNICODE),
            'msg'           => $logsdf['msg'],
            'response'      => json_encode($logsdf['response'], JSON_UNESCAPED_UNICODE),
            'log_type'      => '',
            'api_type'      => 'response',
            'memo'          => '',
            'original_bn'   => 'openapi',
            'createtime'    => time(),
            'last_modified' => time(),
        );

        $apilogModel->insert($logsdf);
    }
}
