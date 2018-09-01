<?php


class db
{
    static protected $aParams = array();
    static protected $where = '';

    static protected $left_join     = '';
    static protected $right_join    = '';
    static protected $join          = '';

    static protected $limit = '';
    static protected $order = '';


    static protected $dbPool = array();

    // УСтановка timezone по умолчанию GMT
    static protected $sTimeZone = 'Set time_zone="+00:00"';
    static protected $tzSetuped = array();
    
    /* Добавление пакетами в базу */
    static protected $aPackHeaders     = array();
    static protected $aPackData        = array();
    static protected $aPackIndexies    = array();
    static protected $aPackLength      = array();
    
    /*Показывать запросы*/
    static protected  $bShowSql = false;
    static protected  $bLogSql  = false;

    static protected  $showSqlType = 'echo';

    static protected $sActiveServer;
    static protected $sActiveDb;

    // Записывает запросы
    static protected $isRecordingRequests;
    static protected $recordingLog;

    // Последняя таблица для вставки
    static protected $sLastInsertedTable;

    static protected $selectedDb;

    static public function destruct()
    {
        self::flushPackets(); 
        self::closeConnect();
    }

    function __toString()
    {
        return 'dbObject';
    }
    
    /**  Отрыть соединение с базой */
    static protected function openConnect($sHost, $sUser, $sPass, $sDb)
    {
        // выбор базы данных (тестовая или реальная)
//        $sDb = $sDb;

        if(!eN(self::getMysqli())) {

            if (self::$sActiveDb != $sDb){
                self::selectDb($sDb);
            }

            self::tz_sendSql();
            return true;
        }

        self::$sActiveDb = $sDb;


        self::$dbPool[self::$sActiveServer] =
            new mysqli($sHost, $sUser, $sPass, $sDb);


        if (self::getMysqli()->connect_errno){
            var_dump(self::getMysqli()->connect_errno);echo "db__83var_dump_exit";exit;

            return null;
        } 

        if (!self::getMysqli()->set_charset("utf8")) {
            err(self::getMysqli()->connect_errno);
            return null;
        }
        
        self::tz_sendSql();
        
        return true;
    }


    static protected function tz_sendSql()
    {
        $oldTz = sp(self::$tzSetuped, self::$sActiveServer);

        // Установитvь timezone соединения с бд
        if ($oldTz != self::$sTimeZone) {
            self::$tzSetuped[self::$sActiveServer] = self::$sTimeZone;
            self::Execute(self::$sTimeZone);
        }
    }


    static protected function  getMysqli(){
        return sp(self::$dbPool, self::$sActiveServer);
    }

    static public function startRecording()
    {
        self::$isRecordingRequests = true;
        self::$recordingLog = array();

    }

    static public function stopRecording($isText = false)
    {
        self::$isRecordingRequests = false;

        return self::getRecord($isText);
    }

    static public function getRecord($isText)
    {
        $ret = null;

        if( $isText ){
            $ret = implode(' || ', self::$recordingLog);
            $ret = str_replace('"', '', $ret);

        } else {
            $ret = self::$recordingLog;

        }

        return $ret;
    }

    /** Выбрать базу данных */
    static public function selectDb($db)
    {
        if(!self::getMysqli()->select_db($db)){
            crit('Cant select db with name: '. $db);
        }

        self::$sActiveDb = $db;
    }
    
    /** Закрыть соединение бд */
    static public function closeConnect()
    {
        foreach(self::$dbPool as $mysqli){
            $mysqli->close();
        }

        self::$dbPool = array();
    }
    
    /** Установка соединения с серdвером бд */
    static public function connectServer($sActiveGroup = null)
    {
        global $db;
        self::$aParams          = $db;

        self::$sActiveServer = 'def';

        return self::openConnect(
            self::getParam('hostname'),
            self::getParam('username'),
            self::getParam('password'),
            self::getParam('database')
        );
    }
    
    static public function leaveServer() { self::destruct(); }
    
