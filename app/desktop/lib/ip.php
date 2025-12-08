<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class desktop_ip{
    /**
     * IP限制
     * 
     * @return void
     * @author 
     */
    public function limit($ip_white)
    {
        $ip_addr = array();
        if ($ip_white['ip_addr']) $ip_addr = array_filter(explode("\n", $ip_white['ip_addr']));
        if (!array_intersect($this->seg(), $ip_addr)) {
            return true;
        }

        return false;
    }

    /**
     * IP段
     * 
     * @return void
     * @author 
     */
    public function seg()
    {
        $ip_list = array();

        $ip = $this->getIp();
        if(strpos($ip, ',')) {
            list($ip, ) = explode(',', $ip);
        }
        $ip_seg = explode('.', $ip);

        $ip_list[] = $ip;
        $ip_list[] = $ip_seg[0].'.'.$ip_seg[1].'.'.$ip_seg[2].'.'.'*';
        $ip_list[] = $ip_seg[0].'.'.$ip_seg[1].'.*'.'.*';

        return $ip_list;
    }

    public function getIp() {
        return kernel::single('base_component_request')->get_server('HTTP_X_FORWARDED_FOR') ?
            : kernel::single('base_component_request')->get_server('REMOTE_ADDR');;
    }
}
