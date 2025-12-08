<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2021/4/6 15:05:23
 * @describe:
 * loginUrl 后跳转地址 http://oms-nestle/index.php?ctl=passport&act=accountCode
 * ============================
 */
class erpapi_account_request_user extends erpapi_account_request_abstract {

    private static function claimDefinition($k)
    {
        $definition = [
            'logout_redirect_uri'   => 'redirect_uri',
            'userinfo_uname'        => 'username',
            'userinfo_name'         => 'displayname',
            'userinfo_mobile'       => 'mobile',
            'userinfo_uid'          => 'uid',
        ];

        $oidcInfo = app::get('ome')->getConf('pam.passport.oidc.info');
        switch (strtolower($oidcInfo['oidctype'])) {
            case 'ciam':
                $definition['logout_redirect_uri'] = 'post_logout_redirect_uri';
                $definition['userinfo_uname']      = 'phone_number';
                $definition['userinfo_name']       = 'name';
                $definition['userinfo_mobile']     = 'phone_number';
                $definition['userinfo_uid']        = 'sub';
                break;
                
            case 'okta':
                $definition['userinfo_uname']      = 'preferred_username';
                $definition['userinfo_name']       = 'name';
                // $definition['userinfo_mobile']     = 'phone_number';
                $definition['userinfo_uid']        = 'sub';
                $definition['userinfo_search']     = 'email';
                break;
        }


        return (string) $definition[$k];
    }

    /**
     * loginUrl
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */

    public function loginUrl($sdf) {
        $oidc = app::get('ome')->getConf('pam.passport.oidc.enable');
        if($oidc != 'true') {
            return $this->error('未开启oidc', 100);
        }
        $oidcInfo = app::get('ome')->getConf('pam.passport.oidc.info');
        if(!$oidcInfo['client_id']) {
            return $this->error('need client_id', 100);
        }
        if(!$oidcInfo['authorization']) {
            return $this->error('need authorization', 100);
        }
        $params = ['client_id'=>$oidcInfo['client_id'], 'response_type'=>'code', 'scope' => 'openid', 'state'=>time()];

        if($oidcInfo['redirect']) {
            $params['redirect_uri'] = $oidcInfo['redirect'];
        }

        if (in_array(strtolower($oidcInfo['oidctype']), ['ciam', 'okta'])) {
            $params['scope'] = 'openid phone profile';
        }

        $url = $oidcInfo['authorization'] . '?' . http_build_query($params);
        if($oidcInfo['to_out'] == 'true') {
            header('Location:' . $url);exit();
        }
        return $this->succ('成功', 0, $url);
    }

    /**
     * loginOutUrl
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function loginOutUrl($sdf) {
        $oidc = app::get('ome')->getConf('pam.passport.oidc.enable');
        if($oidc != 'true') {
            return $this->error('未开启oidc', 100);
        }
        $oidcInfo = app::get('ome')->getConf('pam.passport.oidc.info');
        if(!$oidcInfo['client_id']) {
            return $this->error('need client_id', 100);
        }
        if(!$oidcInfo['endsession']) {
            return $this->error('need endsession', 100);
        }
        $params = ['client_id'=>$oidcInfo['client_id'], 'response_type'=>'code', 'scope' => 'openid'];

        $redirectUriField = self::claimDefinition('logout_redirect_uri');
        if($oidcInfo['redirect']) {
            $params[$redirectUriField] = $oidcInfo['redirect'];
        }

        if ($redirectUriField == 'post_logout_redirect_uri') {
            $params['id_token_hint'] = $_SESSION['id_token'];
            
            if (!$params['id_token_hint']){
                return $this->error('need id_token', 100);
            }
        }

        if (in_array(strtolower($oidcInfo['oidctype']), ['ciam', 'okta'])) {
            $params['scope'] = 'openid phone profile';
        }

        $url = $oidcInfo['endsession'] . '?' . http_build_query($params);

        return $this->succ('成功', 0, $url);
    }

    /**
     * login
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function login($sdf) {
        $tokenRs = $this->getToken($sdf);
        if($tokenRs['rsp'] != 'succ') {
            return $tokenRs;
        }
        $info = $this->getInfo($tokenRs['data']);
        if($info['rsp'] != 'succ') {
            return $info;
        }
        $permission = $this->getPermission($tokenRs['data']);
        if($permission['rsp'] != 'succ') {
            return $permission;
        }
        return ['rsp'=>'succ'];
    }

    /**
     * 获取Token
     * @param mixed $sdf sdf
     * @return mixed 返回结果
     */
    public function getToken($sdf) {
        $title = '获取token';
        $method = 'token';
        $params = ['grant_type'=>'authorization_code', 'code'=>$sdf['code']];
        $oidcInfo = app::get('ome')->getConf('pam.passport.oidc.info');
        if($oidcInfo['redirect']) {
            $params['redirect_uri'] = $oidcInfo['redirect'];
        }
        $result= $this->__caller->call($method,$params,array(),$title,500,$sdf['code']);
        $data = @json_decode($result['data'], 1);
        if($data['access_token']) {
            return ['rsp'=>'succ', 'data'=>$data];
        }
        return ['rsp'=>'fail', 'msg'=>'获取token失败'];
    }

