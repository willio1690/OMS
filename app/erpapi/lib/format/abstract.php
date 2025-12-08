<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

abstract class erpapi_format_abstract{

    abstract public function data_encode($data);

    abstract public function data_decode($data);
}