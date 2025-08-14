<?php

namespace FacturaScripts\Test\Plugins;

use FacturaScripts\Plugins\Servicios\Model\CategoriaAT;
use FacturaScripts\Plugins\Servicios\Model\CheckAT;
use FacturaScripts\Plugins\Servicios\Model\ServicioCategoriaAT;
use FacturaScripts\Plugins\Servicios\Model\ServicioAT;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class ServicioCategoriaAtTest extends TestCase
{
    use LogErrorsTrait;

    public function testInstall(): void
    {
        new CheckAT();
        $servicioCategoriaAT = new ServicioCategoriaAT();
        $this->assertEquals('', $servicioCategoriaAT->install());
    }

    public function testPrimaryColumn(): void
    {
        $this->assertEquals('id', ServicioCategoriaAT::primaryColumn());
    }

    public function testTableName(): void
    {
        $this->assertEquals('serviciosat_serviciocategorias', ServicioCategoriaAT::tableName());
    }

    public function testFunctionTrue(): void
    {
        $service = new ServicioCategoriaAT();
        $service->id = 999;
        $service->idcategory = 998;
        $service->idservice = 997;

        $this->assertTrue($service->test());
    }

    public function testUrlWhithoutParameters(): void
    {
        $categoria = new ServicioCategoriaAT();
        $this->assertEquals('EditServicioAT?code=&activetab=ListServicioCategoriaAT', $categoria->url());
    }

    public function testUrlWithParameters(): void
    {
        $categoria = new ServicioCategoriaAT();
        $categoria->idservice = 999;
        $this->assertEquals('EditServicioCategoriaAT', $categoria->url('edit', 'CustomList'));
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
