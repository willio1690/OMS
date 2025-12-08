<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


interface base_admin_backup{
    public function start();
    public function end();
    public function next();
    public function get();
}
