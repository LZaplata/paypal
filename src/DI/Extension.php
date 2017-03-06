<?php

namespace LZaplata\PayPal\DI;


use Nette\DI\CompilerExtension;

class Extension extends CompilerExtension
{
    public $defaults = [

    ];

    public function loadConfiguration()
    {
        $config = $this->getConfig($this->defaults);
    }
}