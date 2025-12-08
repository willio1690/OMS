<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_privilege {

    /**
     * 检查Access
     * @param mixed $flag flag
     * @param mixed $obj obj
     * @param mixed $method method
     * @return mixed 返回验证结果
     */
    static public function checkAccess($flag,$obj,$method){
        if(!$flag){
            return false;
        }

        $settingObj = app::get('openapi')->model('setting');
        $settingInfo = $settingObj->dump(array('code'=>$flag,'status'=>1),'*');
        if($settingInfo){
            if(isset($settingInfo['config'][$obj]) && in_array($method,$settingInfo['config'][$obj])){
                // 将setting信息存储到全局变量中，供API方法使用
                $GLOBALS['openapi_current_setting'] = $settingInfo;
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

}