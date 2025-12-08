<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class qimen_rpc_service
{
    private $path = array();

    /**
     * 处理
     * @param mixed $path path
     * @return mixed 返回值
     */
    public function process($path){
        if(!kernel::is_online()){
            die('error');
        }
        
        $p = strpos($_REQUEST['method'], '.');
        $method = substr($_REQUEST['method'], $p+1, strlen($_REQUEST['method']));
        switch($method)
        {
            case 'onex.oms.custom.iostock.add':
                $iostockLib = kernel::single('qimen_iostock_iostock');
                
                //创建入库单
                $res = $iostockLib->add($_REQUEST);
                
                //write_log
                $title = '接收转仓单通知';
                $original_bn = $_REQUEST['trfoutno'];
                $status = ($res['rsp'] == 'succ' ? 'success' : 'fail');
                
                $params    = array();
                $params[0]  = $method;
                $params[1]  = $_POST;
                
                $msg = json_encode($res);
                
                $iostockLib->_write_log($title, $original_bn, $status, $params, $msg);
                
                echo $msg;
                exit;
                break;
            case 'onex.oms.custom.openapi.sync':
                $q = json_decode(base64_decode($_REQUEST['q']), true);
                
                $format_parmas = array(
                    'flag' => $_REQUEST['flag'],
                    'type' => $_REQUEST['type'],
                    'charset' => $_REQUEST['charset'],
                    'ver' => $_REQUEST['ver'],
                );
                unset($_POST, $_REQUEST);
                
                $_POST = array_merge($format_parmas, $q);
                
                $params = array();
                kernel::single('openapi_entrance')->service($params);
                break;
            default:
                break;
        }
        
        return json_encode(array('rsp'=>'fail', 'sub_code'=>'e0053', 'sub_message'=>'无效的请求通知'));
    }
}
