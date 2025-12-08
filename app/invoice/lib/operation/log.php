<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class invoice_operation_log{
	    
    function get_operations(){
        $operations = array(
           'invoice_create' => array('name'=> '发票创建','type' => 'order@invoice'),
           'invoice_cancel' => array('name'=> '发票作废','type' => 'order@invoice'),
           'invoice_billing' => array('name'=> '发票开票','type' => 'order@invoice'),
           'invoice_edit' => array('name'=> '发票编辑','type' => 'order@invoice'),
           'einvoice_prepare_tmall' => array('name'=> '电子发票的天猫状态更新','type' => 'order@invoice'),
           'einvoice_upload_tmall' => array('name'=> '电子发票上传天猫','type' => 'order@invoice'),
           'einvoice_upload'        => array('name' => '电子发票上传', 'type' => 'order@invoice'),
           'invoice_print' => array('name'=> '发票打印','type' => 'order@invoice'),
        );
        return array('invoice'=>$operations);
    }
}