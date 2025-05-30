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

use FacturaScripts\Plugins\Servicios\Mod\SalesHeaderHTMLMod;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\Empresa;
use FacturaScripts\Dinamic\Model\Agente;
use FacturaScripts\Dinamic\Model\Almacen;
use FacturaScripts\Dinamic\Model\MaquinaAT;
use FacturaScripts\Dinamic\Model\TipoAT;
use FacturaScripts\Dinamic\Model\PrioridadAT;
use FacturaScripts\Dinamic\Model\EstadoAT;

/**
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
final class ModTest extends TestCase
{
    use LogErrorsTrait;
    use DefaultSettingsTrait;

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
        self::installAccountingPlan();
        self::removeTaxRegularization();
        new User();
        new Cliente();
        new Empresa();
        new Agente();
        new Almacen();
        new MaquinaAT();
        new TipoAT();
        new PrioridadAT();
        new EstadoAT();
    }

    public function testSalesHeaderHTMLMod()
    {
        new SalesHeaderHTMLMod();
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
