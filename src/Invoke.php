<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2/1/2019
 * Time: 4:23 PM
 */

namespace Eslym\LightStream;


class Invoke
{
    private $name;
    private $arguments;

    public function __invoke($object)
    {
        call_user_func_array([$object, $this->name], $this->arguments);
    }

    public static function __callStatic($name, $arguments)
    {
        return new static($name, $arguments);
    }

    public function __construct($name, $arguments)
    {
        $this->name = $name;
        $this->arguments = $arguments;
    }
}