<?php
/**
 * This file is part of Servicios plugin for FacturaScripts
 * Copyright (C) 2020-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Plugins\Servicios\Model\EstadoAT;
use FacturaScripts\Plugins\Servicios\Model\ServicioAT;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class ServicioAtTest extends TestCase
{
    use DefaultSettingsTrait;
    use LogErrorsTrait;
    use RandomDataTrait;

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
    }

    public function testCreate(): void
    {
        // creamos un cliente
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save());

        // creamos un servicio
        $service = new ServicioAT();
        $service->codalmacen = AppSettings::get('default', 'codalmacen');
        $service->codcliente = $customer->codcliente;
        $service->descripcion = 'Test service';
        $service->idempresa = AppSettings::get('default', 'idempresa');
        $this->assertTrue($service->save());

        // comprobamos que se ha creado
        $this->assertTrue($service->exists());

        // comprobamos que es editable
        $this->assertTrue($service->editable);

        // eliminamos el servicio
        $this->assertTrue($service->delete());
    }

    public function testChangeStatus(): void
    {
        // creamos un cliente
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save());

        // creamos un servicio
        $service = new ServicioAT();
        $service->codalmacen = AppSettings::get('default', 'codalmacen');
        $service->codcliente = $customer->codcliente;
        $service->descripcion = 'Test service';
        $service->idempresa = AppSettings::get('default', 'idempresa');
        $this->assertTrue($service->save());

        // buscamos un estado no editable
        $status = new EstadoAT();
        $where = [new DataBaseWhere('editable', false)];
        $this->assertTrue($status->loadFromCode('', $where));

        // asignamos el estado no editable
        $service->idestado = $status->id;
        $this->assertTrue($service->save());

        // recargamos el servicio
        $service->loadFromCode($service->idservicio);

        // comprobamos que el servicio ya no es editable
        $this->assertFalse($service->editable);

        // buscamos un estado editable
        $status2 = new EstadoAT();
        $where = [new DataBaseWhere('editable', true)];
        $this->assertTrue($status2->loadFromCode('', $where));

        // asignamos el estado editable
        $service->idestado = $status2->id;
        $this->assertTrue($service->save());

        // recargamos el servicio
        $service->loadFromCode($service->idservicio);

        // comprobamos que el servicio ya es editable
        $this->assertTrue($service->editable);

        // eliminamos el servicio
        $this->assertTrue($service->delete());
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
