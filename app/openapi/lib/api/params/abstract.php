<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

abstract class openapi_api_params_abstract{

    protected function checkParams($method,$params,&$sub_msg){
        $defined_params = $this->getAppParams($method);
        if(empty($defined_params)){
            return false;
        }
        foreach($defined_params as $defined_param => $attribute){
            if(isset($attribute['required']) && $attribute['required'] == 'true'){
                if(!isset($params[$defined_param])){
                    return false;
                }
            }else {
            	if (empty($params[$defined_param])){
            		continue;
            	}
            }

            
            switch($attribute['type']){
                case 'money':
                    if(!is_double($params[$defined_param])){
                        return false;
                    }
                    break;
                case 'date':
                    if(!preg_match("([0-9]{4}-[0-9]{2}-[0-9]{2})",$params[$defined_param])){
                        return false;
                    }
                    break;
                case 'number':
                    if(!is_numeric($params[$defined_param]) || $params[$defined_param]<= 0){
                        return false;
                    }
                    break;
                case 'string':
                    if(!is_string($params[$defined_param])){
                        return false;
                    }
                    break;
            }
        }

        return true;
    }

}