<?php 
class BaseModel 
{ 
    protected $link; 
    //构造函数 
    public function __construct($db_config = array()) 
    { 
        if(empty($db_config)){ 
            $db_config = Yaf_Application::app()->getConfig()->db; 
        } 
        $this->link = $this->connect($db_config["host"], $db_config["username"], $db_config["password"], $db_config["database"]); 
    }

    //数据库连接 
    public function connect($dbhost, $dbuser, $dbpw, $dbname, $charset = 'utf8') 
    { 
        $this->link = @mysqli_connect($dbhost, $dbuser, $dbpw, $dbname); 
        if (!$this->link) { 
            return "database error:" . mysqli_connect_errno(); 
        } 
        if (!@mysqli_select_db($this->link, $dbname)) { 
            return 'database error'; 
        } 
        mysqli_query($this->link,"set names " . $charset); 
        return $this->link; 
    } 
    //查询 
    public function query($sql) 
    { 
        $query = mysqli_query($this->link, $sql); 
        return $query; 
    } 

    //获取全部记录 
    public function get_all($sql,$result_type = MYSQL_ASSOC) 
    { 
        $query = $this->query($sql); 
        $i = 0; 
        $rt = array(); 
        while($row =& mysqli_fetch_array($query,$result_type)) { 
            $rt[$i]=$row; $i++; 
        } 
        return $rt; 
    } 

    public function add($data = array()){ 
        return true; 
    } 
} 

