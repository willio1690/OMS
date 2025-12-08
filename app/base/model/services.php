<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class base_mdl_services extends base_db_model{

    function __construct(&$app){
        $this->app = $app;
        $this->columns = array (
            'content_id'=>array(
                'label'=>'',
                'width'=>200,
                'type' => 'number',
                'pkey' => true,
                'extra' => 'auto_increment',
            ),
            'content_type' =>
            array (
                'label'=>app::get('base')->_('支式'),
                'width'=>200,
                'type' => 'varchar(80)',
                'required' => true,
                'hidden' => true,
                'width' => 100,
                'in_list' => true,
                'default_in_list' => true,
            ),
            'app_id' =>
            array (
                'label'=>'app',
                'width'=>200,
                'type' => 'table:apps',
                'required' => true,
                'width' => 100,
                'in_list' => true,
                'default_in_list' => true,
            ),
            'content_name'=>array(
                'label'=>'',
                'width'=>200,
                'type'=>'varchar(80)',
            ),
            'content_title'=>array(
                'label'=>'',
                'width'=>200,
                'type'=>'varchar(100)',
                'is_title'=>true,
            ),
            'content_path'=>array(
                'label'=>app::get('base')->_('支式'),
                'width'=>200,
                'type'=>'varchar(255)',
            ),
            'disabled'=>array(
                'label'=>app::get('base')->_('支式'),
                'width'=>200,
                'type'=>'bool',
                 'hidden' => true,
                'default'=>'true'
            )
        );

        $this->schema = array(
                'default_in_list'=>array_keys($this->columns),
                'in_list'=>array_keys($this->columns),
                'idColumn'=>'app_id',
                'columns'=>&$this->columns
            );
    }

    function get_schema(){
        return $this->schema;
    }

    function count($filter=''){
        //useless code just echo 1; edit by danny
        //return count(kernel::servicelist('ectools_payment.ectools_mdl_payment_cfgs'));
        return 1;
    }

    /**
     * 获取List
     * @param mixed $cols cols
     * @param mixed $filter filter
     * @param mixed $offset offset
     * @param mixed $limit limit
     * @param mixed $orderby orderby
     * @return mixed 返回结果
     */
    public function getList($cols='*', $filter='null', $offset=0, $limit=-1, $orderby=null)
    {
        $data = app::get('base')->model('app_content')->getlist('*',$filter);
        return $data;
    }
}
