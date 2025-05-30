<?php
/**
 * This file is part of Servicios plugin for FacturaScripts
 * Copyright (C) 2020-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Model\ServicioAT;
use PHPUnit\Framework\TestCase;
use FacturaScripts\Dinamic\Model\TipoAT;
use FacturaScripts\Dinamic\Model\PrioridadAT;
use FacturaScripts\Dinamic\Model\EstadoAT;

final class ServicioAtTest extends TestCase
{
    use MacroThreat;

    public static function setUpBeforeClass(): void
    {
        self::installDependencies();
    }

    public function testCreate(): void
    {
        // creamos un cliente
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save());

        // creamos un servicio
        $service = new ServicioAT();
        $service->codalmacen = Tools::settings('default', 'codalmacen');
        $service->codcliente = $customer->codcliente;
        $service->descripcion = 'Test service';
        $service->idempresa = Tools::settings('default', 'idempresa');
        $this->assertTrue($service->save());

        // comprobamos que se ha creado
        $this->assertTrue($service->exists());

        // comprobamos que es editable
        $this->assertTrue($service->editable);

        // eliminamos
        $this->assertTrue($service->delete());
        $this->assertTrue($customer->delete());
    }

    public function testEscapeHtml(): void
    {
        $html = '<br/>';

        // creamos un cliente
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save());

        // creamos un servicio
        $service = new ServicioAT();
        $service->codalmacen = Tools::settings('default', 'codalmacen');
        $service->codcliente = $customer->codcliente;
        $service->descripcion = $html;
        $service->idempresa = Tools::settings('default', 'idempresa');
        $service->material = $html;
        $service->observaciones = $html;
        $service->solucion = $html;
        $service->telefono1 = $html;
        $service->telefono2 = $html;
        $this->assertTrue($service->save());

        // comprobamos que se ha escapado el html
        $escaped = Tools::noHtml($html);
        $this->assertEquals($escaped, $service->descripcion);
        $this->assertEquals($escaped, $service->material);
        $this->assertEquals($escaped, $service->observaciones);
        $this->assertEquals($escaped, $service->solucion);
        $this->assertEquals($escaped, $service->telefono1);
        $this->assertEquals($escaped, $service->telefono2);

        // eliminamos
        $this->assertTrue($service->delete());
        $this->assertTrue($customer->delete());
    }

    public function testDefaultStatus(): void
    {
        // creamos un cliente
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save());

        // creamos un estado por defecto
        $status = new EstadoAT();
        $status->nombre = 'Test state';
        $status->editable = true;
        $status->predeterminado = true;
        $this->assertTrue($status->save(), 'Error creating EstadoAT');

        // creamos un servicio
        $service = new ServicioAT();
        $service->codalmacen = Tools::settings('default', 'codalmacen');
        $service->codcliente = $customer->codcliente;
        $service->descripcion = 'Test service';
        $service->idempresa = Tools::settings('default', 'idempresa');
        $this->assertTrue($service->save(), 'Error saving ServicioAT');

        // comprobamos que tiene el estado por defecto
        $this->assertEquals($status->id, $service->idestado, 'Error checking default status ServicioAT');

        // eliminamos
        $this->assertTrue($service->delete());
        $this->assertTrue($status->delete());
        $this->assertTrue($customer->delete());
    }

    public function testChangeStatus(): void
    {
        // creamos un cliente
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save());

        // creamos un estado
        $status1 = new EstadoAT();
        $status1->nombre = 'Test state 3';
        $status1->editable = true;
        $this->assertTrue($status1->save(), 'Error creating EstadoAT');

        // creamos un estado no editable
        $status2 = new EstadoAT();
        $status2->nombre = 'Test state 4';
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

        // asignamos el estado no editable
        $service->idestado = $status2->id;
        $this->assertTrue($service->save(), 'Error saving ServicioAT');

        // recargamos el servicio
        $service->loadFromCode($service->idservicio);

        // comprobamos que el servicio ya no es editable
        $this->assertFalse($service->editable, 'Error checking editable ServicioAT');

        // asignamos el estado editable
        $service->idestado = $status1->id;
        $this->assertTrue($service->save(), 'Error saving ServicioAT');

        // recargamos el servicio
        $service->loadFromCode($service->idservicio);

        // comprobamos que el servicio ya es editable
        $this->assertTrue($service->editable, 'Error checking editable ServicioAT');

        // eliminamos
        $this->assertTrue($service->delete());
        $this->assertTrue($status1->delete());
        $this->assertTrue($status2->delete());
        $this->assertTrue($customer->delete());
    }

    public function testStatusAssigned(): void
    {
        // creamos un cliente
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save());

        // creamos un usuario
        $user = $this->getRandomUser();
        $user->password = Tools::randomString(8) . rand(1111, 9999);
        $this->assertTrue($user->save());

        // creamos un estado con usuario asignado
        $status = new EstadoAT();
        $status->nombre = 'Test state';
        $status->asignado = $user->nick;
        $this->assertTrue($status->save(), 'Error creating EstadoAT');

        // creamos un servicio con estado asignado
        $service = new ServicioAT();
        $service->codalmacen = Tools::settings('default', 'codalmacen');
        $service->codcliente = $customer->codcliente;
        $service->descripcion = 'Test service';
        $service->idempresa = Tools::settings('default', 'idempresa');
        $service->idestado = $status->id;

        // comprobamos que no tiene asignado (antes de guardar)
        $this->assertNull($service->asignado, 'Error checking asignado ServicioAT');

        // guardamos el servicio
        $this->assertTrue($service->save(), 'Error saving ServicioAT');

        // comprobamos que tiene asignado (después de guardar)
        $this->assertEquals($user->nick, $service->asignado, 'Error checking asignado ServicioAT');

        // eliminamos
        $this->assertTrue($service->delete());
        $this->assertTrue($status->delete());
        $this->assertTrue($customer->delete());
        $this->assertTrue($user->delete());
    }

    public function testDefaultPriority(): void
    {
        // creamos una prioridad por defecto
        $priority = new PrioridadAT();
        $priority->nombre = 'Test priority';
        $priority->predeterminado = true;
        $this->assertTrue($priority->save());

        // creamos un cliente
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save());

        // creamos un servicio
        $service = new ServicioAT();
        $service->codalmacen = Tools::settings('default', 'codalmacen');
        $service->codcliente = $customer->codcliente;
        $service->descripcion = 'Test service';
        $service->idempresa = Tools::settings('default', 'idempresa');
        $this->assertTrue($service->save());

        // comprobamos que tiene la prioridad por defecto
        $this->assertEquals($priority->id, $service->idprioridad, 'Error checking default priority ServicioAT');

        // eliminamos
        $this->assertTrue($service->delete());
        $this->assertTrue($priority->delete());
        $this->assertTrue($customer->delete());
    }

    public function testDefaultType(): void
    {
        // creamos un tipo por defecto
        $type = new TipoAT();
        $type->name = 'Test type';
        $type->default = true;
        $this->assertTrue($type->save());

        // creamos un cliente
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save());

        // creamos un servicio
        $service = new ServicioAT();
        $service->codalmacen = Tools::settings('default', 'codalmacen');
        $service->codcliente = $customer->codcliente;
        $service->descripcion = 'Test service';
        $service->idempresa = Tools::settings('default', 'idempresa');
        $this->assertTrue($service->save());

        // comprobamos que tiene el tipo por defecto
        $this->assertEquals($type->id, $service->idtipo, 'Error checking default type ServicioAT');

        // eliminamos
        $this->assertTrue($service->delete());
        $this->assertTrue($type->delete());
        $this->assertTrue($customer->delete());
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
