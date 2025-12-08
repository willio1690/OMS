<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_branch_flow_damagedin extends ome_branch_flow_abstract implements ome_branch_flow_interface
{
    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function getContent($id)
    {
        $flow = app::get('ome')->model('branch_flow')->dump($id);
        $content = (array)json_decode($flow['content'], 1);
        $content['branch_id'] = $content['branch_id'] ? : [];
        $branchList = app::get('ome')->model('branch')->getList('branch_id,name',[
            'b_type' => '1',
            'type' => 'damaged',
            'check_permission' => 'false',
        ]);

        foreach ($branchList as $key => $value) {
            $branchList[$key]['checked'] = in_array($value['branch_id'], $content['branch_id']);
        }


        $render = app::get('ome')->render();

        $render->pagedata['branchList'] = $branchList;

        return $render->fetch('admin/branch/flow/damagedin.html');
    }

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function getBranchId()
    {
        $flow = app::get('ome')->model('branch_flow')->dump([
            'flow_type' => 'damagedin',
        ]);
        $content = (array)json_decode($flow['content'], 1);

        return $content['branch_id'];
    }

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function translateContent($content)
    {
        $content = (array)json_decode($content, 1);

        $branchList = app::get('ome')->model('branch')->getList('branch_id,name',[
            'b_type' => '1',
            'type' => 'damaged',
            'check_permission' => 'false',
            'branch_id' => $content['branch_id']
        ]);

        return implode('，', array_column($branchList, 'name'));
    }
}
