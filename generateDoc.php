<?php

class GenerateDoc{
    protected static $_dbh = null; //静态属性,所有数据库实例共用,避免重复连接数据库
    protected $_dbType = 'mysql';
    protected $_pconnect = true; //是否使用长连接
    protected $_host = '';
    protected $_port = 3306;
    protected $_user = '';
    protected $_pass = '';
    protected $_dbName = ''; //数据库名
    private $fields = '*';
    private $tables = [];
    private $fileName = '';
    private $fileType = '';

    public function __construct(array $conf)
    {
        class_exists('PDO') or die("PDO: class not exists.");
        $this->_host = $conf['host'];
        $this->_port = $conf['port'];
        $this->_user = $conf['user'];
        $this->_pass = $conf['passwd'];
        $this->_dbName = $conf['dbname'];
        $this->fields = isset($conf['fields']) ? $conf['fields'] : '*';
        $this->tables = isset($conf['tables']) ? $conf['tables'] : [];
        $this->fileName = isset($conf['fileName']) ? $conf['fileName'] : '';
        $this->fileType = isset($conf['fileType']) ? $conf['fileType'] : '';
        //连接数据库
        if ( is_null(self::$_dbh) ) {
            $this->_connect();
            $this->_getTables();
        }
    }

    /**
     * 连接数据库的方法
     */
    protected function _connect() {
        $dsn = $this->_dbType.':host='.$this->_host.';port='.$this->_port.';dbname='.$this->_dbName;
        $options = $this->_pconnect ? array(PDO::ATTR_PERSISTENT=>true) : array();
        try {
            $dbh = new PDO($dsn, $this->_user, $this->_pass, $options);
            $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);  //设置如果sql语句执行错误则抛出异常，事务会自动回滚
            $dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); //禁用prepared statements的仿真效果(防SQL注入)
        } catch (PDOException $e) {
            die('Connection failed: ' . $e->getMessage());
        }
        $dbh->exec('SET NAMES utf8');
        self::$_dbh = $dbh;
    }

    /**
     * 执行查询 主要针对 SELECT, SHOW 等指令
     * @param string $sql sql指令
     * @return mixed
     */
    protected function _doQuery($sql='') {
        $this->_sql = $sql;
        try{
            $pdostmt = self::$_dbh->prepare($this->_sql); //prepare或者query 返回一个PDOStatement
            $pdostmt->execute();
            $result = $pdostmt->fetchAll(PDO::FETCH_ASSOC);
            return $result;
        }catch(PDOException $e){
            echo "\n".$sql."\n";
            return;
        }
    }

    protected function _getTables(){
        if(empty($this->tables)){
            $res = $this->_doQuery("select table_name from information_schema.tables where table_schema='$this->_dbName' and table_type='base table'");
            if(empty($res)) die('The database is empty');
            foreach ($res as $val){
                $this->tables []= $val['table_name'];
            }
        }
    }

    protected function _getTableDesign($table){
        return $this->_doQuery("select $this->fields from information_schema.columns where table_schema = '$this->_dbName' and table_name = '$table'");
    }

    public function main(){
        foreach ($this->tables as $k=>$table){
            $desc = $this->_getTableDesign($table);
            if($this->fileType == 'md'){
                $tableContent = $this->_arrayToMarkDown($table, $desc, $k);
                $this->_putContentFile($tableContent, $this->fileType);
            }
        }
    }

    protected function _arrayToMarkDown($table, $arr, $order)
    {
        if(empty($arr)) return;
        $txt = '';
        $title = '';
        $bar = '';
        $tableTitle = '## '.($order+1).'.'.$table.PHP_EOL;
        foreach ($arr as $key=>$val){
            if($bar == ''){
                $bar = str_repeat('|:-', count($val)).'|'.PHP_EOL;
            }
            if($title == ''){
                $title = array_keys($val);
                $title = '|'.implode('|', $title).'|'.PHP_EOL;
            }
            foreach ($val as $k=>$v){
                $txt .= '|'.$v;
            }
            $txt .= '|'.PHP_EOL;
        }

        return $tableTitle.PHP_EOL.PHP_EOL.$title.$bar.$txt.PHP_EOL.PHP_EOL;
    }

    protected function _putContentFile($content, $suffix){
        $myfile = fopen($this->fileName.'.'.$suffix, 'a+') or die("Unable to open file!");
        fwrite($myfile, $content);
        fclose($myfile);
    }

    protected function _arrayToExcel(){
        //TODO 
    }
}
