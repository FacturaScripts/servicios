<?php
/**
 * This file is part of Servicios plugin for FacturaScripts
 * Copyright (C) 2024 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Test\Plugins;

use FacturaScripts\Core\Tools;
use FacturaScripts\Plugins\Servicios\Model\MaquinaAT;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

/**
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
final class MaquinaAtTest extends TestCase
{
    use LogErrorsTrait;
    use RandomDataTrait;
    use DefaultSettingsTrait;

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
        self::installAccountingPlan();
        self::removeTaxRegularization();
        
    }

    public function testCreate(): void
    {
        // creamos un cliente
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save());

        // creamos la maquina
        $machine = new MaquinaAT();
        $machine->nombre = 'Test machine';
        $machine->codcliente = $customer->codcliente;
        $this->assertTrue($machine->save(), 'Error creating MaquinaAT');

        // eliminamos
        $this->assertTrue($machine->delete());
        $this->assertTrue($customer->delete());
    }

    public function testEscapeHtml(): void
    {
        $html = '<br/>';
        $escaped = Tools::noHtml($html);

        // creamos una máquina
        $machine = new MaquinaAT();
        $machine->descripcion = $html;
        $machine->nombre = $html;
        $machine->numserie = $html;
        $machine->referencia = $html;
        $this->assertTrue($machine->save(), 'Error creating MaquinaAT with HTML');

        // comprobamos que se ha escapado
        $this->assertEquals($escaped, $machine->descripcion);
        $this->assertEquals($escaped, $machine->nombre);
        $this->assertEquals($escaped, $machine->numserie);
        $this->assertEquals($escaped, $machine->referencia);

        // eliminamos
        $this->assertTrue($machine->delete());
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
