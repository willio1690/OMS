<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class base_service_cachevary 
{
    /**
     * 获取_varys
     * @return mixed 返回结果
     */
    public function get_varys() 
    {
        $varys['HOST'] = kernel::base_url(true);    //host信息
        $varys['REWRITE'] = (defined('WITH_REWRITE')) ? WITH_REWRITE : '';  //是否有rewirte支持
        $varys['LANG'] = kernel::get_lang(); //语言环境
        $varys['ECAE'] = ECAE_PUB_POINT;    //ecae布置环境
        return $varys;
    }//End Function

}//End Class