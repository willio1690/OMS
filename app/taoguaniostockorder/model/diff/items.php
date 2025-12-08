<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class taoguaniostockorder_mdl_diff_items extends dbeav_model
{
    public $diff_status = array(
        1 => '未处理',
        2 => '处理中',
        3 => '已处理',
        4 => '取消',
    );
    
    public $diff_reason = array(
        'less'   =>  '短发',
        'lost'   =>  '丢失',
        'wrong'  =>  '收货操作失误',
        'other'  =>  '其他原因',
        'more'   =>  '超发',
    );
    
    public $description = array(
        'less'   =>  '商品将从DF66调拨回发货方【%s】,请操作调拨入库单。调拨单号:%s',
        'lost'   =>  '商品将从DF66直接出库,出入库单号:%s',
        'wrong'  =>  '商品将从DF66调拨回收货方【%s】,请操作调拨入库单。调拨单号:%s',
        'other'  =>  '其他%s%s',
    );
    
    public $handle_type = array(
        'transfer'    =>  '调拔',
        'directOut'   =>  '直接出库',
    );
    
    public $responsible = array(
        1 => '请选择',
        2 => '发货方',
        3 => '收货方',
//        4 => '第三方物流',
    );
    
    /**
     * 差异描述
     * @var string[]
     */
    public $newDescribe = array (
        'less'   =>  '商品库存将从%s【%s】直接【增加】',
        'more'  =>  '商品库存将从%s【%s】直接【扣减】',
    );
    
    /**
     * 责任方
     * @var string[]
     */
    public $newResponsible = array (
        2 => '发货方',
        3 => '收货方',
        4 => '第三方物流',
    );
    
}
