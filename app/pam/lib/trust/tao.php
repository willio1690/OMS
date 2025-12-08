<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class pam_trust_tao {

    /**
     * login
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function login($params=null){
        if(!$params) return false;
        foreach(kernel::servicelist('api_login') as $k=>$passport){
            return $passport->login($params);
        }
    }

}
