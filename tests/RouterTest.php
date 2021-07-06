<?php

use PHPUnit\Framework\TestCase;
use microservice_template\coreapp\Router;

class RouterTest extends TestCase
{
    public Router $router;

    public function setUp(): void
    {
        $this->router = new Router();
        $this->router->initRoutes();
    }

    public function testInitRoutes()
    {
        $this->assertNotEmpty($this->router->getInitRoutes(), 'Список маршрутов пуст');
    }

    public function testDispatch()
    {
        $result = $this->router->dispatch('/test', []);
        $this->assertIsString($result, 'Возвращаемое значение - не строка');
//        $this->assertIsJso
    }

    public function testDispatch1()
    {
        $result = $this->router->dispatch('/calculation_rule', []);
        var_dump($result); die(__FILE__ . __LINE__ . PHP_EOL);
    }

    public function test__construct()
    {
        $this->assertInstanceOf(Router::class, $this->router, 'Expected object instance of class Router!');
    }

    public function testInitRoutesNotEmpty()
    {
        $this->assertNotEmpty($this->router->getInitRoutes(), 'Список маршрутов пуст');
    }

    public function testInitRoutesIsArray()
    {
        $this->assertIsArray($this->router->getInitRoutes(), 'Список маршрутов не массив');
    }

    public function testInitRoutesArrayHasKey1()
    {
        $this->assertArrayHasKey('/calculation_rule', $this->router->getInitRoutes(), 'Список маршрутов не содержит \'/calculation_rule\'');
    }

    public function testInitRoutesArrayHasKey2()
    {
        $this->assertArrayHasKey('/calculated_data', $this->router->getInitRoutes(), 'Список маршрутов не содержит \'/calculated_data\'');
    }

    public function testDispatchIsString()
    {
        $result = $this->router->dispatch('/', []);
        $this->assertIsString($result, 'Возвращаемое значение - не строка');
    }

    public function testDispatchJson()
    {
        $result = $this->router->dispatch('/', []);
        $this->assertJson($result, 'Возвращаемое значение не json-строка');
    }

}
