<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 请求转发路由
 *
 * @category
 * @package
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
@include_once(dirname(__FILE__).'/../apiname.php');

class erpapi_router_request
{
    /**
     * 平台类型  wms|shop
     *
     * @var string
     **/
    private $__channel_type = '';

    /**
     * 平台ID
     *
     * @var string
     **/
    private $__channel_id = null;

    /**
     * 标识业务 delivery|goods ...
     *
     * @var string
     **/
    private $__business;


    /**
     * 设置初始化
     *
     * @return object
     * @author 
     **/

    public function set($channel_type,$channel_id)
    {
        $this->__channel_type = $channel_type;
        
        $this->__channel_id   = $channel_id;
        
        // $this->__business      = $business;

        return $this;
    }

    /**
     * 
     *
     * @return Array array('rsp'=>'succ|fail','msg'=>'','data'=>'','msg_code'=>'')
     * @author 
     **/
    public function __call($method,$args)
    {   
        try {
            if (!$this->__channel_id) throw new Exception("channel_id is required");

            if (!$this->__channel_type) throw new Exception("channel_type is required");
            if (in_array($this->__channel_type,array('store','wms','tbo2o'))){
                list($this->__business,$action) = explode('_',$method);
                $action = $method;
            }else{
                if (false !== $pos=strpos($method,'_')) {
                    $this->__business = substr($method, 0,$pos);
                    $action = substr($method, $pos+1);
                }
            }
            
            if (!$this->__business || !$action) {
                throw new Exception('method:format error', 1);
            }

            $channel_name = 'erpapi_channel_'.$this->__channel_type;
            $channel_class = kernel::single($channel_name,array($this->__channel_type,$this->__channel_id));
            if (!$channel_class instanceof erpapi_channel_abstract) throw new Exception("{$channel_name} not instanceof erpapi_channel_abstract");

            $channelRs = $channel_class->init(null,$this->__channel_id);
            if (!$channelRs) throw new Exception('渠道不存在');

            $adapter  = $channel_class->get_adapter();
            $platform = $channel_class->get_platform();
            $ver      = $channel_class->get_ver();
          

            // 可配置默认类
            $default_config_name = 'erpapi_'.$this->__channel_type.'_config';
            $config_class = kernel::single($default_config_name,array($channel_class));

            try {
                // 自带配置类
                $config_name_arr = array('erpapi',$this->__channel_type,$adapter,$platform,'config');
                $config_name = implode('_',array_filter($config_name_arr));

                if (class_exists($config_name)) {
                    $config_class = kernel::single($config_name,array($channel_class));

                    if (!is_subclass_of($config_class, $default_config_name)) throw new Exception("{$config_name} not instanceof {$default_config_name}");
                }    
            } catch (Exception $e) {
                try {
                    $config_name_arr = array('erpapi',$this->__channel_type,$adapter,'config');
                    $config_name = implode('_',array_filter($config_name_arr));

                    if (class_exists($config_name)) {
                        $config_class = kernel::single($config_name,array($channel_class));
                        if (!is_subclass_of($config_class, $default_config_name)) throw new Exception("{$config_name} not instanceof {$default_config_name}");
                    }
                } catch (Exception $e) {
                       
                }   
            }
            $config_class->init($channel_class);

            // 结果默认类
            $result_class = kernel::single('erpapi_result',array($channel_class));

            try {
                // 自带结果类
                $result_name_arr = array('erpapi',$this->__channel_type,$adapter,$platform,'result');
                $result_name = implode('_',array_filter($result_name_arr));

                if (class_exists($result_name)) {
                    $result_class = kernel::single($result_name,array($channel_class));

                    if (!is_subclass_of($result_class, 'erpapi_result')) throw new Exception("{$result_name} not instanceof erpapi_result");
                }
            } catch (Exception $e) {
                try {
                    // 自带结果类
                    $result_name_arr = array('erpapi', $this->__channel_type, $adapter, 'result');
                    $result_name = implode('_', array_filter($result_name_arr));
                    if (class_exists($result_name)) {
                        $result_class = kernel::single($result_name, array($channel_class));
                        if (!is_subclass_of($result_class, 'erpapi_result')) throw new Exception("{$result_name} not instanceof erpapi_result");
                    }
                } catch (Exception $e) {
                    try {
                        // 自带结果类
                        $result_name_arr = array('erpapi', $this->__channel_type, 'result');
                        $result_name = implode('_', array_filter($result_name_arr));
                        if (class_exists($result_name)) {
                            $result_class = kernel::single($result_name, array($channel_class));
                            if (!is_subclass_of($result_class, 'erpapi_result')) throw new Exception("{$result_name} not instanceof erpapi_result");
                        }
                    } catch (Exception $e) {

                    }
                }
            }

            // 平台处理默认类
            $object_class = $this->_get_object_class($channel_class);
            $object_class->init($channel_class,$config_class,$result_class);
           
            if (!method_exists($object_class,$action)) throw new Exception("method error");
            
            return call_user_func_array(array($object_class,$action), $args);
        } catch (Exception $e) {
            return array('rsp'=>'fail','msg'=>$e->getMessage(),'data'=>'','msg_code'=>'');
        }
    }

     /**
     * 获取处理类
     *
     * @return void
     * @author 
     **/
    private function _get_object_class($channel_class)
    {
        $adapter  = $channel_class->get_adapter();
        $platform = $channel_class->get_platform();
        $platform_business = $channel_class->get_platform_business();
        $ver      = $channel_class->get_ver();

        // 平台处理默认类
        $default_object_name = 'erpapi_'.$this->__channel_type.'_request_'.$this->__business;

        do {
            $tgV = $ver > 1 ? 'v'.$ver : '';

            // 自带处理类
            $object_name_arr = array('erpapi',$this->__channel_type,$adapter,$platform,'request',$tgV,$this->__business);
            $object_name = implode('_',array_filter($object_name_arr));

            try {
                if (class_exists($object_name)) {
                    $object_class = kernel::single($object_name,array($channel_class));

                    if (is_subclass_of($object_class, $default_object_name)) break;
                }
            } catch (Exception $e) {

            }

            $ver--;
        } while ($ver > 0);

        if (!is_object($object_class)) {
            if (false !== strpos($platform, 'shopex_')) {//自由体系特殊处理
                $parentPlatForm = 'shopex';
                // 自带处理类
                $object_name_arr = array('erpapi', $this->__channel_type, $adapter, $parentPlatForm, 'request', $this->__business);
                $object_name = implode('_', array_filter($object_name_arr));
                try {
                    if (class_exists($object_name)) {
                        $object_class = kernel::single($object_name,array($channel_class));

                        if (!is_subclass_of($object_class, $default_object_name)) {
                            unset($object_class);
                        }
                    }
                } catch (Exception $e) {

                }
            }
        }

        if($platform_business) {
            // array_push($object_name_arr, $platform_business);
            array_splice( $object_name_arr, -1, 0, [$platform_business]);
            // $object_name_arr = array('erpapi', $this->__channel_type, $adapter, $parentPlatForm, 'request', $platform_business, $this->__business);

            $object_name = implode('_',array_filter($object_name_arr));

            try {
                if (class_exists($object_name)) {
                    $pb_object_class = kernel::single($object_name,array($channel_class));

                    if (is_subclass_of($pb_object_class, $default_object_name)) {
                        $object_class = $pb_object_class;
                    }
                }
            } catch (Exception $e) {

            }
        }

        if (!is_object($object_class)) {
            $object_class = kernel::single($default_object_name,array($channel_class));
        }
       
        return $object_class;
    }
}