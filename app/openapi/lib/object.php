<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_object{

    /**
     *
     * 接口对象类名
     * @var string
     */
    static private $_class = null;

    /**
     *
     * 接口对象的函数名
     * @var string
     */
    static private $_method = null;

    /**
     *
     * 接口对象的路径
     * @var string
     */
    static private $_path = null;

    /**
     *
     * 接口对象的版本
     * @var string
     */
    static private $_version = 1;

    /**
     *
     * 接口对象的来源标识
     * @var string
     */
    static private $_flag = null;

    /**
     *
     * 接口对象的输出格式
     * @var string
     */
    static private $_type = 'json';


    /**
     *
     * 接口对象的输出编码
     * @var string
     */
    static private $_charset = 'utf-8';


    /**
     *
     * 接口对象的应用级参数
     * @var array
     */
    static private $_appParams = array();

    /**
     *
     * 接口对象的类实例化对象
     * @var object
     */
    static private $_funcObject = null;

    /**
     *
     * 接口对象的参数类实例化对象
     * @var object
     */
    static private $_paramsObject = null;

    /**
     *
     * 接口对象的输出模板类实例化对象
     * @var object
     */
    static private $_templateObject = null;

    /**
     *
     * 接口对象的来源标识的配置信息
     * @var array
     */
    static private $_conf = null;

    /**
     *
     * 接口对象的函数处理后的原始数据
     * @var array
     */
    static private $_original_data = null;

    /**
     *
     * 接口对象的原始数据模板格式化后的输出
     * @var array
     */
    static private $_output_data = null;

    /**
     *
     * 实例化接口对象
     * @param array $sysParams
     * @param array $appParams
     * @param string $msg
     */
    public function instance($sysParams,$appParams,&$code,&$sub_msg){

        //设置系统参数
        $this->setSysParams($sysParams);

        //加载接口类
        if(!$this->loadModule()){
            $code = 'e000005';
            return false;
        }

        //加载接口参数类
        if(!$this->loadParamModule()){
            $code = 'e000005';
            return false;
        }

        //设置应用参数
        if(!$this->setAppParams($appParams,$sub_msg)){
            $code = 'e000006';
            return false;
        }

        //加载接口配置信息
        if(!$this->loadTemplateModule()){
            $code = 'e000007';
        }

        return true;
    }

    /**
     *
     * 设置系统级参数
     * @param array $params
     */
    private function setSysParams($params){
        self::$_flag = $params['flag'];
        self::$_class = $params['class'];
        self::$_method = $params['method'];
        self::$_path = $params['path'];
        self::$_version = $params['ver'];
        self::$_type = $params['type'];
        self::$_charset = $params['charset'];
    }

    /**
     *
     * 实例化接口类
     */
    private function loadModule(){
        if(isset(self::$_funcObject[self::$_flag])){
            return self::$_funcObject[self::$_flag];
        }else{
            if(self::$_path){
                $endClass = substr(self::$_class, strrpos(self::$_class, '.')+1);
                $className =  sprintf("openapi_api_function_v%s_%s_%s", self::$_version,self::$_path,  $endClass);
                try{
                    $obj = new $className();
                }catch(Throwable $e){
                    $className =  sprintf("%s_openapi_function_v%s_%s", self::$_path, self::$_version, $endClass);
                    $obj = new $className();
                }
            }else{
                $className =  sprintf("openapi_api_function_v%s_%s", self::$_version, self::$_class);
                $obj = new $className();
            }
            if(is_object($obj) && method_exists($obj,self::$_method)){
                self::$_funcObject[self::$_flag] = $obj;
                return true;
            }else{
                return false;
            }
        }
    }

    /**
     *
     * 实例化接口参数类
     */
    private function loadParamModule(){
        if(isset(self::$_paramsObject[self::$_flag])){
            return self::$_paramsObject[self::$_flag];
        }else{
            if(self::$_path){
                $endClass = substr(self::$_class, strrpos(self::$_class, '.')+1);
                $className =  sprintf("openapi_api_params_v%s_%s_%s", self::$_version, self::$_path,  $endClass);
                try{
                    $obj = new $className();
                }catch(Throwable $e){
                    $className =  sprintf("%s_openapi_params_v%s_%s", self::$_path, self::$_version, $endClass);
                    $obj = new $className();
                }
            }else{
                $className =  sprintf("openapi_api_params_v%s_%s", self::$_version, self::$_class);
                $obj = new $className();
            }
            if(is_object($obj)){
                self::$_paramsObject[self::$_flag] = $obj;
                return true;
            }else{
                return false;
            }
        }
    }

    /**
     *
     * 设置接口应用级参数
     */
    public function setAppParams($params,&$sub_msg){
        if($this->checkParams($params,$sub_msg)){
            self::$_appParams = $params;
            return true;
        }else{
            return false;
        }
    }

    /**
     *
     * 检查接口应用级参数
     */
    private function checkParams($params,&$sub_msg){
        return self::$_paramsObject[self::$_flag]->checkParams(self::$_method,$params,$sub_msg);
    }


    /**
     *
     * 实例化接口输出模板类
     */
    private function loadTemplateModule(){

        //加载标识的配置信息
        $this->loadConf();

        //根据配置信息识别调用的输出模板类to do $_templateObject[self::$_flag]
        return true;
    }

    /**
     *
     * 加载来源标识的配置信息
     */
    private function loadConf(){
        self::$_conf = openapi_setting::getConf(self::$_flag);
    }

    /**
     *
     * 执行接口类的处理流程
     */
    public function process(&$result,&$code,&$sub_msg){
        //输入参数获取原始数据
        $this->input($code,$sub_msg);

        //判断应用级处理结果是否成功
        if(self::$_original_data === false){
            return false;
        }

        //按照模板或定义调整输出数据
        $this->output();

        $result = self::$_output_data;
        return true;
    }

    /**
     *
     * 执行接口类的函数处理
     */
    private function input(&$code,&$sub_msg){
        
        self::$_original_data = self::$_funcObject[self::$_flag]->{self::$_method}(self::$_appParams,$code,$sub_msg);
    }

    /**
     *
     * 执行接口类的模板格式化输出
     */
    private function output(){

        if(isset(self::$_templateObject[self::$_flag])){
            //根据模板及定义调整输出to do
        }else{
            self::$_output_data = self::$_original_data;
        }
    }

}