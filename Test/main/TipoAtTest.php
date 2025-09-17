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
use FacturaScripts\Dinamic\Model\TipoAT;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

/**
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
final class TipoAtTest extends TestCase
{
    use LogErrorsTrait;

    public function testCreate(): void
    {
        // creamos un tipo
        $type = new TipoAT();
        $type->name = 'Test type';
        $this->assertTrue($type->save(), 'Error creating TipoAT');

        // eliminamos
        $this->assertTrue($type->delete());
    }

    public function testEscapeHtml(): void
    {
        $html = '<br/>';
        $escaped = Tools::noHtml($html);

        // creamos un tipo
        $type = new TipoAT();
        $type->name = $html;
        $this->assertTrue($type->save(), 'Error creating TipoAT');

        // comprobamos que el nombre se ha escapado
        $this->assertEquals($escaped, $type->name, 'Error checking escaped name');

        // eliminamos
        $this->assertTrue($type->delete());
    }

    public function testDefaultStatus(): void
    {
        // creamos el tipo 1
        $type1 = new TipoAT();
        $type1->name = 'Test type 1';
        $type1->default = true;
        $this->assertTrue($type1->save(), 'Error creating TipoAT 1');

        // comprobamos que el tipo 1 es predeterminado
        $this->assertTrue($type1->default, 'Error checking default TipoAT 1');

        // creamos el tipo 2
        $type2 = new TipoAT();
        $type2->name = 'Test type 2';
        $type2->default = true;
        $this->assertTrue($type2->save(), 'Error creating TipoAT 2');

        // comprobamos que el tipo 2 es predeterminado
        $this->assertTrue($type2->default, 'Error checking default TipoAT 2');

        // comprobamos que el tipo 1 ya no es predeterminado
        $type1->load($type1->id);
        $this->assertFalse($type1->default, 'Error checking default TipoAT 1');

        // eliminamos
        $this->assertTrue($type1->delete());
        $this->assertTrue($type2->delete());
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
