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
abstract class base_kvstore_abstract 
{
    
    /*
     * 生成经过处理的唯一key
     * @var string $key
     * @access public
     * @return string
     */
    public function create_key($key) 
    {
        return md5(base_kvstore::kvprefix() . $this->prefix . $key);
    }//End Function

    /**
     * 是否支持同步的自增单号处理
     */
    public function supportUUID() {

        return false;
    }

    /**
     * 返回类型值
     */
    public function getUUIDFix() {

        return '1';
    }
}//End Class