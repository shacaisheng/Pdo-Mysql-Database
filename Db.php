<?php
// +--------------------------------------------------------------------------
// | Description :PDO数据库操作封装
// +--------------------------------------------------------------------------
// | Copyright (c) 2014-2015 http://www.sixsir.com All rights reserved.
// +--------------------------------------------------------------------------
// | Author: Persi <persi@sixsir.com> <http://www.sixsir.com>
// +--------------------------------------------------------------------------
// | Version:0.0.1                Date:2015/9/09
// +--------------------------------------------------------------------------

Class Db{

    // 当前实例
    protected static $_instance;
    // 数据库实例
    protected $_pdo;
    // 表前缀
    protected static $_prefix = '';
    // 配置信息
    protected $_config;

    protected $_tableName;

    protected $_where;

    protected $_join;

    protected $_fields;

    protected $_data;

    protected $_limit;

    protected $_order;

    protected $_group;
    // 查询语句
    protected $_query = '';
    // 绑定参数
    protected $_buildParams = array();
    // 最后插入的ID
    protected $_lastInsertId = false;
    // 最后的查询语句
    protected $_lastQuery;
    // 错误信息
    protected $_error;
    // Statement
    protected $_statement;
    // 结果集
    protected $_result;

    final private function __construct(){

        $this->_config = Yaf\Registry::get( 'config' )->dsn;

        try{
            $this->_pdo = new PDO( $this->_config->type . ':dbname=' . $this->_config->db_name . ';host=' . $this->_config->host .';charset=' . $this->_config->charset , $this->_config->user , $this->_config->password );
            $this->_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->_pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            $this->_pdo->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
            $this->setPrefix( $this->_config->prefix );

        }catch( PDOException $e ){
            throw new SixException( $e->getMessage() );
        }

    }

    public static function getInstance(){

        if( ! self::$_instance instanceof self ){
            self::$_instance = new self;
        }
        return self::$_instance;
    }

    /**
     * [setPrefix ]
     * @param string $prefix [string]
     */
    private function setPrefix( $prefix = '' ){

        self::$_prefix = $prefix;
        return $this;
    }

    public function table( $table = null ){

        $this->_tableName = $table;
        return $this;
    }

    // 获取表信息
    protected function getTableInfo(){


    }
    public function where( $field = null ,$value = null ,$operation = '=' ,$condition = 'AND' ){

        if( empty( count( $this->_where ) ) )$condition = '';
        $this->_where[] = Array( $condition, $field, $operation, $value );

        return $this;
    }

    public function join( $tableName = null ,$condition = null ,$type = 'LEFT' ){

        $joinTypes = array('LEFT', 'RIGHT', 'OUTER', 'INNER', 'LEFT OUTER', 'RIGHT OUTER');

        $type = strtoupper( trim( $type ) );

        if( !in_array( $type, $joinTypes ) )throw new SixException( '连接类型错误！' );

        if(!is_object ($tableName))
            $tableName = self::$_prefix . filter_var($tableName, FILTER_SANITIZE_STRING);

        if( strpos( $condition, '=' ) === false )throw new SixException( '连接条件错误！' );

        $this->_join[] = Array( $type, $tableName, $condition );

        return $this;
    }

    public function limit( $limit, $num = 0){

        if( empty( $num ) ){
            $this->_limit = $limit;
        }else{
            $this->_limit = $limit . ',' . $num;
        }

        return $this;
    }

    /**
     * [fields]
     * @param  array  $fields [description]
     * 如果存在Join 必须为 array('od'=>array('id'));
     * @return [type]         [description]
     */
    public function fields( array $fields = array('*') ){

        if( ! count( $this->_join ) ){
            $this->_fields = implode( ',', $fields );
            return $this;
        }

        $fieldArr = array();
        foreach ($fields as $key => $value) {

            $fieldArr[] = $this->_parseFields( $key, $value );
        }
        $this->_fields = implode(',',$fieldArr);
        return $this;
    }

    public function data( array $data ){

        $this->_data = $data;
        return $this;
    }
    private function _parseFields( $tableName ,array $fieldValue ){

        $field = '';
        foreach ($fieldValue as $key => $value) {
            $field .= $tableName . '.' . $value . ',';
        }
        return rtrim($field, ',');
    }

    public function order( $condition ){

        $this->_order = filter_var($condition, FILTER_SANITIZE_STRING);
        return $this;
    }

    public function group( $condition ){

        $this->_group = filter_var($condition, FILTER_SANITIZE_STRING);
        return $this;
    }

    protected function _buildSelect(){

        $this->_query = 'SELECT ';
    }

    protected function _buildInsert(){

        $this->_query = 'INSERT INTO ';
        $this->_buildTable();

    }

    protected function _buildUpdate(){

        $this->_query = 'UPDATE ';
        $this->_buildTable();
        $this->_query .= ' SET ';
    }

    protected function _buildDelete(){
        $this->_query = 'DELETE FROM ';
        $this->_buildTable();
    }

    protected function _buildTable(){

        if( empty( $this->_tableName ) )
            throw new SixException('表名不能为空！');
        $this->_query .= ' ' . self::$_prefix . $this->_tableName;
    }

    protected function _buildFields(){

        if( empty( $this->_fields ) )$this->_fields = '*';
        $this->_query .= $this->_fields . ' FROM ';

    }

    protected function _buildWhere(){

        if( empty( $this->_where ) )
            return;

        $this->_query .= ' WHERE';

        foreach ($this->_where as $val) {

            list( $condition, $field, $operation, $value ) = $val;

            if( $this->_checkJoin() ){
                if( ! strpos( $field, '.' ) )throw new SixException('请指定表名！');
            }
            $this->_query .= ' ' . $condition . ' ' . $field . ' ' . $operation;

            switch ( strtolower( $operation ) ) {
                case 'not':
                case 'not in':
                        if( is_array( $value ) ){
                            $value = implode( ',',array_values( $value ) );
                        }
                        $this->_query .= "(': ". $field ."')";
                    break;
                default:
                        $this->_query .= ':' . $field;
                    break;
            }

            $this->buildParams( $field, $value );
        }
    }

    protected function _buildJoin(){

        if( empty( $this->_join ) )return;

        foreach ($this->_join as $val) {
            list( $type, $tableName, $condition ) = $val;
            $this->_query .= ' ' .$type . ' JOIN ' .  $tableName . ' ' . $condition;
        }
    }

    protected function _buildLimit(){

        if( empty( $this->_limit ) )return;

        $this->_query .= ' LIMIT' . $this->_limit;
    }

    protected function _buildOrder(){

        if( empty( $this->_order ) )return;

        if( $this->_checkJoin() ){
            if( ! strpos( $this->_order, '.' ) ){
                throw new SixException( '请指定表名！' );
            }
        }

        $this->_query .= ' ORDER BY ' . $this->_order;
    }

    protected function _buildGroup(){

        if( empty( $this->_group ) )return;

        if( $this->_checkJoin() ){
            if( ! strpos( $this->_group, '.' ) ){
                throw new SixException( '请指定表名！' );
            }
        }

        $this->_query .= ' GROUP BY ' . $this->_group;
    }

    protected function _buildPrepare(){

        if( empty( $this->_pdo ) || empty( $this->_query ))
            throw new SixException('参数非法！');

        $this->_statement = $this->_pdo->prepare( $this->_query );
        return $this;
    }

    protected function _buildParams(){

        if( empty( $this->_statement ) )
            throw new SixException("STATEMENT 生成对象失败!", 1);
        foreach ($this->_buildParams as $key => $value) {
            $this->_statement->bindValue(':'.$key,$value['val'],$value['type']);
        }
    }

    protected function _buildExecute(){

        $this->_statement->execute();
    }

    protected function buildParams( $key, $value ){

        $this->_buildParams[$key] = array(
                'val'=>$value,
                'type'=> $this->checkValueType( $value )
            );
        return $this;
    }

    protected function checkValueType( $value ){

        switch (gettype($value)) {
            case 'NULL':
                return PDO::PARAM_NULL;
                break;
            case 'double':
            case 'string':
                return PDO::PARAM_STR;
                break;
            case 'boolean':
                return PDO::PARAM_BOOL;
                break;
            case 'int':
            case 'integer':
                return PDO::PARAM_INT;
                break;
            case 'blob':
                return PDO::PARAM_LOB;
                break;
            default:
                return '';
        }
    }

    protected function _buildSelectQuery(){

        $this->_buildSelect();
        $this->_buildFields();
        $this->_buildTable();
        $this->_buildJoin();
        $this->_buildWhere();
        $this->_buildLimit();
        $this->_buildOrder();
        $this->_buildGroup();
        $this->_buildPrepare();
        $this->_buildParams();
        $this->_buildExecute();

    }

    protected function _buildInsertQuery(){

        if( empty( $this->_data ) )throw new SixException( '数据不能为空' );

        $this->_buildInsert();
        $this->_query .= ' (' . implode( ',' , array_keys( $this->_data ) ) . ') values(';

        $dataStr = '';
        foreach ($this->_data as $key => $val) {
            $dataStr .= ':' . $key .',';
        }

        $this->_query .= rtrim( $dataStr , ',') . ')';

        foreach ($this->_data as $key => $value) {
            $this->buildParams( $key, $value );
        }
    }

    protected function _buildUpdateQuery(){

        if( empty( $this->_data ) )throw new SixException( '数据不能为空' );

        $this->_buildUpdate();

        $dataStr = '';
        foreach ($this->_data as $key => $value) {
            $dataStr .= '`' . $key . '`=:' .$key . ',';
        }
        $this->_query .= rtrim( $dataStr, ',' );

        foreach ($this->_data as $key => $value) {
            $this->buildParams( $key, $value );
        }
    }

    protected function _buildDeleteQuery(){

        $this->_buildDelete();
    }

    public function select(){

        $this->_buildSelectQuery();
        return $this->_statement->fetchAll( PDO::FETCH_ASSOC );

    }

    public function find(){

        $this->_buildSelectQuery();
        return $this->_statement->fetch( PDO::FETCH_ASSOC );

    }

    /**
     * [insert 插入数据，返回插入的行数]
     * @return [type] [description]
     */
    public function insert(){

        $this->_buildInsertQuery();
        $this->_buildPrepare();
        $this->_buildParams();
        $this->_statement->execute();
        $rowNum = $this->_statement->rowCount();
        $this->_lastInsertId = $this->_pdo->lastInsertId();
        if( empty( $rowNum ) )
            return false;
        return $rowNum;
    }

    public function update(){

        if( empty( $this->_where ) )
            throw new SixException('UPDATE 条件为空，禁止更新！');

        $this->_buildUpdateQuery();
        $this->_buildWhere();
        $this->_buildPrepare();
        $this->_buildParams();
        $rowNum  = $this->_statement->execute();
        if( empty( $rowNum ) )
            return false;
        return $rowNum;
    }

    public function delete(){

        if( empty( $this->_where ) )
            throw new SixException('DELETE 条件为空，禁止删除！');

        $this->_buildDeleteQuery();
        $this->_buildWhere();
        $this->_buildPrepare();
        $this->_buildParams();
        $rowNum  = $this->_statement->execute();
        if( empty( $rowNum ) )
            return false;
        return $rowNum;
    }

    public function getLastInsertId(){

        return $this->_lastInsertId;
    }

    public function getSelectNum(){

        return $this->_statement->rowCount();
    }
    private function _checkJoin(){

        if( count( $this->_join ) )
            return true;
        return false;
    }

    public function startTransaction(){

        $this->_pdo->beginTransaction();
    }

    public function commit(){

        $this->_pdo->commit();
    }

    public function rollback(){

        $this->_pdo->rollback();
    }
}
