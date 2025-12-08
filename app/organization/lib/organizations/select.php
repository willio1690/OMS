<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class organization_organizations_select
{

    //获取组织层级select/options
    /**
     * 获取_organization_select
     * @param mixed $path path
     * @param mixed $params 参数
     * @param mixed $selected_id ID
     * @return mixed 返回结果
     */
    public function get_organization_select($path, $params, $selected_id = null)
    {
        //启用 和 未删除
        $basefilter      = array("status" => 1, "del_mark" => 0);
        $params['depth'] = $params['depth'] ? intval($params['depth']) : 1;
        $only_show_tree  = false;

        $html = '<select onchange="selectOrganization(this,this.value,' . ($params['depth'] + 1) . ',0)">';
        //判断到底显示整棵树，还是只显示普通的层次
        if (isset($params['show']) && $params['show'] == 'onlytree') {
            $only_show_tree         = true;
            $basefilter['org_type'] = 1;
            $html                   = '<select onchange="selectOrganization(this,this.value,' . ($params['depth'] + 1) . ',\'' . $params['show'] . '\')">';
        }
        //判断是否有effect参数
        if ($params["effect"]) {
            $html = '<select onchange="selectOrganization(this,this.value,' . ($params['depth'] + 1) . ',0,\'' . $params["effect"] . '\')">';
            if ($only_show_tree) {
                $html = '<select onchange="selectOrganization(this,this.value,' . ($params['depth'] + 1) . ',\'' . $params['show'] . '\',\'' . $params["effect"] . '\')">';
            }
        }

        $html .= '<option value="_NULL_">请选择...</option>';
        $filter = ($path) ? array('org_level_num' => $params['depth'], 'parent_id' => $path) : array('org_level_num' => $params['depth']);

        $filter          = array_merge($filter, $basefilter);
        $mdlOrganization = app::get('organization')->model('organization');
        $rows            = $mdlOrganization->getList('org_id,org_name,org_level_num,haschild', $filter, 0, -1, 'org_id ASC');
        if (!empty($rows)) {
            foreach ($rows as $item) {
                //目前组织结构最大五层层级
                if ($item['org_level_num'] <= 5) {
                    $selected = $selected_id == $item['org_id'] ? 'selected="selected"' : '';
                    // 查找当前地区是否有子集
                    if ($only_show_tree) {
                        if (($item['haschild'] & 1) == 1) {
                            $html .= '<option has_c="true" value="' . $item['org_id'] . '" ' . $selected . '>' . $item['org_name'] . '</option>';
                        } else {
                            $html .= '<option value="' . $item['org_id'] . '" ' . $selected . '>' . $item['org_name'] . '</option>';
                        }
                    } else {
                        if (intval($item['haschild']) > 0) {
                            $html .= '<option has_c="true" value="' . $item['org_id'] . '" ' . $selected . '>' . $item['org_name'] . '</option>';
                        } else {
                            $html .= '<option value="' . $item['org_id'] . '" ' . $selected . '>' . $item['org_name'] . '</option>';
                        }
                    }
                } else {
                    $no = true;
                }
            }
        }
        $html .= '</select>';
        if ($no) {
            $html = "";
        }
        return $html;
    }

}
