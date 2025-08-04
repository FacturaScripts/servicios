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

use FacturaScripts\Core\Tools;
use FacturaScripts\Plugins\Servicios\Model\CategoriaAT;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

/**
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
final class CategoriaAtTest extends TestCase
{
    use LogErrorsTrait;

    public function testPrimaryColumn(): void
    {
        $this->assertSame('id', CategoriaAT::primaryColumn());
    }

    public function testTableName(): void
    {
        $this->assertSame('serviciosat_categorias', CategoriaAT::tableName());
    }

    public function testFunction(): void
    {
        $categoria = new CategoriaAT();
        $categoria->name = '<strong>Test Function</strong>';

        $resultado = $categoria->test();

        $this->assertTrue($resultado);
        $this->assertSame('&lt;strong&gt;Test Function&lt;/strong&gt;', $categoria->name);
    }

    public function testUrlWhithoutParameters(): void
    {
        $categoria = new CategoriaAT();
        $this->assertSame('AdminServicios?activetab=ListCategoriaAT', $categoria->url());
    }

    public function testUrlWithParameters(): void
    {
        $categoria = new CategoriaAT();
        $categoria->id = 999;
        $this->assertSame('EditCategoriaAT?code=999', $categoria->url('edit', 'CustomList'));
    }

}