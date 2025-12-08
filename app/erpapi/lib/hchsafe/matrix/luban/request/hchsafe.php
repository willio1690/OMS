<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_hchsafe_matrix_luban_request_hchsafe extends erpapi_hchsafe_request_hchsafe
{
    private $__unikey = '';
    private $__nodeId = '';

    /**
     * __construct
     * @return mixed 返回值
     */
    public function __construct()
    {

        parent::__construct();

        // 判断是否有京东店铺
        $shop = app::get('ome')->model('shop')->dump(array(
            'node_type'  => 'luban',
            'disabled'   => 'false',
            'filter_sql' => 'node_id is not null and node_id !=""',
        ));

        if ($shop) {
            $this->__unikey = $shop['addon']['user_id'] ?: '000000';
            $this->__nodeId = $shop['node_id'];
        }
        $this->__device_id = $this->__ati;
    }

    /**
     * 登录日志
     * 
     * @return void
     * @author
     * */
    public function login($sdf)
    {
        if (!$this->__is_config_mq() || !$this->__unikey || !trim($sdf['uname'])) {
            return $this->succ();
        }

        
        $title = '抖音安全规范日志-登录';

        if ($sdf['member_id']) {
            $user = app::get('desktop')->model('users')->db_dump(['user_id' => $sdf['member_id']], 'mobile');
        }
        
        //params
        $params = [
            'account_id'          => $this->__prefixUname . (string) $sdf['uname'],
            'shop_ids'            => [$this->__unikey],
            'login_success'       => $_SESSION['last_error'] ? false : true,
            'login_message'       => $_SESSION['last_error'] ?: '登录成功',
            'operate_time'        => time(),
            'mobile'              => $user['mobile'] ? (string) $user['mobile'] : '13811111111',
            // 'mobile_sha256' => hash("sha256", '15012431763'),
            'ip'                  => $this->__ip, //$_SERVER['REMOTE_ADDR']
            'mac'                 => '5489-98f6-16c0',
            'send_channel'        => '浏览器',
            'url'                 => $_SERVER['PHP_SELF'],
            'encrypted_state'     => false,
            'sensitive_data_list' => [],
            'device_type'         => 'Windows',
            'device_id'           => $this->__device_id,
            'referer'             => $_SERVER['HTTP_REFERER'] ?: 'index.php',
            'user_agent'          => $_SERVER['HTTP_USER_AGENT'],
        ];

        $params['mobile_sha256'] = hash("sha256", $params['mobile']);

        $pushData = [
            'params'     => json_encode($params),
            'event_time' => time(),
            'node_id'    => $this->__nodeId,
        ];

        $p = [
            'upload_params' => json_encode($pushData),
            'to_node_id'    => $this->__nodeId,
        ];

        $this->__caller->call(HCHSAFE_UPLOAD_LOGIN_LOG,$p,array(),$title,10);

        return $this->succ();
    }

        /**
     * orderdata
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function orderdata($sdf)
    {
        if (!$this->__is_config_mq() || !$this->__device_id || !$this->__unikey || !$sdf['tradeIds']) {
            return $this->succ();
        }
        
        // 过滤掉非抖音订单
        foreach ((array)$sdf['tradeIds'] as $key => $value) {
            if ('A' != substr($value,-1,1) && strlen($value) != 19) unset($sdf['tradeIds'][$key]);
        }
        
        if (!$sdf['tradeIds']) return $this->succ();

        $title = '抖音安全规范日志-订单访问';

        $uname = kernel::single('desktop_user')->get_login_name();

        
        foreach (array_chunk((array) $sdf['tradeIds'], 1000) as $value) {
            $params = [
                'account_id'          => $this->__prefixUname . (string) $uname,
                'account_type'        => '0',
                'shop_ids'            => [$this->__unikey],
                'order_ids'           => $value,
                'operation'           => $this->to_operation($sdf['operation'], count($sdf['tradeIds'])),
                'operate_time'        => time(),
                'url'                 => $_SERVER['PHP_SELF'],
                'ip'                  => $this->__ip, //$_SERVER['REMOTE_ADDR']
                'mac'                 => '5489-98f6-16c0',
                'identifyInfoList'    => [
                    ['name' => 'post_receiver', 'encrypted' => 'true'],
                    ['name' => 'post_tel', 'encrypted' => 'true'],
                    ['name' => 'detail', 'encrypted' => 'true'],
                ],
                'sensitive_data_list' => [],
                'device_type'         => 'Windows',
                'device_id'           => $this->__device_id,
                'referer'             => $_SERVER['HTTP_REFERER'] ?: 'index.php',
                'user_agent'          => $_SERVER['HTTP_USER_AGENT'],
            ];
            $pushData = [
                'params'     => json_encode($params),
                'event_time' => time(),
                'node_id'    => $this->__nodeId,
            ];

            $p = [
                'upload_params' => json_encode($pushData),
                'to_node_id'    => $this->__nodeId,
            ];

            $this->__caller->call(HCHSAFE_UPLOAD_ORDER_LOG,$p,array(),$title,10);
        }

        return $this->succ();
    }

    /**
     * 订单推送第三方
     *
     * @return void
     * @author
     **/
    public function orderpush($sdf)
    {
        if (!$this->__is_config_mq() || !$this->__device_id || !$this->__unikey || !$sdf['tradeIds']) {
            return $this->succ();
        }
        
        // 过滤掉非抖音订单
        foreach ((array)$sdf['tradeIds'] as $key => $value) {
            if ('A' != substr($value,-1,1) && strlen($value) != 19) unset($sdf['tradeIds'][$key]);
        }

        if (!$sdf['tradeIds']) return $this->succ();

        $title = '抖音安全规范日志-订单下发';

        $uname = kernel::single('desktop_user')->get_login_name();

        
        foreach (array_chunk((array) $sdf['tradeIds'], 100) as $value) {
            $params = [
                'account_id'   => $this->__prefixUname . (string) $uname,
                'account_type' => '0',
                'shop_ids'     => [$this->__unikey],
                'order_ids'    => $value,
                'operation'    => 'send',
                'operate_time' => time(),
                'sendTo'       => '',
                'node_id'      => $sdf['to_node_id'],
                'url'          => $_SERVER['PHP_SELF'],
                'ip'           => $this->__ip, //$_SERVER['REMOTE_ADDR'],
                'mac'          => '5489-98f6-16c0',
                'device_type'  => 'Windows',
                'device_id'    => $this->__device_id,
                'referer'      => $_SERVER['HTTP_REFERER'] ?: 'index.php',
                'user_agent'   => $_SERVER['HTTP_USER_AGENT'],
            ];
            $pushData = [
                'params'     => json_encode($params),
                'event_time' => time(),
                'node_id'    => $this->__nodeId,
            ];

            $p = [
                'upload_params' => json_encode($pushData),
                'to_node_id'    => $this->__nodeId,
            ];

            $this->__caller->call(HCHSAFE_UPLOAD_ORDERSEND_LOG,$p,array(),$title,10);
        }

        return $this->succ();
    }

    private function to_operation($operation = '', $order_num = '1')
    {

        if ($order_num > 1) {
            // 批量操作
            $jd_operation = 'view_order_list';

            if (stripos($operation, '导出') !== false) {
                $jd_operation = 'export_order_list';
            } elseif (stripos($operation, '查看') !== false) {
                $jd_operation = 'view_order_list';
            } elseif (stripos($operation, '打印') !== false) {
                $jd_operation = 'print_order_list';
            }
        } else {
            // 单个操作
            $jd_operation = 'view_order';

            if (stripos($operation, '导出') !== false) {
                $jd_operation = 'export_order';
            } elseif (stripos($operation, '查看') !== false) {
                $jd_operation = 'view_order';
            } elseif (stripos($operation, '打印') !== false) {
                $jd_operation = 'print_order';
            }
        }

        return $jd_operation;
    }
}
