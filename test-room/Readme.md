
# Setup

Для того чтобы запустить приложение:
```angular2html
    composer update
```


# Demo

Посмотреть как работает можно по ссылке
https://blooming-waters-36197.herokuapp.com/

Небольшое видео с описанием
https://www.useloom.com/share/56c42dfe7df146459abe5adcd0ee4f85



## Настройка бд

В файле index.php  

```angular2html
$db = array(
    'hostname' => 'mysql',
    'username' => 'root',
    'password' => '',
    'database' => $db_prefix. 'room',
);
```

Чтобы создать таблицы запустите адрес у приложения
```angular2html
/install
```

