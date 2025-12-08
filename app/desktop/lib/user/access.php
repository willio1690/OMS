<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class desktop_user_access
{
    /**
     * 仓储权限标记
     * @var int
     */
    const __BRANCH_ROLE = 2;

    /**
     * 订单分组权限标记
     * @var int
     */
    const __ORDER_ROLE = 3;

    /**
     * 门店权限标记
     * @var int
     */
    const __STORE_ROLE = 99;

    /**
     * 仓库权限
     *
     * @return void
     * @author
     **/
    public function role($role = null, $check_id = [], $user_id = null, $post = [])
    {
        $roles = app::get('desktop')->model('roles');
        $menus = app::get('desktop')->model('menus');

        if (!$check_id) {
            return '';
        }

        $aPermission = array();
        foreach ($roles->getList('*', ['role_id' => $check_id]) as $val) {
            $data = unserialize($val['workground']);
            if ($data) {
                $aPermission = array_merge($aPermission, $data);
            }
        }

        $aPermission = array_unique($aPermission);

        if (!$aPermission) {
            return '';
        }

        $branchList = [];

        $menuList = $menus->getList('*', array('menu_type' => 'permission', 'permission' => $aPermission));
        foreach ($menuList as $key => $value) {
            $addon = unserialize($value['addon']);

            if (!$addon) {
                continue;
            }

            if ($addon['show'] && $addon['save']) {
                // 如果存在控制

                $access    = explode(':', $addon['show']);
                $classname = $access[0];
                $method    = $access[1];
                $obj       = kernel::single($classname);

                // 检测是否包含订单确认
                if ('show_group' == $method && $role == self::__ORDER_ROLE) {
                    return $obj->$method($user_id,$post);
                }

                //检测是否包含仓库选择
                if ('show_branch' == $method && $role == self::__BRANCH_ROLE) {
                    return $branchList = $obj->$method($user_id, $post);
                }

                //检测是否包含仓库选择
                if ('show_o2o_branch' == $method && $role == self::__STORE_ROLE) {
                    return $obj->$method($user_id, $post);
                }
            }
        }
    }
}
