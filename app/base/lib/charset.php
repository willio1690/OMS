<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


/*
 * @package base
 * @copyright Copyright (c) 2010, shopex. inc
 * @author edwin.lzh@gmail.com
 * @license 
 */
 
class base_charset{
    
    private $_instance = null;

    function __construct() 
    {
        $obj = kernel::service('base_charset');
        if($obj instanceof base_charset_interface){ 
            $this->set_instance($obj);
        }
    }//End Function

    /**
     * 设置_instance
     * @param mixed $obj obj
     * @return mixed 返回操作结果
     */
    public function set_instance(&$obj) 
    {
        $this->_instance = $obj;
    }//End Function
    
    /**
     * 获取_instance
     * @return mixed 返回结果
     */
    public function get_instance() 
    {
        return $this->_instance;
    }//End Function

    /**
     * local2utf
     * @param mixed $strFrom strFrom
     * @param mixed $charset charset
     * @return mixed 返回值
     */
    public function local2utf($strFrom,$charset='zh') 
    {
        return $this->_instance->local2utf($strFrom, $charset);
    }//End Function

    /**
     * utf2local
     * @param mixed $strFrom strFrom
     * @param mixed $charset charset
     * @return mixed 返回值
     */
    public function utf2local($strFrom,$charset='zh') 
    {
        return $this->_instance->utf2local($strFrom, $charset);
    }//End Function

    /**
     * u2utf8
     * @param mixed $str str
     * @return mixed 返回值
     */
    public function u2utf8($str) 
    {
        return $this->_instance->u2utf8($str);
    }//End Function

    /**
     * utf82u
     * @param mixed $str str
     * @return mixed 返回值
     */
    public function utf82u($str) 
    {
        return $this->_instance->utf82u($str);
    }//End Function
	
    /**
     * replace_utf8bom
     * @param mixed $str str
     * @return mixed 返回值
     */
    public function replace_utf8bom( $str )  
	{
		return $this->_instance->replace_utf8bom($str);
	}
	
    /**
     * is_utf8
     * @param mixed $str str
     * @return mixed 返回值
     */
    public function is_utf8( $str )
	{
		return $this->_instance->is_utf8($str);
	}
}
