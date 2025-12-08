<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class base_service_render 
{
    /**
     * pre_display
     * @param mixed $content content
     * @return mixed 返回值
     */
    public function pre_display(&$content) 
    {
        $content = base_storager::image_storage($content);
    }//End Function

}//End Class