    /**
     * refreshToken
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function refreshToken($sdf) {
        $title = '刷新token';
        $method = 'token';
        $params = ['grant_type'=>'refresh_token', 'refresh_token'=>$sdf['refresh_token']];
        $result= $this->__caller->call($method,$params,array(),$title,500,$sdf['access_token']);
        $data = @json_decode($result['data'], 1);
        if($data['access_token']) {
            $data['refresh'] = 1;
            return $this->getInfo($data);
        }
        return ['rsp'=>'fail', 'msg'=>'刷新token失败'];
    }

    /**
     * 获取Info
     * @param mixed $sdf sdf
     * @return mixed 返回结果
     */
    public function getInfo($sdf) {
        $title = '获取用户信息';
        $method = 'userinfo';
        $result= $this->__callerGet($method,['access_token'=>$sdf['access_token']], $title);
        $data = @json_decode($result['data'], 1);
        if($data['code']) {
            if($data['code'] == '110' && !$sdf['refresh']) {
                return $this->refreshToken($sdf);
            }
            return ['rsp'=>'fail', 'msg'=>$data['msg']];
        }
        $data = $data['data'] ? : $data;

        $oidcInfo = app::get('ome')->getConf('pam.passport.oidc.info');
        $unamefield  = self::claimDefinition('userinfo_uname');
        $searchfield = self::claimDefinition('userinfo_search');

        if(empty($data['username'])) {
            if($data[$unamefield]) {
                $data['username'] = $data[self::claimDefinition('userinfo_uname')];
                $data['nickname'] = $data[self::claimDefinition('userinfo_name')];
                $data['mobile']   = $data[self::claimDefinition('userinfo_mobile')];
            }
            
            if (!$data['username'] && $sdf['id_token']) {
                $jwt = $sdf['id_token'];
                $header = base64_decode(strtr(substr($jwt, 0, strpos($jwt, '.')), '-_', '+/'));
                $idToken = base64_decode(strtr(substr($jwt, strpos($jwt, '.') + 1, strpos($jwt, '.', strpos($jwt, '.') + 1) - strpos($jwt, '.') - 1), '-_', '+/'));
                
                if ($idToken[$unamefield]) {
                    $data['username'] = $idToken[$unamefield];
                }
            }
            
            if (!$data['username'] && $sdf['access_token']) {
                $jwt = $sdf['access_token'];
                $header = base64_decode(strtr(substr($jwt, 0, strpos($jwt, '.')), '-_', '+/'));
                $idToken = base64_decode(strtr(substr($jwt, strpos($jwt, '.') + 1, strpos($jwt, '.', strpos($jwt, '.') + 1) - strpos($jwt, '.') - 1), '-_', '+/'));
                
                if ($idToken[$unamefield]) {
                    $data['username'] = $idToken[$unamefield];
                }
            }
        }

        if(empty($data['username'])) {
            return ['rsp'=>'fail', 'msg'=>'缺失用户名:'.json_encode($result).json_encode($idToken)];
        }

        $data['uid'] = $data[self::claimDefinition('userinfo_uid')];

        $pamMdl  = app::get('pam')->model('account');
        $authMdl = app::get('pam')->model('auth');
        $userMdl = app::get('desktop')->model('users');

        // 判断管理员是否存在
        if (in_array(strtolower($oidcInfo['oidctype']), ['ciam','okta'])) {
           $login_name = $data['username'];
           
           if ($searchfield){
                $user = $userMdl->db_dump([$searchfield => $login_name], 'user_id');
                
                if ($user){
                    $account = $pamMdl->db_dump($user['user_id']);
                    
                    $login_name = $account['login_name'];
                }
           } else {
               $account = $pamMdl->db_dump(['login_name' => $login_name]);
           }
           
            if (!$account) {
                return ['rsp'=>'fail', 'msg'=>'账号【'.$data['username'].'】不存在，请联系管理员！'];
            }

            if (!$authMdl->db_dump(['module_uid'=>$data['uid']], 'auth_id,account_id')) {
                $authInData = [
                    'account_id' => $account['account_id'],
                    'module_uid' => $data['uid'],
                    'module'     => 'account',
                    'data'       => json_encode($data)
                ];
                $authMdl->insert($authInData);
            }
        }

        $isSuper    = $login_name == trim($oidcInfo['super_account']) ? 1 : 0;
        $session_id = kernel::single('base_session')->sess_id();

        $accountType = pam_account::get_account_type('desktop');
        if($old = $authMdl->db_dump(['module_uid'=>$data['uid']], 'auth_id,account_id')) {
            $_SESSION['account'][$accountType]  = $old['account_id'];
            $_SESSION['id_token']               = $sdf['id_token'];
            $_SESSION['login_trust']            = true;

            $user = $userMdl->db_dump($old['account_id'], 'session_id');

            //注销同账号,其它电脑上登录的session_id
            if ('false' !== app::get('ome')->getConf('desktop.account.equal.restrict')
                && $user['session_id']
                && $user['session_id'] != $session_id
            ) {
                kernel::single('base_session')->deleteSessionId($user['session_id']);
            }

            $userMdl->update(['super'=>$isSuper, 'session_id' => $session_id],['user_id'=>$old['account_id']]);
            return ['rsp'=>'succ'];
        }

        $inData = [
            'pam_account'   => [
                'login_name'        => $data['username'],
                'account_type'      => $accountType,
                'login_password'    => pam_encrypt::get_encrypted_password(time().uniqid(),$accountType)
            ],
            'name'          => $data['nickname'],
            'status'        => 1,
            'mobile'        => (string) $data['mobile'],
            'super'         => $isSuper,
            'session_id'    => $session_id,
        ];

        $userMdl->save($inData);
        $authInData = [
            'account_id' => $inData['pam_account']['account_id'],
            'module_uid' => $data['uid'],
            'module'     => 'account',
            'data'       => json_encode($data)
        ];
        $authMdl->insert($authInData);
        $_SESSION['account'][$accountType]  = $inData['pam_account']['account_id'];
        $_SESSION['id_token']               = $sdf['id_token'];
        $_SESSION['login_trust']            = true;

        return ['rsp'=>'succ'];
    }

