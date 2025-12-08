<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class financebase_finder_bill_import_sku {

    var $column_edit = "操作";
    var $column_edit_width = "110";
    var $column_edit_order = 1;

    function column_edit($row)
    {
        $finder_id = $_GET['_finder']['finder_id'];

        $seeBtn = '';
        if ($row['confirm_status'] == 0) {
            $seeBtn .= <<<EOF
            <a onclick="javascript:new Request({
                url:'index.php?app=financebase&ctl=admin_shop_settlement_cainiao&act=doChangeStatus&type=sku&id={$row['id']}',
                data:'',
                method:'post',
                onSuccess:function(response){
                    alert(response);
                    finder = finderGroup['{$finder_id}'];
                    finder.refresh.delay(100, finder);
                }
            }).send();" href="javascript:;" >确认</a>
EOF;
        
        
            $seeBtn .= <<<EOF
               &nbsp;&nbsp; <a onclick="javascript:if(confirm('数据取消将无法恢复，需要重新导入')) new Request({
                    url:'index.php?app=financebase&ctl=admin_shop_settlement_cainiao&act=doCancel&type=sku&id={$row['id']}',
                    data:'',
                    method:'post',
                    onSuccess:function(response){
                        alert(response);
                        finder = finderGroup['{$finder_id}'];
                        finder.refresh.delay(100, finder);
                    }
                }).send();" href="javascript:;" >取消</a>
EOF;
        }
        return $seeBtn;
    }


//    var $column_increment_service_sn = "增值服务单号";
//    var $column_increment_service_sn_width = "300";
//    var $column_cost_project_order = 4;
//    function column_increment_service_sn($row)
//    {
//        return $row['increment_service_sn'];
//    }


//    var $column_name = "确认人";
//    var $column_name_width = "100";
//    function column_name($row)
//    {
////        p($row);
//        return $row['name'];
//    }

}

