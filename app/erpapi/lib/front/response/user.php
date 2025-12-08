<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 *  前后端分离，管理员类
 *
 * @author <chenping@shopex.cn>
 * @time 2020-11-18T19:26:39+08:00
 */
class erpapi_front_response_user extends erpapi_front_response_abstract
{
    /**
     * 管理员登陆，method=front.user.login
     *
     * @param array $params (uname="test" password="test")
     * @return array (uname="test" password="test")
     * @author
     **/

    public function login($params)
    {
        $this->__apilog['title']       = '管理员登陆';
        $this->__apilog['original_bn'] = $params['uname'];

        if (!$params['uname']) {
            $this->__apilog['result']['msg'] = '缺少用户名';
            return false;
        }

        if (!$params['password']) {
            $this->__apilog['result']['msg'] = '缺少用户密码';
            return false;
        }

        $params['uname']    = trim($params['uname']);
        $params['password'] = trim($params['password']);

        return $params;
    }

    /**
     * 用户登出，method=front.user.logout
     * 需要SESSION授权
     *
     * @param array $params
     * @return void
     * @author
     **/
    public function logout($params)
    {
        return $params;
    }
}
