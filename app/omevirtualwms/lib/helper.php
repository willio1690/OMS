<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/*

*/

class omevirtualwms_helper extends desktop_controller {

    function function_desktop_header($params, &$smarty)
    {
        return app::get('omevirtualwms')->render()->fetch('header.html');
    }

    function function_desktop_footer($params, &$smarty){}
}
