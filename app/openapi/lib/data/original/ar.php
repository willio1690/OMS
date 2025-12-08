<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_data_original_ar
{
    /**
     * 获取List
     * @param mixed $filter filter
     * @param mixed $start_time start_time
     * @param mixed $end_time end_time
     * @param mixed $trade_start_time trade_start_time
     * @param mixed $trade_end_time trade_end_time
     * @param mixed $offset offset
     * @param mixed $limit limit
     * @return mixed 返回结果
     */
    public function getList($filter, $start_time, $end_time, $trade_start_time, $trade_end_time, $offset = 0, $limit = 100)
    {
        $arMdl = app::get('finance')->model('ar');
        $count = $arMdl->count($filter);

        $arLists = $arMdl->getList('*', $filter, $offset, $limit,'ar_id asc');
        if (!$arLists){
            return [
                'lists' => [],
                'count' => $count,
            ];
        }


        $monthlyIds = $arLists ? array_column($arLists, 'monthly_id') : [0];
        $reportList = app::get("finance")->model("monthly_report")->getList('monthly_id,monthly_date', ['monthly_id' => $monthlyIds]);
        $reportList = $reportList ? array_column($reportList, 'monthly_date', 'monthly_id') : [];
        
        $arInfos = [];
        foreach ($arLists as $k => $ar) {
            $arInfos[$ar['ar_id']] = $ar;
            $arInfos[$ar['ar_id']]['monthly_name']      = $reportList[$ar['monthly_id']] ?: '';//账期名称

            $arInfos[$ar['ar_id']]['ar_items'] = array();
        }
        
        $ar_items = app::get('finance')->model('ar_items')->getList('*', [
            'ar_id' => array_column($arLists, 'ar_id'),
        ]);

        //items
        foreach ($ar_items as $k => $item) {
            $arInfos[$item['ar_id']]['ar_items'][] = $item;
        }
        
        return [
            'lists' => array_values($arInfos),
            'count' => $count,
        ];

    }
}