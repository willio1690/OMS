<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 企业组织结构的队列任务导入最终执行Lib类
 *
 * @access public
 * @author wangbiao<wangbiao@shopex.cn>
 * @version $Id: import.php 2016-07-26 15:00
 */
class organization_organizations_to_import
{
    /**
     * 企业组织结构导入的队列任务执行
     *
     * @param String $cursor_id
     * @param Array $params
     * @param String $errmsg
     * @return Boolean
     */
    function run(&$cursor_id, $params, &$errmsg)
    {
        $importObj    = app::get($params['app'])->model($params['mdl']);
        $dataSdf      = $params['sdfdata'];
        
        foreach ($dataSdf as $val)
        {
            $importData    = array(
                                'org_no' => $val[0],
                                'org_name' => $val[1],
                                'org_type' => $val[8],
                                'org_level_num' => $val[4],
                                'parent_id' => $val[5],
                                'parent_no' => $val[6],
                                'status' => $val[3],
                                'create_time' => $val[9],//新建时间
                                'recently_enabled_time' => $val[10],//最近启用时间
                                'recently_stopped_time' => $val[11],//首次启用时间
                                'first_enable_time' => $val[12],//最近停用时间
                                'org_parents_structure' => $val[7],
                            );
            
            $is_save    = $importObj->save($importData);
            if($is_save)
            {
                if(empty($importData['parent_id']))
                {
                    continue;#没有父级跳过
                }
                
                $parent_info    = $importObj->dump(array('org_id'=>$importData['parent_id'], 'org_type'=>1), 'org_id');
                if(empty($parent_info))
                {
                    continue;#跳过
                }
                
                #更新父级关联信息
                $child_list    = $importObj->getList('org_id, org_no, org_name', array('parent_id'=>$importData['parent_id'], 'org_type'=>1));
                if($child_list)
                {
                    $temp_data    = array();
                    foreach ($child_list as $key => $val)
                    {
                        $temp_data['child_nos'][]    = $val['org_no'];
                        $temp_data['child_names'][]  = $val['org_name'];
                    }
                    
                    $child_nos    = implode(',', $temp_data['child_nos']);
                    $child_names  = implode(',', $temp_data['child_names']);
                    
                    $update_data    = array(
                                        'haschild' => 1,
                                        'child_nos' => $child_nos,
                                        'child_names' => $child_names,
                                    );
                    $importObj->update($update_data, array('org_id'=>$importData['parent_id'], 'org_type'=>1));
                }
            }
            else 
            {
                $m    = $importObj->db->errorinfo();
                if(!empty($m))
                {
                    $errmsg    .=$m . ";";
                }
            }
        }
        
        return false;
    }
}