    /** Временная зона для соединнеия  */
    static public function setTimeZone($time_zone)
    {
        // установка временно зоны
        $timeZone = sprintf('Etc/GMT%+d', $time_zone* -1);

        $tz = new DateTimeZone($timeZone);

        // получаем текущий офсет времени в секундах от UTC/GMT+0 (здесь летнее время уже учтено)
        $tzOffset = $tz->getOffset(
            new DateTime('now', new DateTimeZone('UTC'))
        );

        // строим строку в формате, который понимает мускуль (летом для москвы +04:00, зимой — +03:00)
        $sSign = $tzOffset >= 0 ? '+': '-';
        $tzOffset = abs($tzOffset);

        $tzOffset = sprintf(
            '%s%02d:%02d',
            $sSign,
            floor($tzOffset / 3600), floor($tzOffset % 3600 / 60)
        );

        self::$sTimeZone = sprintf('SET time_zone = "%s";', $tzOffset);

        return self::$sTimeZone;
    }
    
    /** Получить приконнекченный сервер */
    static public function getActiveServer() {
        return self::$sActiveServer;
    }

    /** Получить приконнекченный сервер */
    static public function getActiveDb() {
        return self::$sActiveDb;
    }

    static public function transaction() { db::Execute('START TRANSACTION'); }
    static public function commit() { db::Execute('COMMIT'); }
    static public function rollback() { db::Execute('ROLLBACK'); }

    /**
     * database::where()
     * RAW where string empl:  | `cond`='str'  AND `cond` = 'str2'
    */
    static public function raw_where($sWhere = '') {
        self::$where .= self::realEscapeString($sWhere);
    }
    
    /** Включить вывод запросов */
    static public function show($bShow = true, $showType='fb') {
        self::$bShowSql         = $bShow;
        self::$showSqlType      = $showType;

        if (inFlag('dbhide'))
            self::$bShowSql = false;
    }

    static public function loggin($bLog = true) {

        self::$bLogSql = $bLog;
    }

    /**
    * Условие WHERE для запроса к бд 
    * exmpl: where('id',5),where('id <',5)
    */
    static public function where($sCol, $value, $sGlue = 'AND')
    {
        $aCol = explode(' ', $sCol);

        if (is_int($value))
            $sql = sprintf("`%s` %s %d",
                $aCol[0], (isset($aCol[1]) ? $aCol[1] : '='), $value);
        else
            $sql = sprintf("`%s` %s '%s'",
                $aCol[0],
                (isset($aCol[1]) ? $aCol[1] : '='),
                self::realEscapeString($value)
            );

        if (!isset(self::$where[0]))
            self::$where = $sql;

        else
            self::$where .= ' ' . $sGlue . ' ' . $sql;

    }

    static private function where_in_option(
        $intersecUnion, $fieldName, $inArray, $glue="AND", $isStrings = false)
    {
        if( $isStrings )
        {
            $sql = sprintf(' %s %s ("%s") ', $fieldName, $intersecUnion, implode('","', $inArray));
        }
        else
        {
            $sql = sprintf(' %s %s (%s) ', $fieldName, $intersecUnion, implode(',', $inArray));
        }


        $isEmptyWhere = !isset(self::$where[0]);

        if ($isEmptyWhere)
        {
            self::$where .= $sql;
        }
        else
        {
            self::$where .= sprintf(' %s %s', $glue, $sql);
        }

        return $sql;
    }

    static public function where_not_in($fieldName, $notInArray, $glue="AND", $isStrings = false)
    {
        return self::where_in_option('not in', $fieldName, $notInArray, $glue, $isStrings);
    }

    static public function where_in($fieldName, $inArray, $glue="AND", $isStrings = false)
    {
        return self::where_in_option('in', $fieldName, $inArray, $glue, $isStrings);

    }
    
    /** Сортировка для запроса */
    static public function order($sOrder = '')
    {
        if(empty(self::$order))
            self::$order .= $sOrder;
        else
            self::$order .= ', ' . $sOrder;
    }

    static protected function __join(&$join_variable, $table, $joinString)
    {
        $ret = null;

        $is_not_empty = isset($join_variable[0]);

        if( $is_not_empty )
        {
            $join_variable .= ' AND ';
        }

        $table_parts = explode(' ', $table);

        if( count($table_parts) > 1 )
        {
            $join_string = sprintf(' `%s` `%s` on %s',
                $table_parts[0], $table_parts[1],  $joinString);
        }
        else
        {
            $join_string = sprintf(' `%s` on %s', $table, $joinString);
        }

        $join_variable .= $join_string;

        return $ret;
    }

    static public function join_($table, $joinString)
    {
        $ret = self::__join(self::$join, $table, $joinString);
        return $ret;
    }

