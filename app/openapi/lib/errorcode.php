<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_errorcode {

    /**
     * 获取
     * @param mixed $code code
     * @return mixed 返回结果
     */
    static public function get($code){
        $errorInfos = array(
            //系统级错误码
			'e000001' => array('code' => 'e000001','msg' => 'system params lost or error'),
            'e000002' => array('code' => 'e000002','msg' => 'sign error'),
            'e000003' => array('code' => 'e000003','msg' => 'class or method not exist'),
            'e000004' => array('code' => 'e000004','msg' => 'no permissions to access'),
            'e000005' => array('code' => 'e000005','msg' => 'init interface fail'),
            'e000006' => array('code' => 'e000006','msg' => 'application params error'),
            'e000007' => array('code' => 'e000007','msg' => 'init template fail'),
            'e000008' => array('code' => 'e000008','msg' => 'refer server time invalid'),
            'e000009' => array('code' => 'e000009','msg' => 'repeat request invalid'),
        );

        return $errorInfos[$code] ? $errorInfos[$code] : array('code'=>'','msg'=>'');
    }
}