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
use FacturaScripts\Dinamic\Model\Stock;
use FacturaScripts\Plugins\Servicios\Lib\ServiceToInvoice;
use FacturaScripts\Plugins\Servicios\Model\ServicioAT;
use FacturaScripts\Plugins\Servicios\Model\TrabajoAT;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
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
final class TrabajoAtTest extends TestCase
{
    use DefaultSettingsTrait;
    use LogErrorsTrait;
    use RandomDataTrait;

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
        $this->assertTrue($service->save(), 'Error creating ServicioAT');

        // creamos un producto
        $product = $this->getRandomProduct();
        $product->precio = 17;
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

        // comprobamos que se han asignado el precio y descripción del producto
        $this->assertEquals($product->precio, $work->precio);
        $this->assertEquals($product->descripcion, $work->descripcion);

        // comprobamos que se ha actualizado el neto del servicio
        $service->loadFromCode($service->primaryColumnValue());
        $this->assertEquals($product->precio, $service->neto);

        // eliminamos el servicio
        $this->assertTrue($service->delete());

        // comprobamos que el trabajo se ha eliminado
        $this->assertFalse($work->exists());

        // eliminamos
        $this->assertTrue($customer->delete());
        $this->assertTrue($product->delete());
    }

    public function testEscapeHtml(): void
    {
        $html = '<br/>';
        $escaped = Tools::noHtml($html);

        // creamos un cliente
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save());

        // creamos un servicio
        $service = new ServicioAT();
        $service->codalmacen = Tools::settings('default', 'codalmacen');
        $service->codcliente = $customer->codcliente;
        $service->descripcion = 'Test service';
        $service->idempresa = Tools::settings('default', 'idempresa');
        $this->assertTrue($service->save(), 'Error creating ServicioAT');

        // creamos un trabajo
        $work = new TrabajoAT();
        $work->idservicio = $service->idservicio;
        $work->descripcion = $html;
        $work->observaciones = $html;
        $this->assertTrue($work->save(), 'Error creating TrabajoAT with HTML');

        // comprobamos que se ha escapado
        $this->assertEquals($escaped, $work->descripcion);
        $this->assertEquals($escaped, $work->observaciones);

        // eliminamos
        $this->assertTrue($service->delete());
        $this->assertTrue($customer->delete());
    }

    public function testDefaultStatus(): void
    {
        // establecemos el estado por defecto para los trabajos
        Tools::settingsSet('servicios', 'workstatus', TrabajoAT::STATUS_MAKE_ESTIMATION);

        // creamos un cliente
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save());

        // creamos un servicio
        $service = new ServicioAT();
        $service->codalmacen = Tools::settings('default', 'codalmacen');
        $service->codcliente = $customer->codcliente;
        $service->descripcion = 'Test service';
        $service->idempresa = Tools::settings('default', 'idempresa');
        $this->assertTrue($service->save(), 'Error creating ServicioAT');

        // creamos un trabajo
        $work1 = new TrabajoAT();
        $work1->idservicio = $service->idservicio;
        $work1->descripcion = 'Test work';
        $this->assertTrue($work1->save(), 'Error creating TrabajoAT with default status');

        // comprobamos que se ha asignado el estado por defecto
        $this->assertEquals(TrabajoAT::STATUS_MAKE_ESTIMATION, $work1->estado);

        // cambiamos el estado predefinido
        Tools::settingsSet('servicios', 'workstatus', TrabajoAT::STATUS_NONE);

        // creamos otro trabajo
        $work2 = new TrabajoAT();
        $work2->idservicio = $service->idservicio;
        $work2->descripcion = 'Test work 2';
        $this->assertTrue($work2->save(), 'Error creating TrabajoAT with default status');

        // comprobamos que se ha asignado el nuevo estado por defecto
        $this->assertEquals(TrabajoAT::STATUS_NONE, $work2->estado);

        // eliminamos
        $this->assertTrue($service->delete());
        $this->assertTrue($customer->delete());
    }

    public function testUpdateStock(): void
    {
        // desactivamos la opción de restar stock
        Tools::settingsSet('servicios', 'disablestockmanagement', true);

        // creamos un cliente
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save());

        // creamos un servicio
        $service = new ServicioAT();
        $service->codalmacen = Tools::settings('default', 'codalmacen');
        $service->codcliente = $customer->codcliente;
        $service->descripcion = 'Test service';
        $service->idempresa = Tools::settings('default', 'idempresa');
        $this->assertTrue($service->save(), 'Error creating ServicioAT');

        // creamos un producto
        $product = $this->getRandomProduct();
        $product->precio = 17;
        $product->nostock = false;
        $product->ventasinstock = false;
        $this->assertTrue($product->save(), 'Error creating Producto');

        // añadimos stock
        $stock = new Stock();
        $stock->referencia = $product->referencia;
        $stock->codalmacen = Tools::settings('default', 'codalmacen');
        $stock->cantidad = 10;
        $this->assertTrue($stock->save(), 'Error creating Stock');

        // creamos un trabajo
        $work1 = new TrabajoAT();
        $work1->idservicio = $service->idservicio;
        $work1->referencia = $product->referencia;
        $work1->cantidad = 2;
        $work1->estado = TrabajoAT::STATUS_MAKE_INVOICE;
        $this->assertTrue($work1->save(), 'Error creating TrabajoAT with stock');

        // comprobamos que no se ha restado el stock
        $stock->loadFromCode($stock->primaryColumnValue());
        $this->assertEquals(10, $stock->cantidad);

        // activamos la opción de restar stock
        Tools::settingsSet('servicios', 'disablestockmanagement', false);

        // creamos otro trabajo
        $work2 = new TrabajoAT();
        $work2->idservicio = $service->idservicio;
        $work2->referencia = $product->referencia;
        $work2->cantidad = 3;
        $work2->estado = TrabajoAT::STATUS_MAKE_INVOICE;
        $this->assertTrue($work2->save(), 'Error creating TrabajoAT with stock');

        // comprobamos que se ha restado el stock
        $stock->loadFromCode($stock->primaryColumnValue());
        $this->assertEquals(7, $stock->cantidad);

        // eliminamos el trabajo 2
        $this->assertTrue($work2->delete(), 'Error deleting TrabajoAT with stock');

        // comprobamos que se ha sumado el stock
        $stock->loadFromCode($stock->primaryColumnValue());
        $this->assertEquals(10, $stock->cantidad);

        // desactivamos la opción de restar stock
        Tools::settingsSet('servicios', 'disablestockmanagement', true);

        //eliminamos el trabajo 1
        $this->assertTrue($work1->delete(), 'Error deleting TrabajoAT with stock');

        // comprobamos que no se ha restado el stock
        $stock->loadFromCode($stock->primaryColumnValue());
        $this->assertEquals(10, $stock->cantidad);

        // eliminamos
        $this->assertTrue($service->delete());
        $this->assertTrue($customer->delete());
        $this->assertTrue($product->delete());
    }

    public function testCreateEstimation(): void
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
        $this->assertTrue($service->save(), 'Error creating ServicioAT');

        // creamos un trabajo con estado hacer presupuesto
        $work = new TrabajoAT();
        $work->idservicio = $service->idservicio;
        $work->descripcion = 'Test work';
        $work->cantidad = 2;
        $work->precio = 10;
        $work->estado = TrabajoAT::STATUS_MAKE_ESTIMATION;
        $this->assertTrue($work->save(), 'Error creating TrabajoAT with estimation');

        // generamos el presupuesto
        ServiceToInvoice::clear();
        $done = ServiceToInvoice::estimation($service);
        $this->assertTrue($done, 'Error generating estimation');

        // comprobamos que se ha generado el presupuesto correctamente
        $generated = ServiceToInvoice::generated();
        $this->assertCount(1, $generated, 'Error generating estimation');
        $this->assertEquals($service->codcliente, $generated[0]->codcliente, 'Error generating estimation');
        $this->assertEquals(20, $generated[0]->neto, 'Error generating estimation');

        // comprobamos las líneas
        $lines = $generated[0]->getLines();
        $this->assertCount(1, $lines, 'Error generating estimation');
        $this->assertEquals($work->descripcion, $lines[0]->descripcion, 'Error generating estimation');
        $this->assertEquals($work->cantidad, $lines[0]->cantidad, 'Error generating estimation');
        $this->assertEquals($work->precio, $lines[0]->pvpunitario, 'Error generating estimation');

        // eliminamos
        $this->assertTrue($generated[0]->delete());
        $this->assertTrue($service->delete());
        $this->assertTrue($customer->delete());
    }

    public function testCreateDeliveryNote(): void
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
        $this->assertTrue($service->save(), 'Error creating ServicioAT');

        // creamos un trabajo con estado hacer albarán
        $work1 = new TrabajoAT();
        $work1->idservicio = $service->idservicio;
        $work1->descripcion = 'Test work 1';
        $work1->cantidad = 3;
        $work1->precio = 5;
        $work1->estado = TrabajoAT::STATUS_MAKE_DELIVERY_NOTE;
        $this->assertTrue($work1->save(), 'Error creating TrabajoAT with delivery note');

        // creamos otro trabajo con estado hacer albarán
        $work2 = new TrabajoAT();
        $work2->idservicio = $service->idservicio;
        $work2->descripcion = 'Test work 2';
        $work2->cantidad = 2;
        $work2->precio = 10;
        $work2->estado = TrabajoAT::STATUS_MAKE_DELIVERY_NOTE;
        $this->assertTrue($work2->save(), 'Error creating TrabajoAT with delivery note');

        // generamos el albarán
        ServiceToInvoice::clear();
        $done = ServiceToInvoice::deliveryNote($service);
        $this->assertTrue($done, 'Error generating delivery note');

        // comprobamos que se ha generado el albarán correctamente
        $generated = ServiceToInvoice::generated();
        $this->assertCount(1, $generated, 'Error generating delivery note');
        $this->assertEquals($service->codcliente, $generated[0]->codcliente, 'Error generating delivery note');
        $this->assertEquals(35, $generated[0]->neto, 'Error generating delivery note');

        // comprobamos las líneas
        $lines = $generated[0]->getLines();
        $this->assertCount(2, $lines, 'Error generating delivery note');
        $this->assertEquals($work1->descripcion, $lines[0]->descripcion, 'Error generating delivery note');
        $this->assertEquals($work1->cantidad, $lines[0]->cantidad, 'Error generating delivery note');
        $this->assertEquals($work1->precio, $lines[0]->pvpunitario, 'Error generating delivery note');
        $this->assertEquals($work2->descripcion, $lines[1]->descripcion, 'Error generating delivery note');
        $this->assertEquals($work2->cantidad, $lines[1]->cantidad, 'Error generating delivery note');
        $this->assertEquals($work2->precio, $lines[1]->pvpunitario, 'Error generating delivery note');

        // eliminamos
        $this->assertTrue($generated[0]->delete());
        $this->assertTrue($service->delete());
        $this->assertTrue($customer->delete());
    }

    public function testCreateInvoice(): void
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
        $this->assertTrue($service->save(), 'Error creating ServicioAT');

        // creamos un trabajo con estado hacer factura
        $work1 = new TrabajoAT();
        $work1->idservicio = $service->idservicio;
        $work1->descripcion = 'Test work 1';
        $work1->cantidad = 3;
        $work1->precio = 6;
        $work1->estado = TrabajoAT::STATUS_MAKE_INVOICE;
        $this->assertTrue($work1->save(), 'Error creating TrabajoAT with invoice');

        // creamos un trabajo con estado no hacer nada
        $work2 = new TrabajoAT();
        $work2->idservicio = $service->idservicio;
        $work2->descripcion = 'Test work 2';
        $work2->cantidad = 2;
        $work2->precio = 10;
        $work2->estado = TrabajoAT::STATUS_NONE;
        $this->assertTrue($work2->save(), 'Error creating TrabajoAT with invoice');

        // generamos la factura
        ServiceToInvoice::clear();
        $done = ServiceToInvoice::invoice($service);
        $this->assertTrue($done, 'Error generating invoice');

        // comprobamos que se ha generado la factura correctamente
        $generated = ServiceToInvoice::generated();
        $this->assertCount(1, $generated, 'Error generating invoice');
        $this->assertEquals($service->codcliente, $generated[0]->codcliente, 'Error generating invoice');
        $this->assertEquals(18, $generated[0]->neto, 'Error generating invoice');

        // comprobamos las líneas
        $lines = $generated[0]->getLines();
        $this->assertCount(1, $lines, 'Error generating invoice');
        $this->assertEquals($work1->descripcion, $lines[0]->descripcion, 'Error generating invoice');
        $this->assertEquals($work1->cantidad, $lines[0]->cantidad, 'Error generating invoice');
        $this->assertEquals($work1->precio, $lines[0]->pvpunitario, 'Error generating invoice');

        // eliminamos
        $this->assertTrue($generated[0]->delete());
        $this->assertTrue($service->delete());
        $this->assertTrue($customer->delete());
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
