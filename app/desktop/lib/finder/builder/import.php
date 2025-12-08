<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class desktop_finder_builder_import extends desktop_finder_builder_prototype
{

    /**
     * main
     * @return mixed 返回值
     */
    public function main()
    {
        $render = app::get('desktop')->render();

        if (!$render->pagedata['thisUrl']) {
            $render->pagedata['thisUrl'] = $this->url;
        }

        $render->pagedata['use_import_template'] = $this->use_import_template;

        echo $render->fetch('common/import.html');
    }
}
