<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


interface pam_interface_passport{

    function get_name();
    function get_login_form($auth,$appid,$view,$ext_pagedata=array());
    function login($auth,&$usrdata);
    function loginout($auth,$backurl="index.php");
    function get_data();
    function get_id();
    function get_expired();

}
