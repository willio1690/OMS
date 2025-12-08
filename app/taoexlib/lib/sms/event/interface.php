<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @description 短信事件接口类
 * @access public
 * @param void
 * @return void
 */
interface  taoexlib_sms_event_interface
{
    public function checkParams(&$params, &$error_msg);

    public function formatContent($params, &$sendParams, &$error_msg);
}