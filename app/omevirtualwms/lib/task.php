<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/*

*/

class omevirtualwms_task{

    function week(){

    }

    function minute(){

    }

    function hour(){
        app::get('omevirtualwms')->model('dataStatus')->scanANDclean();
    }

    function day(){
        
    }

    function month(){

    }

}