    static public function left_join($table, $joinString)
    {
        $ret = self::__join(self::$left_join, $table, $joinString);

        return $ret;
    }

    /** Получить все строоки для запроса */
    static public function get($table, $what = '*')
    {
        db::connectServer();

        if (ctype_alnum($what))
        {
            $what = '`' . $what . '`';
        }

        $table_parts = explode(' ', $table);

        if( count($table_parts) > 1 )
        {
            $sql = sprintf('SELECT %s FROM `%s` `%s`', $what, $table_parts[0], $table_parts[1]);
        }
        else
        {
            $sql = sprintf('SELECT %s FROM `%s`', $what, $table);
        }
        
        if (!empty(self::$left_join))
        {
            $sql .= ' LEFT JOIN '. self::$left_join;
        }

        if (!empty(self::$join))
        {
            $sql .= ' JOIN '. self::$join;
        }
        
        if (!empty(self::$where)) $sql .= ' WHERE ' . self::$where;
        if (!empty(self::$order)) $sql .= ' ORDER BY ' . self::$order;
        if (!empty(self::$limit)) $sql .= self::$limit;
        $aRes = self::Execute($sql);

        self::CleanVariables();
        return self::result($aRes);
    }
    
    /**
     * Получить одну строку в виде массива
     * @static
     * @param $sTable
     * @param string $sWhat
     * @return null|array
     */
    static public function getRow($sTable, $sWhat='*')
    {
        $ret = null;

        self::limit(0, 1);
        $aData = self::get($sTable, $sWhat);

        if (isset($aData[0]) )
        {
            $ret = $aData[0];
        }

        return $ret;
    }
    
    /** Вывести протокол запроса */
    static protected function printBacktrace($sql, $aParams=array())
    {
        $title = '';
        $ret = array();

        if (self::$bShowSql) {
            
            $title .= self::$sActiveServer. ' => '. $sql. "\r\n";
            if (count($aParams))
                $title .= ve($aParams, true). "\r\n";

            $aBacks = debug_backtrace();
            $sPref = str_repeat(' ', 5);//'&nbsp&nbsp&nbsp&nbsp';
            $iCnt = count($aBacks) - 1;
            
            for ($i = 1;$i <= $iCnt; ++$i) {
                $aBack = $aBacks[$i];
                if (isset($aBack['line']))
                    $str = $aBack['function']. ' '. $aBack['file']. ' : '. $aBack['line'] . "\n";

                $ret[] = array($sPref. $str);
                $sPref .= str_repeat(' ', 5);
            }

            if (self::$showSqlType == 'fb')
                fb::table($title, $ret);

            elseif (self::$showSqlType == 'echo'){

                echo '<hr>';
                echo '<b>', $title, '</b>';

//                if (ninFlag('dbnotrace')) {
//                    foreach ($ret as $k => $row) {
//                        $tab = str_repeat('&nbsp;', ($k+1) * 2);
//                        echo '<div>', $tab, $row[0], '</div>';
//                    }
//                }

                echo '<hr>';

            } else if (self::$showSqlType == 'echo_text') {

                echo "\r\n";
                echo  $title;

//                if (ninFlag('dbnotrace')) {
//                    foreach ($ret as $k => $row) {
//                        $tab = str_repeat(' ', ($k+1));
//                        echo $tab, $row[0];
//                    }
//                }

                echo "\r\n";
            }
        }

//        if( self::$bLogSql ){
//            dumpSql($sql);
//        }
    }
    
    /** Получить результат в виде массива */
    static public function result($oRes, $bRow = false)
    {
        $aData = array();
        if ($oRes === false)
            return array();
        if (!$bRow)
            while($aRec = $oRes->fetch_assoc())
                $aData[] = $aRec;

        else
            $aData = $oRes->fetch_assoc();

        $oRes->free();
        //$oRes->close();

        return $aData;
    }
    
    /** Получить одно значение из строки */
    static public function getOne($table, $what = '*')
    {
        self::limit(0, 1);
        $aValue = self::get($table, $what);
        return isset($aValue[0]) ? array_pop($aValue[0]) : null;
    }
    
