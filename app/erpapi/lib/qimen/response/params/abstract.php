<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
abstract class erpapi_qimen_response_params_abstract
{
    /**
     * 检查
     *
     * @param mixed $params 参数
     * @param mixed $method method
     * @return mixed 返回验证结果
     */
    public function check($params,$method)
    {   
        $check_params = $this->{$method}();
        
        if (!$check_params || !is_array($check_params)) return array('rsp'=>'succ');
        
        foreach ($check_params as $col => $valid)
        {
            if ($valid['required']=='true' && !$params[$col]) {
                $msg = $valid['errmsg'] ? $valid['errmsg'] : "{$col} required";
                return array('rsp'=>'fail', 'msg'=>$msg);
            }
            
            switch ($valid['type']) {
                case 'date':
                    if ($params[$col] && !preg_match('/^\d{4}\-\d{2}\-\d{2} \d{2}:\d{2}:\d{2}$/', $params[$col])) {
                        $msg = $valid['errmsg'] ? $valid['errmsg'] : '日期格式有误';
                        return array('rsp'=>'fail', 'msg'=>$msg);
                    }
                    break;
                case 'string':
                    if (!is_string($params[$col])) {
                        $msg = $valid['errmsg'] ? $valid['errmsg'] : "{$col} must be string";
                        return array('rsp'=>'fail', 'msg'=>$msg);
                    }
                    break;
                case 'enum':
                    if (!in_array($params[$col],$valid['value'])) {
                        $msg = $valid['errmsg'] ? $valid['errmsg'] : "{$col}: only ".implode('|', $valid['value']).' can be choise';
                        return array('rsp'=>'fail', 'msg'=>$msg);
                    }
                    break;
                case 'array':
                    if (!is_array($params[$col]) || !$params[$col]) {
                        $msg = $valid['errmsg'] ? $valid['errmsg'] : "{$col} must be array";
                        return array('rsp'=>'fail', 'msg'=>$msg);
                    }
                    
                    if (is_numeric(key($params[$col]))) {
                        foreach ($params[$col] as $k => $v) {
                            $rs = self::validate($valid,$v);
                            if ($rs['rsp'] != 'succ') {
                                return $rs;
                            }
                        }
                    } else {
                        $rs = self::validate($valid, $params[$col]);
                        if ($rs['rsp'] != 'succ') {
                            return $rs;
                        }
                    }
                    break;
                default:
                    # code...
                    break;
            }
        }
        
        return array('rsp'=>'succ','msg'=>'');
    }
}