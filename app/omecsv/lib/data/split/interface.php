<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

interface omecsv_data_split_interface
{
    
    public function process($cursor_id, $params, &$errmsg);
    
    public function checkFile($file_name, $file_type,$queue_data);
    
    public function getTitle($filter=null,$ioType='csv' );
    
}