    /**
     * 获取Permission
     * @param mixed $sdf sdf
     * @return mixed 返回结果
     */
    public function getPermission($sdf) {
        $title = '获取用户权限';
        $method = 'permission';
        $oidcInfo = app::get('ome')->getConf('pam.passport.oidc.info');
        if(!$oidcInfo[$method]) {
            return ['rsp'=>'succ'];
        }
        $result= $this->__callerGet($method,['access_token'=>$sdf['access_token']]);
        $data = @json_decode($result['data'], 1);
        if($data['code']) {
            return ['rsp'=>'fail', 'msg'=>$data['msg']];
        }
        $accountType = pam_account::get_account_type('desktop');
        $userId = $_SESSION['account'][$accountType];
        $operationOpsObj = app::get('ome')->model('operation_ops');
        $operationOpsObj->delete(array('op_id' => $userId));
        app::get('ome')->model('branch_ops')->delete(array('op_id' => $userId));
        $permission = [];
        foreach ($data['data'] as $v) {
            $tmpPer = $this->getUserPermission($v);
            $permission = array_merge($permission, $tmpPer);
        }
        $_SESSION['account']['user_permission'] = $permission;
        return ['rsp'=>'succ'];
    }

    protected function getUserPermission($v) {
        $per = [];
        $accountType = pam_account::get_account_type('desktop');
        $userId = $_SESSION['account'][$accountType];
        if($v['extId'] == 'operation_organization') {
            $operationOpsObj = app::get('ome')->model('operation_ops');
            foreach ($v['children'] as $vv) {
                //保存现有
                $addOperPer = array(
                    'org_id' => $vv['path'],
                    'op_id'  => $userId,
                );
                $operationOpsObj->insert($addOperPer);
            }
            return $per;
        }
        if($v['extId'] == 'branch') {
            foreach ($v['children'] as $vv) {
                $t_data = array('branch_id' => $vv['path'], 'op_id' => $userId);
                app::get('ome')->model('branch_ops')->save($t_data);
            }
            return $per;
        }
        if($v['children']) {
            foreach ($v['children'] as $vv) {
                $temPer = $this->getUserPermission($vv);
                $per = array_merge($per, $temPer);
            }
        }
        if($v['path']) {
            $per[] = $v['path'];
        }
        return $per;
    }