    /** Выполняет запрос к бд */
    static public function select($sTable, $sWhat='*', $sWhere='', $aParams = array())
    {
        //self::get($sTable, )
        /*
        if(isset($sWhere[0])) $sql =  sprintf('SELECT %s FROM `%s` WHERE %s', $sWhat, $sTable, $sWhere);
        else $sql =  sprintf('SELECT %s FROM `%s`', $sWhat, $sTable);
        if (!empty(self::$order)) $sql .= ' ORDER BY ' . self::$order;
        if (!empty(self::$limit)) $sql .= self::$limit;

        if (eN(self::getMysqli()))
            self::$bShowSql = true;

        self::printBacktrace($sql, $aParams);
        $oQuery = self::getMysqli()->prepare($sql);
        if (!$oQuery)
            throw new Exception(
                'SQL Error: '. $sql .' '. self::getMysqli()->error);

        // подготавливаем вхоодые параметры
        if(isset($sWhere[0])) {
            $iParamsLen = count($aParams);
            $sParamStr = '';
            for($i=0; $i < $iParamsLen; $i++) {
                if (is_int($aParams[$i])) $sParamStr .= 'i';
                if (is_string($aParams[$i])) $sParamStr .= 's';
                if (is_double($aParams[$i])) $sParamStr .= 'd';
            }

            if ($iParamsLen == 1)
                $oQuery->bind_param($sParamStr, $aParams[0]);
            elseif ($iParamsLen == 2)
                $oQuery->bind_param($sParamStr, $aParams[0], $aParams[1]);
            elseif ($iParamsLen == 3)
                $oQuery->bind_param($sParamStr, $aParams[0], $aParams[1], $aParams[2]);
            elseif ($iParamsLen == 4)
                $oQuery->bind_param($sParamStr, $aParams[0], $aParams[1], $aParams[2], $aParams[3]);
        }

        $oQuery->execute();

        $aData = self::mysqli_result($oQuery->get_result());

        $oQuery->close();

        self::CleanVariables();
        return $aData;*/
    }
    
    /** Выбрать одну строку */
    static public function selectRow($sTable, $sWhat='*', $sWhere='', $aParams = array())
    {
        $aData = self::select($sTable, $sWhat, $sWhere, $aParams);
        return isset($aData[0]) ? $aData[0] : null;
    }
    
    /** Выбрать одно значение из строки */
    static public function selectOne($sTable, $sWhat='*', $sWhere='', $aParams = array())
    {
        $aData = self::select($sTable, $sWhat, $sWhere, $aParams);
        return isset($aData[0]) ? array_pop($aData[0]) : null;
    }
    
    static protected function mysqli_result($oResult)
    {
        $aData = array();
        while ($aRow = $oResult->fetch_array(MYSQLI_ASSOC)) $aData[] = $aRow;
        return $aData;
    }
     
    /** Выполняет запрос к бд */
    static public function Execute($sql)
    {
        if(eN(self::getMysqli())){
            echo '<h1>', self::getMysqli(), ' is null</h1>';
            self::$bShowSql = true;
            self::printBacktrace($sql);
            self::$bShowSql = false;

        } else {

            self::tz_sendSql();
            self::printBacktrace($sql);

            if( self::$isRecordingRequests ){
                self::$recordingLog[] = $sql;
            }

            $rRes = self::getMysqli()->query($sql);
            if (!$rRes) crit($sql."\n". self::getMysqli()->error);
            return $rRes;
        }
    }

    static public function aMultiExecute($sql)
    {
        $ret = array();

        self::printBacktrace($sql);

        if(eN(self::getMysqli())){
            echo '<h1>', self::getMysqli(), ' is null</h1>';
            self::$bShowSql = true;
            self::printBacktrace($sql);
            self::$bShowSql = false;

        } else {

            $sqlArr = explode(';', $sql);
            $index = 0;
            $rRes = self::getMysqli()->multi_query($sql);
            if (!$rRes)
                crit($sql."\n". self::getMysqli()->error);

            do{


                $chunk = self::result(
                    self::getMysqli()->store_result());

                $ret[] = $chunk;

                $isMoreResults = self::getMysqli()->more_results();


                if ($isMoreResults)
                    self::getMysqli()->next_result();

                if (self::getMysqli()->errno) {
                    echo "<br><br>\r\n";
                    echo $sqlArr[$index], "<br><br>\r\n";
                    echo self::getMysqli()->error;
                }

                $index++;
            }while($isMoreResults);
        }

        return $ret;
    }
    
