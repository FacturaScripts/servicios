<?php
/**
 * This file is part of Servicios plugin for FacturaScripts
 * Copyright (C) 2020-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

        // creamos un estado
        $status = new EstadoAT();
        $status->nombre = 'Test state';
        $this->assertTrue($status->save(), 'Error creating EstadoAT');

        // creamos un servicio
        $service = new ServicioAT();
        $service->codalmacen = Tools::settings('default', 'codalmacen');
        $service->codcliente = $customer->codcliente;
        $service->descripcion = 'Test service';
        $service->idempresa = Tools::settings('default', 'idempresa');
        $service->idestado = $status->id;
        $this->assertTrue($service->save());

        // comprobamos que se ha creado
        $this->assertTrue($service->exists());

        // comprobamos que es editable
        $this->assertTrue($service->editable);

        // eliminamos el servicio
        $this->assertTrue($service->delete());
        $this->assertTrue($status->delete());
    }

    public function testChangeStatus(): void
    {
        // creamos un cliente
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save());

        // creamos un estado
        $status1 = new EstadoAT();
        $status1->nombre = 'Test state 1';
        $this->assertTrue($status1->save(), 'Error creating EstadoAT');

        // creamos un estado no editable
        $status2 = new EstadoAT();
        $status2->nombre = 'Test state 2';
        $status2->editable = false;
        $this->assertTrue($status2->save(), 'Error creating EstadoAT');

        // creamos un servicio
        $service = new ServicioAT();
        $service->codalmacen = Tools::settings('default', 'codalmacen');
        $service->codcliente = $customer->codcliente;
        $service->descripcion = 'Test service';
        $service->idempresa = Tools::settings('default', 'idempresa');
        $service->idestado = $status1->id;
        $this->assertTrue($service->save());

        // buscamos un estado no editable
        $status = new EstadoAT();
        $where = [new DataBaseWhere('editable', false)];
        $this->assertTrue($status->loadFromCode('', $where), 'Error loading EstadoAT');

        // asignamos el estado no editable
        $service->idestado = $status->id;
        $this->assertTrue($service->save(), 'Error saving ServicioAT');

        // recargamos el servicio
        $service->loadFromCode($service->idservicio);

        // comprobamos que el servicio ya no es editable
        $this->assertFalse($service->editable, 'Error checking editable ServicioAT');

        // buscamos un estado editable
        $status2 = new EstadoAT();
        $where = [new DataBaseWhere('editable', true)];
        $this->assertTrue($status2->loadFromCode('', $where), 'Error loading EstadoAT');

        // asignamos el estado editable
        $service->idestado = $status2->id;
        $this->assertTrue($service->save(), 'Error saving ServicioAT');

        // recargamos el servicio
        $service->loadFromCode($service->idservicio);

        // comprobamos que el servicio ya es editable
        $this->assertTrue($service->editable, 'Error checking editable ServicioAT');

        // eliminamos el servicio
        $this->assertTrue($service->delete(), 'Error deleting ServicioAT');
        $this->assertTrue($status1->delete(), 'Error deleting EstadoAT 1');
        $this->assertTrue($status2->delete(), 'Error deleting EstadoAT 2');
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
