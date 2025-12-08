<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class ome_saas_format_xml extends ome_saas_format_data{
    
    /**
     * __construct
     * @param mixed $info info
     * @return mixed 返回值
     */
    public function __construct($info) {
        
        if(isset($info->success) && $info->success == 'true') {
            $data = get_object_vars($info->data);
            
            foreach ($data as $name => $value) {
                $v = explode('_', $name);
                $pn = '';
                foreach ($v as $un) {
                    $pn.= ucfirst($un);
                }
                
                $pn = 'set'. $pn;
                if(method_exists($this, $pn)) {
                    $this->$pn(trim($value));
                }
            }
        }
    }
    
}