    /** Получить рез в виде массива */
    static public function aExecute($sql) { return self::result(self::Execute($sql)); }
    
    /** Получить рез в виде массива */
    static public function rExecute($sql) { return self::result(self::Execute($sql), true); }
    
    /** Получить рез в виде значения */
    static public function sExecute($sql) {
        $ret = self::result(self::Execute($sql), true);
        return array_pop($ret);
    }
    
    /** Очищает переменные для следующего запроса */
    static protected function CleanVariables()
    {
        self::$where = '';
        self::$limit = '';
        self::$order = '';

        self::$join = '';
        self::$right_join = '';
        self::$left_join = '';

    }
    
    /** Вставить массив */
    static public function insertArr($t, $a, $duplicate=null)
    {
        db::createPacket($t, 10, $duplicate);

        foreach ($a as $v)
            db::insertPacket($t, $v);

        db::deletePacket($t);
    }

    /** Выполнить массив инструкций */
    static public function arrayExecute($aSql) { 
        foreach($aSql as $s) 
            self::Execute($s); 
    }
    
    
    /**
    * Вставка одной сторки данных в таблицу
    * $params = array('db_var' => 'value', 'db_var2' => 'value2',)
    */
    static public function insert($sTable, $aParams, $aDupliacate=null)
    {
        $sValues = sprintf("'%s'", implode("','", array_map(
            function($val){
                return db::realEscapeString($val);
            }, 
            array_values($aParams))
        ));
        
        $sFields = sprintf('`%s`', implode('`,`', array_keys($aParams)));
        $sql = sprintf('INSERT INTO `%s` (%s) VALUES (%s)',
            $sTable, $sFields, $sValues);
        
        // указаны данные при дублировании ключа
        if ($aDupliacate !== null) {
            $aDupValues = array();
            foreach ($aDupliacate as $sName => $sValue) {
                if ($sName[1] == '!') { // явно указан тип поля
                    $sFieldType =$sName[0];
                    $sName = substr($sName, 2);
                    
                    if($sFieldType == 'f') {/// field is mysql instruction
                        $aDupValues[] = sprintf(' `%s`=%s ', $sName, db::realEscapeString($sValue));
                    }
                } else{
                    if (is_int($sValue)){
                        $aDupValues[] = sprintf(' `%s`=%d ', $sName, $sValue);	
                    } else { // string
                        $aDupValues[] = sprintf(' `%s`="%s" ', $sName, db::realEscapeString($sValue));
                    }
                }
            }
                
            $sql .= sprintf(' ON DUPLICATE KEY UPDATE %s', implode(',', $aDupValues));
        }

        // последняя таблицыа в которую вставляли
        self::$sLastInsertedTable = $sTable;
        
        if (self::Execute($sql) === false) {
            crit(self::getActiveServer() . mysql_error());
        }
        
        return true;
    }
    
    /** Получить последний вставленные ид */
    static public function getLastInsertedId()
    {
        return self::getMysqli()->insert_id;
        //return db::getOne(self::$sLastInsertedTable, 'LAST_INSERT_ID()');
    }
    
    /** Удаление данных из таблицы */
    static public function delete($table)
    {
        $sql = 'DELETE FROM `' . $table;
        if (!empty(self::$where))
            $sql .= '` WHERE '. self::$where;

        if( !empty(self::$limit) )
            $sql .= self::$limit;

        self::Execute($sql);
        self::CleanVariables();
        return self::getMysqli()->affected_rows;
    }

    static public function GetAffectedRows()
    {
        $ret = self::getMysqli()->affected_rows;
        return $ret;
    }

    static public function PacketDelete($table, $wheares, $limit){
        $ret = null;

        do{
            foreach ($wheares as $field => $value) {
                db::where($field, $value);
            }

            db::limit($limit);
            $deletedCount = db::delete($table);

        }while($limit == $deletedCount);

        return $ret;
    }

    /** Обновлсение данных в таблице */
    static public function update($table, $params)
    {
        if (!sizeof($params))
            return false;

        $sql = 'update `' . $table .'` SET ';

        foreach ($params as $k => $v)
            $sql .= '`' . $k . "`='" . self::realEscapeString($v) . "',";

        $sql = rtrim($sql, ',');

        if (!empty(self::$where))
            $sql .= " WHERE " . self::$where;

        if( !empty(self::$limit) ){
            $sql .= self::$limit;
        }

        self::Execute($sql);
        self::CleanVariables();
        $ret = self::getMysqli()->affected_rows;

        return $ret;
    }

