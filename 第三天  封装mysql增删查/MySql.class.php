<?php
error_reporting(E_ALL ^E_DEPRECATED);

class Mysql{
    public $error;

    public function __construct($host='localhost',$port=3306,$user='root',$pwd='',$dbName='test',$charSet='utf8'){
        //连库
        $conn=mysql_connect($host.':'.$port,$user,$pwd);
        if(!$conn){
            die(mysql_error());
        }
        //选库
        mysql_select_db($dbName)or die(mysql_error());
        //设置字符
        mysql_query('set names'.$charSet)or die(mysql_error());
    }
        public function insert($sql){
            $result=mysql_query($sql);
            return $result;
        }

        public function getAll($sql){
            $result=mysql_query($sql);
            if($result){
                $list=array();
                while($row=mysql_fetch_assoc($result)){
                    $list[]=$row;
                }
                return$list;
            }
            else{
                $this->error=mysql_error();
                return false;
            }
        }
        public function getRow($sql){
            $result=mysql_query($sql);
            if($result)
            {
                return mysql_fetch_assoc($result);
            }
            else
            {
                $this->error=mysql_error();
                return false;
            }
        }

        public function delete(){
            if(mysql_query($sql))
            {
                return mysql_affected_rows();
            }
            else{
                $this->error = mysql_error();
                return false;
            }
        }
    public function update($sql)
    {
        $bool=mysql_query($sql);
        if($bool){
            return mysql_affected_rows();
        }
        else{
            $this->error=mysql_error();
            return false;
        }
    }
}