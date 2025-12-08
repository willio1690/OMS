<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


abstract class dbeav_select_adapter
{
    protected $_connect = null;
    
    abstract public function connect();

    abstract public function limit($sql, $count, $offset=0);

    abstract public function quote_column_as($column, $alias);

    abstract public function quote_identifier($name);

    abstract public function fetch($fetchMode=null);

    abstract public function fetch_all();

    abstract public function fetch_row();

    abstract public function fetch_one();

    abstract public function fetch_col();

    function __contruct() 
    {

    }//End Function

    /**
     * query
     * @param mixed $selectObj selectObj
     * @return mixed 返回值
     */
    public function query($selectObj){        
        $sql = $selectObj->assemble();
        $res = $this->_connect->query($sql);
        return $res;
    }

    /**
     * quote
     * @param mixed $name name
     * @return mixed 返回值
     */
    public function quote($name) 
    {
        if (is_int($value)) {
            return $value;
        } elseif (is_float($value)) {
            return sprintf('%F', $value);
        }
        return "'" . addcslashes($value, "\000\n\r\\'\"\032") . "'";
    }//End Function

}//End Class