    /** Команда лимит для запросы */
    static public function limit($iStart, $iCount='')
    {
        if(!empty($iCount)) {
            if ($iCount == 0)
                crit("Лимит поставлен на нулевое кол-во записей");
            self::$limit = ' LIMIT ' . $iStart . ',' . $iCount;
        } else self::$limit = ' LIMIT ' . $iStart;
    }
    
    /** Получение переменных */
    static protected function getParam($sKey)
    {
         if (!isset(self::$aParams[$sKey]))
             crit('Нет такого ключа для параметров' . $sKey);
         return self::$aParams[$sKey];
    }
    
    /** Безопасная строка для бд */
    static public function realEscapeString($str) {
        return self::getMysqli()->real_escape_string($str);
    }
    
    /** Установка переменных */
    static public function setParam($sKey, $sVal) { self::$aParams[$sKey] = $sVal; }
    
    /** Установка активной бд  */
    static public function setActiveDb($sDbName, $sPrefix='')
    {
         self::setParam('prefix', $sPrefix);
         self::setParam('database', $sDbName);
    }

    /** Создать базу данных */
    static public function createDb($sDb)
    {
        self::connect();
        $sql = 'CREATE DATABASE '. $sDb;
        self::Execute($sql);
    }
    
    /** Очистить таблицу */
    static public function truncate($t) {
        self::Execute('truncate table '.$t);
    }
    
    /* Smart insert*/
    
    /** Создать запись для пакетной вставки */
    static public function createPacket($sTable, $iLength = 10, array $dupKeyArray=null)
    {
        if (isset(self::$aPackData[$sTable])) {
            err('Пакет с таким именем уже есть '. $sTable);
            return;
        }
       
        self::$aPackIndexies[$sTable] = 0;
        self::$aPackLength[$sTable] = $iLength;
        self::$aPackData[$sTable] = array();

        // duplicate key
        if ($dupKeyArray !== null) {
            $a = array();
            foreach ($dupKeyArray as $k => $v)
                $a[] = sprintf('%s = %s', $k, $v);

            self::$aPackData[$sTable]['duplicate'] = implode(',', $a);
        }

    }
    
    /** Удалить пакет из очереди */
    static public function deletePacket($sTable)
    {
        if (!isset(self::$aPackIndexies[$sTable])) {
            err('Нет такого пакета' .$sTable); return;
        }

        self::flushPacket($sTable);
        
        unset(self::$aPackIndexies[$sTable]);
        unset(self::$aPackLength[$sTable]);
        unset(self::$aPackData[$sTable]);
        unset(self::$aPackHeaders[$sTable]);
    }
    
    /** Добавить новый пакет в очередб на добавление */
    static public function insertPacket($sTable, $aData)
    {
        if (!isset(self::$aPackIndexies[$sTable])) {
            err('Нет такого пакета' .$sTable); return;
        }

        foreach ($aData as $key => $fieldValue) {
            $aData[$key] = self::realEscapeString($fieldValue);

        }

        self::$aPackData[$sTable]['sql'][] = $aData;

        ++self::$aPackIndexies[$sTable];
        
        if (self::$aPackIndexies[$sTable] >= self::$aPackLength[$sTable])
            self::flushPacket($sTable);
    }
    
    /** Записать данные в таблицу */
    static protected function flushPacket($sTable)
    {
        if (self::$aPackIndexies[$sTable] == 0) return;
        $sTail = '';
        $mysqli = self::getMysqli();

        foreach (self::$aPackData[$sTable]['sql'] as $aData) {
            $sSql = '(';
            foreach($aData as $sItem) {
                //$sSql .= "'". $mysqli->real_escape_string($sItem). "', ";
                $sSql .= "'". $sItem. "', ";
            }

            $sTail .= substr($sSql, 0, -2) . "),\n";
        }
        $sTail = rtrim($sTail, ",\n");
        
        // если нет заголовков для пакета построить
        if (!isset(self::$aPackHeaders[$sTable])) {
            // подготовка заголовков
            $sFields = '';
            $aFields = array_keys($aData);
            foreach ($aFields as $sField) $sFields .= "`".$sField."`, ";
            self::$aPackHeaders[$sTable] = 'INSERT INTO `' . $sTable . '` (' . substr($sFields, 0, -2) . ') VALUES'."\n";
        }
        
        $sSql =  self::$aPackHeaders[$sTable] . $sTail;

        // on duplicate key
        if (isset(self::$aPackData[$sTable]['duplicate'])) {
            $t = ' ON DUPLICATE KEY UPDATE '. self::$aPackData[$sTable]['duplicate'];
            $sSql .= $t;
        }

        self::Execute($sSql);
        
        self::$aPackData[$sTable]['sql'] = array();
        self::$aPackIndexies[$sTable] = 0;
    }
    
