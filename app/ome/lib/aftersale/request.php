<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_aftersale_request{
    
    const _APP_NAME = 'ome';
    private $shop_id = '';

    /**
     * 设置店铺ID
     * @param String $shop_id
     * @return Object
     * */
    public function setShopId($shop_id){
        $this->shop_id = $shop_id;
        return $this;
    }

    public function __call($method,$args){
        $rs = array('rsp'=>'fail','msg'=>'','data'=>'');

        if (!$this->shop_id) {
            $rs['msg'] = 'set shop id first';
            return $rs;
        }

        $shopModel = app::get(self::_APP_NAME)->model('shop');
        $shop = $shopModel->dump($this->shop_id);
       
        if (!$shop) {
            $rs['msg'] = 'noShop';
            return $rs;
        } elseif ($shop && !$shop['node_id']) {
            $rs['msg'] = 'noNodeId';
            
            return $rs;
        }
        
        $platform = $this->getPlatform($shop['node_type'],$shop['tbbusiness_type']);
        if (!$platform) {
            $rs['msg'] = 'initial platform error';
            return $rs;
        }
        $platform->setShop($shop);
       
        if (method_exists($platform,$method)) {
            return call_user_func_array(array($platform,$method), $args);
        }else{
            return true;
        }
        
    }

    /**
     * 平台类名
     * node_type 截点类型
     */
    private function getClassName($node_type,$tbbusiness_type)
    {
        $dirname = $node_type;
        if (isset(ome_aftersale_mapping::$platname[$node_type])) {
            $dirname = ome_aftersale_mapping::$platname[$node_type];  
        }
        if ($node_type == 'taobao' && strtoupper($tbbusiness_type)=='B') {
            $dirname = 'tmall';
        }
        try {
            $plat_class_name = sprintf('ome_aftersale_request_%s',$dirname);
            if (class_exists($plat_class_name)){
                $class_name = $plat_class_name;
            }
        } catch (Exception $e) {
            $class_name = 'ome_aftersale_request_common';
        }
        
        
        return $class_name;
        
    }

     /**
     * 平台类初始化
     * @param String $node_type 店铺类型
     * @param String $node_version 店铺版本
     * @return Object
     **/
    private function getPlatform($node_type,$tbbusiness_type=''){
        $class_name = $this->getClassName($node_type,$tbbusiness_type);
        
        if ($class_name === false) {
            return false;
        }
        
        $platform = kernel::single($class_name);
        
        if (!$platform instanceof ome_aftersale_abstract) {
            return false;
        }

        return $platform;
    }
    
}

?>