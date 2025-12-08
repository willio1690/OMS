<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class desktop_user_auth
{
    /**
     * 同步账号给最第三方
     *
     * @return void
     * @author
     **/
    public function sync_account($account, $action)
    {
        if (!defined('IDAAS_LOGIN') || true != constant("IDAAS_LOGIN") ) {
            return [true];
        }

        if (!$account['account_id']) {
            return array(false, '缺少账号ID');
        }

        $user = app::get('desktop')->model('users')->db_dump(array('user_id' => $account['account_id']));
        if (!$user) {
            return array(false, '账号不存在');
        }

        return $this->_idaas($account, $user, $action);
    }

    /**
     * 阿里云IDAAS账号同步
     *
     * @return void
     * @author
     **/
    private function _idaas($account, $user, $action)
    {
        $authMdl = app::get('pam')->model('auth');

        // 判断是否已经添加过
        $authinfo = array();
        if ($account['account_id']) {
            $authinfo = $authMdl->dump(array('account_id' => $account['account_id'], 'module' => 'idaas'));

            if ($action == 'add' && $authinfo) {
                return array(true, '账号已同步IDAAS');
            }

            if ($action != 'add' && !$authinfo) {
                $action = 'add';
            }
        }

        switch ($action) {
            case 'add':
                $sdf = array(
                    'login_name'     => $account['login_name'],
                    'login_password' => $account['login_password'],
                    'user_id'        => $user['user_id'],
                    'user_name'      => $user['name'],
                    'mobile'         => $user['mobile'],
                    'enabled'        => $user['status'] == '0' ? 'false' : 'true',
                    'source'         => $account['source'],
                );

                $res = kernel::single('erpapi_router_request')->set('idaas', 'aliyun')->account_create($sdf);
                if ($res['rsp'] == 'fail' || !$res['data']['id']) {
                    return array(false, $res['err_msg']);
                }

                $authData = array(
                    'account_id' => $account['account_id'],
                    'module_uid' => $res['data']['id'],
                    'module'     => 'idaas',
                    'data'       => serialize($res['data']),
                );

                $authMdl->insert($authData);

                return array(true, '账号同步IDAAS成功');

                break;

            case 'update':
                $sdf = array(
                    'user_id'    => $user['user_id'],
                    'login_name' => $account['login_name'],
                    // 'login_password'    => $account['login_password'],
                    'user_name'  => $user['name'],
                    'mobile'     => $user['mobile'],
                    'enabled'    => $user['status'] == '0' ? 'false' : 'true',
                );

                $res = kernel::single('erpapi_router_request')->set('idaas', 'aliyun')->account_update($sdf);
                if ($res['rsp'] == 'fail') {
                    return array(false, $res['err_msg']);
                }

                return array(true, '账号同步IDAAS成功');
                break;
            case 'password':
                $sdf = array(
                    'user_id'        => $user['user_id'],
                    'login_name'     => $account['login_name'],
                    'login_password' => $account['login_password'],
                    'user_name'      => $user['name'],
                    'mobile'         => $user['mobile'],
                    'enabled'        => $user['status'] == '0' ? 'false' : 'true',
                    'source'         => (string)$account['source'],
                    'domain'         => (string)$account['domain'],
                );

                $res = kernel::single('erpapi_router_request')->set('idaas', 'aliyun')->account_update($sdf);
                if ($res['rsp'] == 'fail') {
                    return array(false, $res['err_msg']);
                }
                break;
        }

        return array(true);
    }
}
