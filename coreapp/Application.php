<?php


namespace archive\coreapp;


class Application
{
    private $request;
    private $router;
    private $content;

    /**
     * Application constructor.
     * @param Router $router
     * @param $content
     */
    public function __construct(Router $router, $content)
    {
        $this->request = $content['service']['path_info'];
        $this->router = $router;
        $this->content = $content;
    }

    public function run()
    {
        return $this->router->dispatch($this->request, $this->content);
    }

    public function terminate($response)
    {
        header('Content-Type: application/json');
        echo $response;
    }
}
