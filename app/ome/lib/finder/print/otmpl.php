<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
* 打印模板finder类
*
* @author chenping<chenping@shopex.cn>
* @version 2012-4-18 13:39
*/
class ome_finder_print_otmpl 
{
    
    function __construct(&$app)
    {
    }

    var $column_control = '操作';
    /**
     * column_control
     * @param mixed $row row
     * @return mixed 返回值
     */

    public function column_control($row){
        return '<a href="index.php?app=ome&ctl=admin_print_otmpl&act=show&p[0]='.$row['id'].'&p[1]='.$row['type'].'&finder_id='.$_GET['_finder']['finder_id'].'" target="_blank">编辑</a>';
    }

}