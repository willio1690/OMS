<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
abstract class erpapi_qimen_response_abstract
{
    protected $__channelObj;

    public $__apilog;

    const MAX_LIMIT = 100;
    
    /**
     * 初始化
     * @param erpapi_channel_abstract $channel channel
     * @return mixed 返回值
     */
    public function init(erpapi_channel_abstract $channel)
    {
        $this->__channelObj = $channel;

        return $this;
    }
    
    /**
     * 去首尾空格
     *
     * @param Array
     * @return Array
     * @author
     **/
    public static function trim(&$arr)
    {
        foreach ($arr as $key => &$value) {
            if (is_array($value)) {
                self::trim($value);
            } elseif (is_string($value)) {
                $value = trim($value);
            }
        }
    }
    
    /**
     * 过滤空
     *
     * @return void
     * @author
     **/
    public function filter_null($var)
    {
        return !is_null($var) && $var !== '';
    }
    
    /**
     * qimen通过Body传输数据
     *
     * @param $params
     * @return void
     */
    public function _getResponseData($params)
    {
        // 获取数据
        if(isset($params['data'])){
            return $params['data'];
        }else{
            return $params;
        }
    }
}
