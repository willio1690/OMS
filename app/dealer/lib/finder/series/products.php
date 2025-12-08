<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class dealer_finder_series_products
{
    public $addon_cols = "";
    public $endorseList = [];

    public $column_status       = '状态';
    public $column_status_width = 60;
    public $column_status_order = 40;
    /**
     * column_status
     * @param mixed $row row
     * @param mixed $list list
     * @return mixed 返回值
     */
    public function column_status($row, $list)
    {
        switch ($row['status']) {
            case 'active':
                $status = '<span style="color:#109010">启用</span>';
                break;
            case 'close':
                $status = '<span style="color:#e60000">停用</span>';
                break;
            default:
                $status = $row['status'];
                break;
        }
        return $status;
    }

    public $column_endorse       = '关联经销商店铺';
    public $column_endorse_width = 110;
    public $column_endorse_order = 65;
    /**
     * column_endorse
     * @param mixed $row row
     * @param mixed $list list
     * @return mixed 返回值
     */
    public function column_endorse($row, $list)
    {
        if (!$this->endorseList) {
            $seMdl   = app::get('dealer')->model('series_endorse');
            $shopMdl = app::get('ome')->model('shop');

            $seriesIdArr       = array_column($list, 'series_id');
            $seriesEndorseList = $seMdl->getList('*', ['series_id|in' => $seriesIdArr]);
            $shopIdArr         = array_unique(array_column($seriesEndorseList, 'shop_id'));
            $shopNameList      = array_column($shopMdl->getList('shop_id,shop_bn,name'), null, 'shop_id');
            foreach ($seriesEndorseList as $k => $v) {
                $this->endorseList[$v['series_id']][] = $shopNameList[$v['shop_id']]['name'];
            }
        }

        $endorse = $detail = '';
        if (isset($this->endorseList[$row['series_id']]) && $this->endorseList[$row['series_id']]) {
            $count    = count($this->endorseList[$row['series_id']]);
            $endorse  = implode('、', $this->endorseList[$row['series_id']]);
            $detail   = implode('<br>', $this->endorseList[$row['series_id']]);
        }

        return '<span style="color:#0000ff"><div class="desc-tip" onmouseover="bindFinderColTip(event);">' . $endorse . '<textarea style="display:none;"><h4>关联经销商店铺('.$count.')</h4>' . $detail . '</textarea></div></span>';
    }
}
