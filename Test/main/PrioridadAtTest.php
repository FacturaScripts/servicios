<?php
/**
 * This file is part of Servicios plugin for FacturaScripts
 * Copyright (C) 2024-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Tools;
use FacturaScripts\Plugins\Servicios\Model\PrioridadAT;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

/**
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
final class PrioridadAtTest extends TestCase
{
    use LogErrorsTrait;
    use DefaultSettingsTrait;

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
        self::installAccountingPlan();
        self::removeTaxRegularization();
        
    }

    public function testCreate(): void
    {
        // creamos una prioridad
        $priority = new PrioridadAT();
        $priority->nombre = 'Test priority';
        $this->assertTrue($priority->save(), 'Error creating PrioridadAT');

        // eliminamos
        $this->assertTrue($priority->delete());
    }

    public function testEscapeHtml(): void
    {
        $html = '<br/>';
        $escaped = Tools::noHtml($html);

        // creamos una prioridad
        $priority = new PrioridadAT();
        $priority->nombre = $html;
        $this->assertTrue($priority->save(), 'Error creating PrioridadAT');

        // comprobamos que el nombre es el esperado
        $this->assertEquals($escaped, $priority->nombre, 'Error checking escape html PrioridadAT');

        // eliminamos
        $this->assertTrue($priority->delete());
    }

    public function testDefaultStatus(): void
    {
        // creamos la prioridad 1
        $priority1 = new PrioridadAT();
        $priority1->nombre = 'Test priority 1';
        $priority1->predeterminado = true;
        $this->assertTrue($priority1->save(), 'Error creating PrioridadAT 1');

        // comprobamos que la prioridad 1 es predeterminada
        $this->assertTrue($priority1->predeterminado, 'Error checking predeterminado PrioridadAT 1');

        // creamos la prioridad 2
        $priority2 = new PrioridadAT();
        $priority2->nombre = 'Test priority 2';
        $priority2->predeterminado = true;
        $this->assertTrue($priority2->save(), 'Error creating PrioridadAT 2');

        // comprobamos que la prioridad 2 es predeterminada
        $this->assertTrue($priority2->predeterminado, 'Error checking predeterminado PrioridadAT 2');

        // comprobamos que la prioridad 1 ya no es predeterminada
        $priority1->loadFromCode($priority1->id);
        $this->assertFalse($priority1->predeterminado, 'Error checking predeterminado PrioridadAT 1');

        // eliminamos
        $this->assertTrue($priority1->delete());
        $this->assertTrue($priority2->delete());

        // comprobamos que queda alguna prioridad predeterminada
        $whereDefault = [new DataBaseWhere('predeterminado', true)];
        $priorities = PrioridadAT::all($whereDefault);
        $this->assertNotEmpty($priorities, 'Error checking predeterminado PrioridadAT');
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
