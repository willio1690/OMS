<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class desktop_site
{
    /**
     * 获取Info
     * @return mixed 返回结果
     */
    public function getInfo()
    {
        $info = app::get('desktop')->getConf('siteInfo');

        $logoUrl = '';
        if ($info['logoUrl']) {
            list($logoUrl) = explode('|', $info['logoUrl']);
        }
        
        $info['logoUrl'] = $logoUrl;
        
        base_kvstore::instance('menudefine')->fetch('version', $menuVer);
        $info['menuVer'] = $menuVer?:'20240722000000';

        return $info;
    }
}