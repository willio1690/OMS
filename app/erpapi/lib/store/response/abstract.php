<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
abstract class erpapi_store_response_abstract
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
    
    //xml字符串转数组
    function xmlToArray($xml){
        libxml_disable_entity_loader(true);
        $xmlstring = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        $arr = json_decode(json_encode($xmlstring),true);
        return $arr;
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

    
    public function getBranchIdByBn($branch_bn){
        $branchMdl = app::get('ome')->model('branch');
        $branch = $branchMdl->db_dump(array('branch_bn' => $branch_bn, 'check_permission' => 'false'), 'branch_id,is_ctrl_store');

        if (!$branch){
            return false;
        }else{
            return $branch;
        }
    }
}
