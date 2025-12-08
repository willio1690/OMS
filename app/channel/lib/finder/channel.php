<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class channel_finder_channel
{
    public $addon_cols = 'node_id,node_type,channel_type';

    public $column_opt = '操作';
    /**
     * column_opt
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function column_opt($row)
    {
        $finder_id = $_GET['_finder']['finder_id'];

        $buttons = [
            'edit'  => sprintf('<a href="index.php?app=channel&ctl=admin_channel&act=edit&p[0]=%s&finder_id=%s" target="dialog::{width:700,height:400,title:\'编辑\'}">编辑</a>', $row['channel_id'], $finder_id),
            'apply' => sprintf('<a href="index.php?app=channel&ctl=admin_channel&act=apply_bindrelation&p[0]=%s" target="dialog::{width:800,title:\'申请绑定\',onClose:function(){window.finderGroup[\'%s\'].refresh();}}">申请绑定</a>', $row['channel_id'], $finder_id),
        ];

        if ($row[$this->col_prefix . 'node_id']) {
            unset($buttons['apply']);
        }
        if ($row[$this->col_prefix . 'channel_type']=='cloudprint') {
            unset($buttons['apply']);
        }
        return implode(' | ', $buttons);
    }
}
