<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_shop_matrix_shopex_ecstore_response_aftersale extends erpapi_shop_response_aftersale {


    /**
     * 添加
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function add($params){
        $sdf = parent::add($params);
        
        $table_additional = $this->_aftersale_additional($params);
        if($sdf && $table_additional){
            $sdf['table_additional'] = $table_additional;
        }

       return $sdf;
    }

    /**
     * _aftersale_additional
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function _aftersale_additional($params){
  
        $shop_id   = $this->__channelObj->channel['shop_id'];

        if ($params['return_type']){
            $data = array(
                'shop_id'=>$shop_id,
                'return_bn'=>$params['return_bn'],
                'return_type'=>$params['return_type'],
                'model'=>'return_product_bbc',
            );
            return $data;
            
        }
        
        
    }
}