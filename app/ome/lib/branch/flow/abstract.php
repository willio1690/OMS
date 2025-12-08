<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

abstract class ome_branch_flow_abstract
{
    /**
     * 获取Content
     * @param mixed $id ID
     * @return mixed 返回结果
     */
    public function getContent($id)
    {
        $flow = app::get('ome')->model('branch_flow')->dump($id);

        $content = (array) json_decode($flow['content'], 1);

        $branchList = app::get('ome')->model('branch')->getList('branch_id,name', [
            'b_type'           => '1',
            'type'             => ['main','aftersale'],
            'check_permission' => 'false',
        ]);

        foreach ($branchList as $key => $value) {
            $branchList[$key]['checked'] = in_array($value['branch_id'], $content['branch_id']);
        }

        $render = app::get('ome')->render();

        $render->pagedata['branchList'] = $branchList;

        return $render->fetch('admin/branch/flow/common.html');
    }

    /**
     * 获取BranchList
     * @return mixed 返回结果
     */
    public function getBranchList()
    {
        $flow_type = array_pop(explode('_', get_class($this)));

        $flow = app::get('ome')->model('branch_flow')->dump([
            'flow_type' => $flow_type,
        ]);
        $content = (array) json_decode($flow['content'], 1);

        if (!$content['branch_id']) {
            return [];
        }

        $branchList = app::get('ome')->model('branch')->getList('branch_id,name', [
            'check_permission' => 'false',
            'branch_id'        => $content['branch_id'],
        ]);

        return $branchList;
    }

    /**
     * translateContent
     * @param mixed $content content
     * @return mixed 返回值
     */
    public function translateContent($content)
    {
        $content = (array) json_decode($content, 1);

        $branchList = app::get('ome')->model('branch')->getList('branch_id,name', [
            'b_type'           => '1',
            'type'             => ['main','aftersale'],
            'check_permission' => 'false',
            'branch_id'        => $content['branch_id'],
        ]);

        return implode('，', array_column($branchList, 'name'));
    }
}
