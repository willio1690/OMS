<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_idaas_request_account extends erpapi_idaas_request_abstract
{
    private $_passwordPre = 'S#';

    /**
     * 同步账号
     * @param  [type] $sdf [description]
     * @return [type]      [description]
     */
    public function create($sdf)
    {
        $res = kernel::single('erpapi_router_request')->set('idaas', 'aliyun')->organization_get($sdf);
        if ($res['rsp'] == 'fail' && $res['res'] == 'EntityNotFound') {
            $res = kernel::single('erpapi_router_request')->set('idaas', 'aliyun')->organization_create($sdf);
        }

        if ($res['rsp'] == 'fail') {
            return $res;
        }

        $title = 'IDAAS同步账号';

        $params = array(
            'login_name'     => base_shopnode::node_id('ome') . '.' . $sdf['login_name'],
            'login_password' => $sdf['login_password'],
            'user_name'      => $sdf['user_name'],
            'external_id'    => base_shopnode::node_id('ome') . '_' . $sdf['user_id'],
            'email'          => $sdf['email'],
            'mobile'         => $sdf['mobile'],
            'belongs'        => json_encode(array(base_shopnode::node_id('ome'))),
            'enabled'        => $sdf['enabled'],
            'source'         => $sdf['source'],
            'domain'         => base_request::get_host(),
        );

        if ($params['login_password']) {
            $params['login_password'] = $this->_passwordPre . $params['login_password'];
        }

        $rsp = $this->__caller->call(IDAAS_ACCOUNT_CREATE, $params, array(), $title, 10, $sdf['login_name']);

        return $rsp;
    }

    /**
     * 同步账号
     * @param  [type] $sdf [description]
     * @return [type]      [description]
     */
    public function update($sdf)
    {
        $title = 'IDAAS同步账号';

        $params = array(
            'login_name'     => base_shopnode::node_id('ome') . '.' . $sdf['login_name'],
            'login_password' => $sdf['login_password'],
            'user_name'      => $sdf['user_name'],
            'external_id'    => base_shopnode::node_id('ome') . '_' . $sdf['user_id'],
            'email'          => $sdf['email'],
            'mobile'         => $sdf['mobile'],
            'belongs'        => json_encode(array(base_shopnode::node_id('ome'))),
            'enabled'        => $sdf['enabled'],
        );

        if ($params['login_password']) {
            $params['login_password'] = $this->_passwordPre . $params['login_password'];
        }

        $rsp = $this->__caller->call(IDAAS_ACCOUNT_UPDATE, $params, array(), $title, 10, $sdf['login_name']);

        return $rsp;
    }

    /**
     * 同步账号
     * @param  [type] $sdf [description]
     * @return [type]      [description]
     */
    public function delete($sdf)
    {
        $title = 'IDAAS删除账号';

        $params = array(
            'external_id' => base_shopnode::node_id('ome') . '_' . $sdf['user_id'],
        );

        $rsp = $this->__caller->call(IDAAS_ACCOUNT_DELETE, $params, array(), $title, 10, $sdf['login_name']);

        return $rsp;
    }

    /**
     * 同步账号
     * @param  [type] $sdf [description]
     * @return [type]      [description]
     */
    public function get($sdf)
    {
        $title = 'IDAAS查询账号';

        $params = array(
            'external_id' => base_shopnode::node_id('ome') . '_' . $sdf['user_id'],
        );

        $rsp = $this->__caller->call(IDAAS_ACCOUNT_GET, $params, array(), $title, 10, $sdf['login_name']);

        return $rsp;
    }

    /**
     * 同步账号
     * @param  [type] $sdf [description]
     * @return [type]      [description]
     */
    public function login($sdf)
    {
        if (defined('DEV_ENV')) {
            return array('rsp' => 'succ');
        }
        
        $shopMdl = app::get('ome')->model('shop');
    
        $seller_nick = [];
        foreach ($shopMdl->getList('addon', ['node_type'=>'taobao']) as $v) {
            if ($v['addon']['nickname']) {
                $seller_nick[] = (string)$v['addon']['nickname'];
            }
        }

        $user = app::get('desktop')->model('users')->db_dump(['user_id' => $sdf['member_id']], 'mobile');

        $title = 'IDAAS登陆账号';

        $params = array(
            'login_name'     => base_shopnode::node_id('ome') . '.' . $sdf['login_name'],
            'login_password' => $sdf['login_password'],
            'ati'            => kernel::single('base_component_request')->get_cookie('_ati'),
            'X-Client-IP'    => $_SERVER['HTTP_X_FORWARDED_FOR'] ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'],
            'merchantName'   => implode(',', $seller_nick),

            'loginFrom'         => kernel::this_url(1) . '?' . $_SERVER['QUERY_STRING'],
            'REMOTE_ADDR'       => $_SERVER['HTTP_X_FORWARDED_FOR'] ?: $_SERVER['REMOTE_ADDR'],
            'HTTP_USER_AGENT'   => $_SERVER['HTTP_USER_AGENT'],
            'mobile'            => $user['mobile'],
        );

        if ($params['login_password']) {
            $params['login_password'] = $this->_passwordPre . $params['login_password'];
        }

        $rsp = $this->__caller->call(IDAAS_ACCOUNT_LOGIN, $params, array(), $title, 10, $sdf['login_name']);

        // 二次验证
        if ($rsp['rsp'] == 'fail' && $rsp['data'] && $rsp['data']['needSecondFactor'] == true) {

            $idaas = $rsp['data'];

            // 发送短信
            $rsp = kernel::single('erpapi_router_request')->set('idaas', 'aliyun')->account_getMobileVerifyCode(array(
                'login_name' => $idaas['username'],
                'fid'        => $idaas['fid'],
            ));

            // 发送短信失败
            if ($rsp['rsp'] != 'succ') {
                return $rsp;
            }

            // $s = $_SESSION;

            // 缓存SESSION，10分钟
            // kernel::single('base_session')->appointStore($rsp['data']['fid'], 600);
            
            base_kvstore::instance('idaas')->store($rsp['data']['fid'], $_SESSION, 600);
            
            // 销毁SESSION
            kernel::single('base_session')->destory();

            $render = kernel::single('base_render');

            $render->pagedata['fid']         = $rsp['data']['fid'];
            $render->pagedata['username']    = $idaas['username'];
            $render->pagedata['phoneNumber'] = $idaas['phoneNumber'];

            $render->display('login_second_factor.html', 'desktop');

            exit;
        }

        return $rsp;
    }

    /**
     * 获取MobileVerifyCode
     * @param mixed $sdf sdf
     * @return mixed 返回结果
     */
    public function getMobileVerifyCode($sdf)
    {
        $title = 'IDAAS获取短信验证码';

        $params = array(
            'username'     => $sdf['login_name'],
            'fid'          => $sdf['fid'],
            'secondFactor' => 'SMS',
        );

        $rsp = $this->__caller->call(IDAAS_ACCOUNT_VERIFY_CODE, $params, array(), $title, 10, $sdf['login_name']);

        return $rsp;
    }

    /**
     * 检查MobileVerifyCode
     * @param mixed $sdf sdf
     * @return mixed 返回验证结果
     */
    public function checkMobileVerifyCode($sdf)
    {
        $title = 'IDAAS验证短信验证码';

        $s = kernel::single('base_session')->appointFetch($sdf['fid']);

        $user = [];

        $accountType = pam_account::get_account_type('desktop');
        if ($s['account'][$accountType]) {
            $user = app::get('desktop')->model('users')->db_dump(['user_id' => $s['account'][$accountType]], 'mobile');
        }

        $shopMdl = app::get('ome')->model('shop');

        $seller_nick = [];
        foreach ($shopMdl->getList('addon', ['node_type' => 'taobao', 'filter_sql' => 'node_id is not null and node_id !=""']) as $v) {
            if ($v['addon']['nickname']) {
                $seller_nick[] = (string) $v['addon']['nickname'];
            }
        }

        $params = array(
            'username'     => $sdf['login_name'],
            'code'         => $sdf['mobileverifycode'],
            'fid'          => $sdf['fid'],
            'secondFactor' => 'SMS',
            'ip'           => $_SERVER['HTTP_X_FORWARDED_FOR'] ?: $_SERVER["REMOTE_ADDR"],

            'loginFrom'         => kernel::this_url(1) . '?' . $_SERVER['QUERY_STRING'],
            'REMOTE_ADDR'       => $_SERVER['HTTP_X_FORWARDED_FOR'] ?: $_SERVER["REMOTE_ADDR"],
            'HTTP_USER_AGENT'   => $_SERVER['HTTP_USER_AGENT'],
            'mobile'            => $user['mobile'],
            'merchantName'      => (string) implode(',', $seller_nick),
        );
        $rsp = $this->__caller->call(IDAAS_ACCOUNT_CODE_VERIFY, $params, array(), $title, 10, $sdf['login_name']);
        return $rsp;
    }
}
