<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_shop_request_member extends erpapi_shop_request_abstract
{
    /**
     * 获取Ouid
     * @param mixed $uname uname
     * @return mixed 返回结果
     */
    public function getOuid($uname)
    {
        $title = '获取买家open_uid';
        $params = array(
            'api' => 'taobao.user.openuid.getbynick',
            'data' => json_encode([
                'buyer_nicks'=>$uname
            ])
        );
        $rsp   = $this->__caller->call(TAOBAO_COMMON_TOP_SEND, $params, array(), $title, 10, $uname);
        $return = [];
        if($rsp['data']) {
            $data = json_decode($rsp['data'], 1);
            if(is_array($data) 
                && is_array($data['user_openuid_getbynick_response']) 
                && $data['user_openuid_getbynick_response']['open_uids']
                && $data['user_openuid_getbynick_response']['open_uids']['open_uid_info']
            ) {
                foreach ($data['user_openuid_getbynick_response']['open_uids']['open_uid_info'] as $key => $value) {
                    $return[] = $value['buyer_open_uid'];
                }
            }
        }
        return $return;
    }
}
