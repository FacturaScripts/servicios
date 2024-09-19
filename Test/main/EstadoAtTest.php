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

use FacturaScripts\Plugins\Servicios\Model\EstadoAT;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

/**
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
final class EstadoAtTest extends TestCase
{
    use LogErrorsTrait;

    public function testCreate(): void
    {
        // creamos un estado
        $status = new EstadoAT();
        $status->nombre = 'Test state';
        $this->assertTrue($status->save(), 'Error creating EstadoAT');

        // eliminamos
        $this->assertTrue($status->delete(), 'Error deleting EstadoAT');
    }

    public function testDefaultStatus()
    {
        // creamos el estado 1
        $status1 = new EstadoAT();
        $status1->nombre = 'Test state 1';
        $status1->predeterminado = true;
        $this->assertTrue($status1->save(), 'Error creating EstadoAT 1');

        // comprobamos que el estado 1 es predeterminado
        $this->assertTrue($status1->predeterminado, 'Error checking predeterminado EstadoAT 1');

        // creamos el estado 2
        $status2 = new EstadoAT();
        $status2->nombre = 'Test state 2';
        $status2->predeterminado = true;
        $this->assertTrue($status2->save(), 'Error creating EstadoAT 2');

        // comprobamos que el estado 2 es predeterminado
        $this->assertTrue($status2->predeterminado, 'Error checking predeterminado EstadoAT 2');

        // comprobamos que el estado 1 ya no es predeterminado
        $status1->loadFromCode($status1->id);
        $this->assertFalse($status1->predeterminado, 'Error checking predeterminado EstadoAT 1');

        // eliminamos
        $this->assertTrue($status1->delete(), 'Error deleting EstadoAT 1');
        $this->assertTrue($status2->delete(), 'Error deleting EstadoAT 2');
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
