<?php

namespace FacturaScripts\Test\Plugins;

use FacturaScripts\Plugins\Servicios\Model\CategoriaAT;
use FacturaScripts\Plugins\Servicios\Model\CheckAT;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class CheckAtTest extends TestCase
{
    use LogErrorsTrait;

    public function testInstall(): void
    {
        $checkAT = new CheckAT();
        new CategoriaAT();
        $this->assertEquals('', $checkAT->install());
    }

    public function testPrimaryColumn(): void
    {
        $this->assertEquals('id', CheckAT::primaryColumn());
    }

    public function testTableName(): void
    {
        $this->assertEquals('serviciosat_checks', CheckAT::tableName());
    }

    public function testFunction(): void
    {
        $checkAT = new CheckAT();
        $checkAT->priority = null;
        
        $this->assertEquals(0, $checkAT->priority);
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
