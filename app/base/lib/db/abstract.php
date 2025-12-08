<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class base_db_abstract 
{
    public $prefix = 'sdb_';
    public static $mysql_query_executions = 0;

    function __construct(){
        $this->prefix = DB_PREFIX;
    }

    /**
     * query
     * @param mixed $sql sql
     * @param mixed $skipModifiedMark skipModifiedMark
     * @param mixed $db_lnk db_lnk
     * @return mixed 返回值
     */
    public function query($sql , $skipModifiedMark = false,$db_lnk=null){
        $rs = $this->exec($sql,$skipModifiedMark,$db_lnk);
        return $rs;
    }

    /**
     * selectPager
     * @param mixed $queryString queryString
     * @param mixed $pageStart pageStart
     * @param mixed $pageLimit pageLimit
     * @return mixed 返回值
     */
    public function selectPager($queryString,$pageStart=null,$pageLimit=null) {
        $_data['total'] = $this->count($queryString);
        $_data['page'] = ceil($_data['total']/$pageLimit);
        if($pageLimit==null) {
            $_data = $this->select($queryString);
        } else {
            $_data['data'] = $this->selectLimit($queryString, $pageLimit, $pageStart*$pageLimit);
        }
        return $_data;
    }
}//End Class