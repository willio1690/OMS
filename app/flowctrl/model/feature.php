<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 特性模型层
 *
 * @author kamisama.xia@gmail.com 
 * @version 0.1
 */

class flowctrl_mdl_feature extends dbeav_model{

    var $defaultOrder = array('ft_id',' DESC');

    /**
     * 销售物料类型字段格式化
     * @param string $row 物料类型字段
     * @return string
     */
    function modifier_type($row){
        $flowctrlConfLib = kernel::single('flowctrl_conf');
        $nodeList = $flowctrlConfLib->getNodeList();
        if($nodeList){
            foreach($nodeList as $node){
                if($node['code'] == $row){
                    return $node['name'];
                }
            }
        }

        return "-";
    }

}
