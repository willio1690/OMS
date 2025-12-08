<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class wap_misc_task{

    function week(){

    }

    function minute(){

    }

    function hour(){

    }

    function day(){
        //清除历史已使用掉的提货码
        kernel::single('wap_code')->clean_code();
    }

    function month(){

    }

}