<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_api_function_v1_pda_stock extends openapi_api_function_v1_pda_abstract{
    /**
     * 获取该货品所有仓库下的库存(pda)
     *
     **/
    public function getAll($params,&$code,&$sub_msg){
        if(empty($params['pda_token']) || !$this->checkPdaToken($params['pda_token'])) {
            $sub_msg = '未登录或登录过期,请先登录';
            return false;
        }
        if(empty($params['device_code']) || !$this->check_device_code($params['device_code'])){
            $sub_msg = '设备未授权';
            return false;
        }
        $data = kernel::single('openapi_api_function_v1_stock')->getAll($params,$code,$sub_msg);
        return $data;
    }

    /**
     * 货位查询
     * @param  [type] $params   [description]
     * @param  [type] &$code    [description]
     * @param  [type] &$sub_msg [description]
     * @return [type]           [description]
     */
    public function getDetailList($params,&$code,&$sub_msg){
        if(empty($params['pda_token']) || !$this->checkPdaToken($params['pda_token'])) {
            $sub_msg = '未登录或登录过期,请先登录';
            return false;
        }

        if(empty($params['device_code']) || !$this->check_device_code($params['device_code'])){
            $sub_msg = '设备未授权';
            return false;
        }

        $page_no   = intval($params['page_no']) > 0 ? intval($params['page_no']) : 1;
        $page_size = (intval($params['page_size']) > 100 || intval($params['page_size']) <= 0) ? 100 : intval($params['page_size']);

        $offset = ($page_no-1) * $page_size;


        $data = kernel::single('openapi_data_original_stock')->getDetailList($params,$offset,$page_size);

        return $data;
    }
}