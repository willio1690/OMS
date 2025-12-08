<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @description 门店优选调用触发类
 * @access public
 * @param void
 * @return void
 */
class o2o_autostore{

    /**
     * 
     * 静态私有变量优选模式
     * @var array
     */
    static private $__Mode = '';

    /**
     * 
     * 静态私有变量优选处理对象
     * @var array
     */
    static private $__ModeObj = null;

    /**
     * 
     * 静态私有变量优选模式白名单定义
     * @var array
     */

    static private $__Modes = array(
        'area'=>array('type' => 'area','name' => "按区域覆盖"),
        //'lbs'=>array('type' => 'lbs','name' => "按LBS定位"),
    );

    /**
     * 获取发送短信的触发事件类型列表
     *
     * @param void
     * @return void
     */

    static public function getAutoStoreModes(){
        return self::$__Modes;
    }

    /**
     * 获取TmplConfByMode
     * @param mixed $mode mode
     * @return mixed 返回结果
     */
    public function getTmplConfByMode($mode){
        $params['mode'] = $mode;

        //初始化服务
        if(!$this->initService($params)){
            return false;
        }

        return $this->getTmplConf();
    }

    private function getTmplConf(){
        return self::$__ModeObj->getTmplConf();
    }

    /**
     * 门店优选处理方法
     * 
     * @param void
     * @return void
     */
    function matchStoreBranch($params, &$error_msg){

        //初始化优选门店模式
        if(!$this->initService($params)){
            return false;
        }

        //检查相应参数
        if(!$this->checkParams($params)){
            return false;
        }

        //处理门店优选
        return $this->process($params, $error_msg);
    }

    /**
     * 初始化优选门店模式
     * 
     * @param array $params
     * @return boolean true/false
     */
    private function initService($params){
        //必要参数触发的优选模式不能为空
        if(!isset($params['mode']) || empty($params['mode']) || !isset(self::$__Modes[$params['mode']])){
            return false;
        }else{
            self::$__Mode = $params['mode'];
        }

        $class_name = sprintf('o2o_autostore_type_%s', self::$__Mode);
        try{
            if (class_exists($class_name)){
                self::$__ModeObj = kernel::single($class_name);
                return true;
            }else{
                return false;
            }
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 检查门店优选的参数
     * 
     * @param array $params
     * @return boolean true/false
     */
    private function checkParams(&$params){
        return self::$__ModeObj->checkParams($params);
    }

    /**
     * 具体处理门店优选
     * 
     * @param array $params
     * @param array $sendParams
     * @return boolean true/false
     */
    private function process($params, &$error_msg){
        return self::$__ModeObj->process($params, $error_msg);
    }
}