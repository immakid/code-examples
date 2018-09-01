<?php

error_reporting(E_ALL);
ini_set("display_errors", 'On');
ini_set("display_startup_errors ", 'On');
ini_set("log_errors", 'On');

use Symfony\Component\HttpFoundation\Request;
use Silex\Application;

require_once __DIR__.'/../vendor/autoload.php';


define('DATA_PATH', __DIR__. '/data/');

$db_prefix = 'test_';
$db_prefix = '';


if(getenv("CLEARDB_DATABASE_URL") === false)
{
    $db = array(
        'hostname' => 'mysql',
        'username' => 'root',
        'password' => '',
        'database' => $db_prefix. 'room',
    );

} else {

    $url=parse_url(getenv("CLEARDB_DATABASE_URL"));

    $db = array(
        'hostname' => $url["host"],
        'username' => $url["user"],
        'password' => $url["pass"],
        'database' => substr($url["path"],1),
    );
}


$app = new Silex\Application();
$app['debug'] = true;


require_once __DIR__.'/../lib/db.php';
require_once __DIR__.'/../lib/util.php';

mb_internal_encoding("UTF-8");
db::connectServer();

date_default_timezone_set('Europe/Kiev');

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../views'
));


function _autoload($className)
{
    $className = ltrim($className, '\\');
    $className = str_replace('\\', '/', $className);
    global $app;

    $paths =[
        __DIR__.'/../App/',
    ];

    foreach ($paths as $path)
    {
        $filePath = $path. $className. '.php';

        if(file_exists($filePath))
        {
            require $filePath;
            break;
        } else {
//            var_dump($filePath);
//            var_dump($filePath);echo "__74_var_dump_exit";exit;
        }
    }
}
spl_autoload_register('_autoload');


/**
 * Таймлайн графиков за которыми слежу
 */
$app->get('/', function () use ($app) {


    $rows = db::get('periods');
    $list = [];

    foreach ($rows as &$row) {

        $Period = PeriodFactory::fromRow($row);
        $View = new PeriodView($Period);

        $dates = $View->getDates();


        $list[] = array_merge($dates, [

            'id'            => $Period->getId(),
            'price'         => $Period->getPrice(),
            'active_days'   => implode(', ', $View->getActiveDays()),

            'start_date_full'    => $Period->getStartDate()->format('Y-m-d H:i:s'),
            'end_date_full'      => $Period->getEndDate()->format('Y-m-d H:i:s'),
        ]);
    }

    return $app['twig']->render('index.twig', [
        'list' => $list
    ]);
});


/**
 * Создание нового периода
 */
$app->post('/api_1/new', function (Request $req) use ($app) {

    $Period = PeriodFactory::fromPostRequest($req);

    $Mapper = new PeriodFlatMapper(
        new \FlatStrategy\InnerPeriodsFlatStrategy(),
        new \FlatStrategy\OuterPeriodsFlatStrategy(),
        new \FlatStrategy\StartDateFlatStrategy(),
        new \FlatStrategy\EndDateFlatStrategy()
    );

    $strategy = $Mapper->GetStrategyForPeriod($Period, 'new');
//    var_dump($strategy);echo "121__index.php";exit;

    $Runner = new \FlatStrategy\StrategyRunner();
    $runLog = $Runner->Run($strategy);


    file_put_contents('strategy_new.txt', json_encode($strategy, JSON_PRETTY_PRINT). PHP_EOL, FILE_APPEND);

    return api_ajax([
        'log' => $runLog,
        'strategy' => $strategy
    ]);
});

/**
 * Изменение периода
 */
$app->post('/api_1/update', function (Request $req) use ($app) {

    $Period = PeriodFactory::fromPostRequest($req);


    $Mapper = new PeriodFlatMapper(
        new \FlatStrategy\InnerPeriodsFlatStrategy(),
        new \FlatStrategy\OuterPeriodsFlatStrategy(),
        new \FlatStrategy\StartDateFlatStrategy(),
        new \FlatStrategy\EndDateFlatStrategy()
    );

    $strategy = $Mapper->GetStrategyForPeriod($Period, 'edit');

    $Runner = new \FlatStrategy\StrategyRunner();
    $runLog = $Runner->Run($strategy);

    file_put_contents('strategy_update.txt', json_encode($strategy, JSON_PRETTY_PRINT). PHP_EOL, FILE_APPEND);

    return api_ajax([
        'log' => $runLog
    ]);
});


/**
 * Получить период
 */
$app->get('/api_1/period', function (Request $req) use ($app) {

    $id = $req->get('id');

    db::where('id', $id);
    $row = db::getRow( 'periods');

    $Period = PeriodFactory::fromRow($row);
    

    return api_ajax([
        'period' => $Period
    ]);
});


/**
 * Удаление периода
 */

$app->get('/api_1/delete', function (Request $req) use ($app) {
    $id = $req->get('id');

    db::where('id', $id);
    db::delete('periods');

    return $app->redirect('/');
});


/**
 * Очистить все
 */
$app->get('/delete_all', function () use ($app) {

    db::delete('periods');

    return $app->redirect('/');
});


/**
 * Создать базу
 */
$app->get('/install', function () use ($app) {


    db::Execute('CREATE TABLE `periods` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `start_date` datetime DEFAULT NULL,
          `end_date` datetime DEFAULT NULL,
          `price` float DEFAULT NULL,
          `mon` tinyint(4) DEFAULT NULL,
          `tue` tinyint(4) DEFAULT NULL,
          `wed` tinyint(4) DEFAULT NULL,
          `thu` tinyint(4) DEFAULT NULL,
          `fri` tinyint(4) DEFAULT NULL,
          `sat` tinyint(4) DEFAULT NULL,
          `sun` tinyint(4) DEFAULT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8;
    ');

});



$app->get('/test', function () use ($app) {

    $startDate = '2018-08-01';
    $endDate = '2018-08-03 23:59:59';
    $price = 50;
    $days = [1,1,0,0,0,0,0];


    $Period = new Period($startDate, $endDate, $price);
    $Period->setDays($days);
    $Period->setId(5);


    $Mapper = new PeriodFlatMapper(
        new \FlatStrategy\InnerPeriodsFlatStrategy(),
        new \FlatStrategy\OuterPeriodsFlatStrategy(),
        new \FlatStrategy\StartDateFlatStrategy(),
        new \FlatStrategy\EndDateFlatStrategy()
    );


    $strategy = $Mapper->GetStrategyForNewPeriod($Period);

    var_dump($strategy);echo "213__index.php";exit;

    $Runner = new \FlatStrategy\StrategyRunner();
    $runLog = $Runner->Run($strategy);
    var_dump($runLog);echo "161__index.php";exit;

});

$app->run();