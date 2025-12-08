<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_branch_flow_router
{
    private $_type = '';

    /**
     * undocumented function
     *
     * @return void
     * @author
     **/
    public function __construct($type)
    {
        $this->_type = $type;
    }

    public function __call($method, $args)
    {
        // $type = array_shift($args);

        try {
            $object_name = 'ome_branch_flow_' . $this->_type;

            if (class_exists($object_name)) {
                $object_class = kernel::single($object_name);

                if (!method_exists($object_class, $method)) {
                    throw new Exception("method error");
                }

                return call_user_func_array(array($object_class, $method), $args);
            }
        } catch (Exception $e) {
            return false;
        }
    }
}
