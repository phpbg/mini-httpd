<?php

class Demo extends \PhpBg\MiniHttpd\Controller\AbstractController
{
    use \PhpBg\MiniHttpd\Middleware\ContextTrait;
    /**
     * Hello world page, that will automatically load Test.phtml, Test.js and Test.css files
     */
    public function __invoke(\Psr\Http\Message\ServerRequestInterface $request)
    {
        $context = $this->getContext($request);
        $context->renderOptions['bottomScripts'] = [
            "/vue-2.5.17.js",
            "/jquery-3.3.1.js"
        ];
        $context->renderOptions['headCss'] = ['/w3-4.11.css'];
        return ['title' => 'Mini HTTPD demo'];
    }
}