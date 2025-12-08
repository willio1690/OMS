<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class ome_service_branchview{
    
   /**
    * 显示选择仓库
    * @param int $branch_id 仓库ID
    * @param string $url    form提交的地址
    * @param string $title  标题显示
    * @param string $method form提交的方式 
    */
   public function getBranchView($branch_id, $url, $title='查看', $method='GET'){
       return kernel::single("ome_branch_view")->getBranchView($branch_id, $url, $title, $method);
   }
}