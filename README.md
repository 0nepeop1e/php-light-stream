# php-light-stream
Very very lightweight stream

## Usage:
```php
<?php
include 'stream.php';

use \LightStream\Stream;

$data = [
  ['id'=>1, 'name'=>'Test1', 'val'=>rand(0, 9)],
  ['id'=>2, 'name'=>'Test2', 'val'=>rand(0, 9)],
  ['id'=>3, 'name'=>'Test1', 'val'=>rand(0, 9)],
  ['id'=>4, 'name'=>'Test2', 'val'=>rand(0, 9)],
  ['id'=>5, 'name'=>'Test1', 'val'=>rand(0, 9)],
  ['id'=>6, 'name'=>'Test2', 'val'=>rand(0, 9)],
  ['id'=>7, 'name'=>'Test1', 'val'=>rand(0, 9)],
  ['id'=>8, 'name'=>'Test2', 'val'=>rand(0, 9)]
];
$result = Stream::of($data)
  ->map(function($record){return (object)$record})
  ->filter(function($record){return $record->val < 5})
  ->pair(function($record){return $record->name})
  ->collectWithKeys();
header('Content-Type: text/plain');
var_dump($result);
```