    /** Сбросить все пакеты в базу */
    static public function flushPackets()
    {
        foreach(self::$aPackIndexies as $sTable => $iLen)
            if ($iLen > 0) self::flushPacket($sTable);
    }
    
    /** Сохранить в файл show create table всех таблиц */
    static public function showCreateTables($sPath, $bAppend=false)
    {
        $sStr = '';
        $sql = 'SHOW TABLES';
        $aTabs = self::aExecute($sql);
        foreach ($aTabs as $aTab){
            $sql = "SHOW CREATE TABLE " .array_pop($aTab);
            $aDdl = self::aExecute($sql);
            $sStr .= $aDdl[0]['Create Table']. ";\n\n";
        }
        
        if ($bAppend) file_put_contents($sPath.'_createTables.sql', $sStr, FILE_APPEND);
        else file_put_contents($sPath.'_createTables.sql', $sStr);
    }

    static public function getTableList()
    {
        $tabs = self::aExecute('SHOW TABLES');
        $ret = array();

        foreach ($tabs as $tab){
            $ret[] = array_pop($tab);
        }

        return $ret;
    }

    /**
     * Пакетный апдейт таблицы
     * Если задается второй ключ - он должен быть уникальным для всей таблицы
     * В массивах ключей индексы должны идти подряд => (0,1,2)
     */
    static public function updatePacket(
        $sTab, $aKeysArr, $aValsArr, $sWhere='', $bAdd=false, $safeZero=false)
    {
        $sql = 'update `'.$sTab.'` set';

        $sKey = $aKeysArr[0][0]; $aKeys = $aKeysArr[0][1];

        // построить код для ключей
        foreach($aValsArr as $index => $aVals) {

            $sVal = $aVals[0]; $aVals = $aVals[1];
            // key name  // key value

            if(count($aKeys) != count($aVals))
                crit('Массивы не равны');

            $sql.= ' `'.$sVal.'`= case `'.$sKey.'`';
            $sAddStr = $bAdd ? (' `' . $sVal.'` + ') : '';

            // then parts
            foreach($aKeys as $key => $iKey) {
                $sql .= ' when '.$iKey.' then ';

                // safe zero adding
                if ($safeZero and $aVals[$key] < 0){
                    $sumValue = $sAddStr . $aVals[$key];

                    $sql.= sprintf(
                        'IF(`%s` > %d, %s, 0)',
                        $sVal,abs($aVals[$key]),
                        $sumValue
                    );

                } else
                    $sql.= $sAddStr . $aVals[$key];
            }

            $sql .= ' end,';
        }

        $sql = rtrim($sql, ',') . ' where ';

        // построить код для условий
        $iValsCount = count($aKeysArr[1]);
        $iValLen = count($aKeysArr[0][1]);

        for($iWidth = 0; $iWidth < $iValLen; $iWidth++) { // $iWidth - key in  array keys
            $sql .= '(';
            for($iHeight = 0; $iHeight < $iValsCount; $iHeight++) {
                $sql .= '`'.$aKeysArr[$iHeight][0].'` = ' . $aKeysArr[$iHeight][1][$iWidth];
                if ($iHeight+1 < $iValsCount) $sql .= ' and ';
                else { // конец блока условия - подставляем дополнительное условие
                    $sql .=  $sWhere;
                }
            }
            $sql .= ')';
            if ($iWidth + 1 < $iValLen) $sql .=  ' or ';
        }

        self::Execute($sql);
        return $sql;
    }

    /** Получить список таблиц в бд */
    static public function showTables()
    {
        $ret = array(); $l = self::aExecute('show tables');
        foreach($l as $a) 
            foreach($a as $t) 
                $ret[] = $t;
        return $ret;
    }


}