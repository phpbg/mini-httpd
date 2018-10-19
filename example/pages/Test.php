<?php

class Test extends \PhpBg\MiniHttpd\Controller\AbstractController
{
    /**
     * Hello world page, that will automatically load Test.phtml, Test.js and Test.css files
     */
    public function __invoke(\Psr\Http\Message\ServerRequestInterface $request)
    {
        return ['name' => 'World'];
    }
}