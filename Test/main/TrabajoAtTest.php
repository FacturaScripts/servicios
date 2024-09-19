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
use FacturaScripts\Plugins\Servicios\Model\EstadoAT;
use FacturaScripts\Plugins\Servicios\Model\ServicioAT;
use FacturaScripts\Plugins\Servicios\Model\TrabajoAT;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

/**
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
final class TrabajoAtTest extends TestCase
{
    use LogErrorsTrait;
    use RandomDataTrait;

    public function testCreate(): void
    {
        // creamos un cliente
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save(), 'Error creating Cliente');

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
        $this->assertTrue($service->save(), 'Error creating ServicioAT');

        // creamos un producto
        $product = $this->getRandomProduct();
        $this->assertTrue($product->save(), 'Error creating Producto');

        // creamos el trabajo
        $work = new TrabajoAT();
        $work->idservicio = $service->idservicio;
        $work->observaciones = 'Test work';
        $work->fechainicio = Tools::date();
        $work->horainicio = Tools::hour();
        $work->referencia = $product->referencia;
        $work->cantidad = 1;
        $this->assertTrue($work->save(), 'Error creating TrabajoAT');

        // eliminamos
        $this->assertTrue($work->delete(), 'Error deleting TrabajoAT');
        $this->assertTrue($service->delete(), 'Error deleting ServicioAT');
        $this->assertTrue($customer->delete(), 'Error deleting Cliente');
        $this->assertTrue($product->delete(), 'Error deleting Producto');
        $this->assertTrue($status->delete(), 'Error deleting EstadoAT');
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