    protected function __callerGet($method,$params, $title = '') {
        $headers = array(
            'Connection' => 10,
        );
        // 应用级参数
        $query_params = $this->__configObj->get_query_params($method, $params);

        if ($query_params['headers']) {
            $headers = array_merge($headers, (array) $query_params['headers']);
            unset($query_params['headers']);
        }
        $url = $this->__configObj->get_url($method, $query_params, 0);
        $response = kernel::single('base_httpclient')->set_timeout(500)->get($url, $headers);
        
        // 记日志
        $apilogModel = app::get('ome')->model('api_log');
        $log_id = $apilogModel->gen_id();
        $logsdf = [
            'log_id'        => $log_id,
            'task_name'     => $title ?: $method,
            'status'        => 'success',
            'worker'        => $method,
            'params'        => json_encode($params),
            'response'      => is_array($response) ? json_encode($response) : $response,
            'api_type'      => 'request',
            'original_bn'   => $method,
        ];
        $apilogModel->insert($logsdf);

        return ['rsp'=>'succ', 'data'=>$response];
    }

    /**
     * syncPermission
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function syncPermission($sdf) {
        $title = '同步权限';
        $method = 'syncpermission';
        $oidcInfo = app::get('ome')->getConf('pam.passport.oidc.info');
        if(!$oidcInfo[$method]) {
            return ['rsp'=>'succ'];
        }
        $data =[];
        foreach ($sdf as $v) {
            $data[] = $this->getOnePermission($v);
        }
        $params = ['data'=>json_encode($data, JSON_UNESCAPED_UNICODE)];
        $result= $this->__caller->call($method,$params,array(),$title,500,'syncPermission');
        $data = @json_decode($result['data'], 1);
        if($data['code']) {
            return ['rsp'=>'fail', 'data'=>'同步权限失败'];
        }
        return ['rsp'=>'fail', 'msg'=>'获取token失败'];
    }

    protected function getOnePermission($v) {
        $children = [];
        if($v['permissions']) {
            foreach ($v['permissions'] as $vv) {
                $children[] = $this->getOnePermission($vv);
            }
        }
        $tmpData = [
            "aliasName"=> $v['workground'] ? : $v['menu_title'],
            "children"=> $children,
            "createdAt"=> "",
            "id"=> ($v['menu_type'] ? $v['menu_type'].'-' : '').$v['menu_id'],
            "isDeleted"=> 0,
            "name"=> $v['menu_title'],
            "num"=> count($children),
            "path"=> $v['permission'] ? : (string)$v['addon'],
            "updatedAt"=> ""
        ];
        return $tmpData;
    }
}