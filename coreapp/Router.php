<?php


namespace archive\coreapp;
use archive\config\Routes;

class Router
{
    use Routes;

    const NAME_CONTROLLER = 0;
    const NAME_ACTION = 1;
    private $route;

    public function dispatch($url, array $contentBodyRequest)
    {
        $result = '';

        if (array_key_exists($url, $this->route)) {
            $nameController = $this->route[$url][self::NAME_CONTROLLER];
            $nameAction = $this->route[$url][self::NAME_ACTION];

            $result = call_user_func(array((new $nameController($contentBodyRequest)), $nameAction));
        }

        return $result;
    }

    public function initRoutes()
    {
        foreach ($this->getRoutes() as $uriWOQueryString => $pairControllerAndAction) {
            $this->route[$uriWOQueryString] = $pairControllerAndAction;
        }
    }
}
