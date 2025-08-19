<?php

namespace FacturaScripts\Test\Plugins;

use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;
use FacturaScripts\Plugins\Servicios\Model\ServicioCheckAT;

final class ServicioCheckAtTest extends TestCase
{
    use LogErrorsTrait;

    public function testInstall(): void
    {
        $service = new ServicioCheckAT();
        $this->assertEquals('', $service->install());
    }

    public function testPrimaryColumn(): void
    {
        $this->assertEquals('id', ServicioCheckAT::primaryColumn());
    }

    public function testTableName(): void
    {
        $this->assertEquals('serviciosat_serviciochecks', ServicioCheckAT::tableName());
    }

    public function testFunction(): void
    {
        $service = new ServicioCheckAT();
        $service->id = 1;
        $service->idcheck = 2;
        $service->idservice = 3;
        $service->checked = true;

        $this->assertTrue($service->test());
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
