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

interface base_charset_interface{
    
    public function local2utf($strFrom,$charset='zh');

    public function utf2local($strFrom,$charset='zh');

    public function u2utf8($str);

    public function utf82u($str);
}