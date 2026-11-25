<?php
/**
 * This file is part of Servicios plugin for FacturaScripts
 * Copyright (C) 2024-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Lib\BusinessDocumentGenerator;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Lib\ServiceToInvoice;
use FacturaScripts\Dinamic\Model\ServicioAT;
use FacturaScripts\Dinamic\Model\ServicioATLog;
use FacturaScripts\Dinamic\Model\Stock;
use FacturaScripts\Dinamic\Model\TrabajoAT;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

/**
 * @author Daniel Fernández Giménez <contacto@danielfg.es>
 */
final class TrabajoAtTest extends TestCase
{
    use DefaultSettingsTrait;
    use LogErrorsTrait;
    use RandomDataTrait;

    /**
     * Establece los ajustes por defecto (almacén, empresa...) para poder ejecutar estos tests de forma aislada
     * y carga los modelos del core para que sus tablas existan antes de los tests:
     * no se pueden crear tablas dentro de una transacción
     * (por ejemplo, productos_imagenes al borrar un producto).
     */
    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
        self::loadCoreModels();
    }

    /**
     * calculateHours() suma los días entre las fechas (por 24) y la diferencia de horas.
     */
    public function testCalculateHours(): void
    {
        // el mismo día: solo cuenta la diferencia de horas
        $work = new TrabajoAT();
        $work->fechainicio = '2026-01-01';
        $work->fechafin = '2026-01-01';
        $work->horainicio = '09:00:00';
        $work->horafin = '12:15:00';
        $this->assertEquals(3.25, $work->calculateHours(), 'same-day-hours');

        // de un día para otro: 24 horas más la diferencia
        $work->fechafin = '2026-01-02';
        $work->horainicio = '08:00:00';
        $work->horafin = '10:30:00';
        $this->assertEquals(26.5, $work->calculateHours(), 'next-day-hours');

        // sin fecha ni hora de fin no hay horas
        $work->fechafin = null;
        $work->horafin = null;
        $this->assertEquals(0.0, $work->calculateHours(), 'no-end-hours');
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
        $service->load($service->id());
        $this->assertEquals($product->precio, $service->neto);

        // eliminamos el servicio
        $this->assertTrue($service->delete());

        // comprobamos que el trabajo se ha eliminado
        $this->assertFalse($work->exists());

        // eliminamos
        $this->assertTrue($customer->delete());
        $this->assertTrue($product->delete());
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
        $this->assertEquals($work1->idtrabajo, $lines[0]->idtrabajo, 'Error saving idtrabajo in delivery note line');
        $this->assertEquals($work2->descripcion, $lines[1]->descripcion, 'Error generating delivery note');
        $this->assertEquals($work2->cantidad, $lines[1]->cantidad, 'Error generating delivery note');
        $this->assertEquals($work2->precio, $lines[1]->pvpunitario, 'Error generating delivery note');
        $this->assertEquals($work2->idtrabajo, $lines[1]->idtrabajo, 'Error saving idtrabajo in delivery note line');

        // eliminamos
        $this->assertTrue($generated[0]->delete());
        $this->assertTrue($service->delete());
        $this->assertTrue($customer->delete());
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
        $this->assertEquals($work->idtrabajo, $lines[0]->idtrabajo, 'Error saving idtrabajo in estimation line');

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
        $this->assertEquals($work1->idtrabajo, $lines[0]->idtrabajo, 'Error saving idtrabajo in invoice line');

        // eliminamos
        $this->assertTrue($generated[0]->delete());
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

    /**
     * Al cambiar la cantidad de un trabajo sin referencia, el historial nombra el trabajo por su
     * descripción y no deja el marcador %reference% sin sustituir.
     */
    public function testQuantityLogWithoutReference(): void
    {
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save(), 'customer-cant-save');

        $service = new ServicioAT();
        $service->codalmacen = Tools::settings('default', 'codalmacen');
        $service->codcliente = $customer->codcliente;
        $service->idempresa = Tools::settings('default', 'idempresa');
        $this->assertTrue($service->save(), 'service-cant-save');

        $work = new TrabajoAT();
        $work->idservicio = $service->idservicio;
        $work->descripcion = 'Trabajo sin referencia';
        $work->estado = TrabajoAT::STATUS_NONE;
        $this->assertTrue($work->save(), 'work-cant-save');

        $work->cantidad = 3;
        $this->assertTrue($work->save(), 'work-cant-update');

        $log = new ServicioATLog();
        $where = [Where::eq('idservicio', $service->idservicio)];
        $this->assertTrue($log->loadWhere($where, ['id' => 'DESC']), 'log-not-found');
        $this->assertStringNotContainsString('%reference%', $log->message);
        $this->assertStringContainsString('Trabajo sin referencia', $log->message);

        // limpiamos
        $this->assertTrue($service->delete(), 'service-cant-delete');
        $this->assertTrue($customer->getDefaultAddress()->delete(), 'address-cant-delete');
        $this->assertTrue($customer->delete(), 'customer-cant-delete');
    }

    public function testServiceLineHasNoWork(): void
    {
        // activamos la opción de añadir la descripción del servicio al documento
        Tools::settingsSet('servicios', 'document_description', true);

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

        // creamos un trabajo ya facturado
        $work1 = new TrabajoAT();
        $work1->idservicio = $service->idservicio;
        $work1->descripcion = 'Test work 1';
        $work1->cantidad = 1;
        $work1->precio = 10;
        $work1->estado = TrabajoAT::STATUS_INVOICED;
        $this->assertTrue($work1->save(), 'Error creating invoiced TrabajoAT');

        // creamos un trabajo pendiente de facturar
        $work2 = new TrabajoAT();
        $work2->idservicio = $service->idservicio;
        $work2->descripcion = 'Test work 2';
        $work2->cantidad = 2;
        $work2->precio = 5;
        $work2->estado = TrabajoAT::STATUS_MAKE_INVOICE;
        $this->assertTrue($work2->save(), 'Error creating TrabajoAT to invoice');

        // generamos la factura
        ServiceToInvoice::clear();
        $this->assertTrue(ServiceToInvoice::invoice($service), 'Error generating invoice');
        $generated = ServiceToInvoice::generated();
        $this->assertCount(1, $generated, 'Error generating invoice');

        // la línea del servicio no pertenece a ningún trabajo y la del trabajo lleva su idtrabajo
        $lines = $generated[0]->getLines();
        $this->assertCount(2, $lines, 'Error generating invoice with service line');
        $serviceLines = 0;
        foreach ($lines as $line) {
            if ($line->descripcion === $work2->descripcion) {
                $this->assertEquals($work2->idtrabajo, $line->idtrabajo, 'Error saving idtrabajo in work line');
                continue;
            }

            $serviceLines++;
            $this->assertNull($line->idtrabajo, 'The service line must not be linked to a work');
        }
        $this->assertEquals(1, $serviceLines, 'Service line not found');

        // eliminamos
        Tools::settingsSet('servicios', 'document_description', false);
        $this->assertTrue($generated[0]->delete());
        $this->assertTrue($service->delete());
        $this->assertTrue($customer->delete());
    }

    public function testUpdateOnDelete(): void
    {
        // creamos un cliente
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save());

        // creamos un servicio
        $service = new ServicioAT();
        $service->codalmacen = Tools::settings('default', 'codalmacen');
        $service->codcliente = $customer->codcliente;
        $service->descripcion = 'Test';
        $service->idempresa = Tools::settings('default', 'idempresa');
        $this->assertTrue($service->save());

        // creamos un trabajo
        $work = new TrabajoAT();
        $work->idservicio = $service->idservicio;
        $work->descripcion = 'Test work';
        $work->cantidad = 1;
        $work->precio = 10;
        $this->assertTrue($work->save(), 'Error creating TrabajoAT');

        // comprobamos que se ha actualizado el neto del servicio
        $service->load($service->id());
        $this->assertEquals(10, $service->neto);

        // eliminamos el trabajo
        $this->assertTrue($work->delete());

        // comprobamos que se ha actualizado el neto del servicio
        $service->load($service->id());
        $this->assertEquals(0, $service->neto);

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
        $stock->load($stock->id());
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
        $stock->load($stock->id());
        $this->assertEquals(7, $stock->cantidad);

        // eliminamos el trabajo 2
        $this->assertTrue($work2->delete(), 'Error deleting TrabajoAT with stock');

        // comprobamos que se ha sumado el stock
        $stock->load($stock->id());
        $this->assertEquals(10, $stock->cantidad);

        // desactivamos la opción de restar stock
        Tools::settingsSet('servicios', 'disablestockmanagement', true);

        //eliminamos el trabajo 1
        $this->assertTrue($work1->delete(), 'Error deleting TrabajoAT with stock');

        // comprobamos que no se ha restado el stock
        $stock->load($stock->id());
        $this->assertEquals(10, $stock->cantidad);

        // eliminamos
        $this->assertTrue($service->delete());
        $this->assertTrue($customer->delete());
        $this->assertTrue($product->delete());
    }

    public function testUpdateStockChangeReference(): void
    {
        // activamos la opción de restar stock
        Tools::settingsSet('servicios', 'disablestockmanagement', false);

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

        // creamos un producto que controla stock
        $product1 = $this->getRandomProduct();
        $product1->precio = 17;
        $product1->nostock = false;
        $product1->ventasinstock = false;
        $this->assertTrue($product1->save(), 'Error creating Producto 1');

        // creamos un producto que no controla stock
        $product2 = $this->getRandomProduct();
        $product2->precio = 5;
        $product2->nostock = true;
        $this->assertTrue($product2->save(), 'Error creating Producto 2');

        // añadimos stock al primer producto
        $stock = new Stock();
        $stock->referencia = $product1->referencia;
        $stock->codalmacen = Tools::settings('default', 'codalmacen');
        $stock->cantidad = 10;
        $this->assertTrue($stock->save(), 'Error creating Stock');

        // creamos un trabajo con el primer producto
        $work = new TrabajoAT();
        $work->idservicio = $service->idservicio;
        $work->referencia = $product1->referencia;
        $work->cantidad = 2;
        $work->estado = TrabajoAT::STATUS_MAKE_INVOICE;
        $this->assertTrue($work->save(), 'Error creating TrabajoAT with stock');

        // comprobamos que se ha restado el stock
        $stock->load($stock->id());
        $this->assertEquals(8, $stock->cantidad);

        // cambiamos al producto que no controla stock
        $work->referencia = $product2->referencia;
        $this->assertTrue($work->save(), 'Error changing TrabajoAT reference');

        // comprobamos que se ha devuelto el stock del primer producto
        $stock->load($stock->id());
        $this->assertEquals(10, $stock->cantidad);

        // comprobamos que no se ha creado stock del segundo producto
        $stock2 = new Stock();
        $this->assertFalse($stock2->loadWhereEq('referencia', $product2->referencia));

        // eliminamos
        $this->assertTrue($service->delete());
        $this->assertTrue($customer->delete());
        $this->assertTrue($product1->delete());
        $this->assertTrue($product2->delete());
    }

    public function testUpdateStockWithNullValues(): void
    {
        // activamos la opción de restar stock
        Tools::settingsSet('servicios', 'disablestockmanagement', false);

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

        // creamos un trabajo que resta stock
        $work = new TrabajoAT();
        $work->idservicio = $service->idservicio;
        $work->referencia = $product->referencia;
        $work->cantidad = 2;
        $work->estado = TrabajoAT::STATUS_MAKE_INVOICE;
        $this->assertTrue($work->save(), 'Error creating TrabajoAT with stock');

        // comprobamos que se ha restado el stock
        $stock->load($stock->id());
        $this->assertEquals(8, $stock->cantidad);

        // ponemos la cantidad y el estado a null directamente en la base de datos, como en los trabajos antiguos
        $db = new DataBase();
        $this->assertTrue($db->exec('UPDATE ' . TrabajoAT::tableName()
            . ' SET cantidad = NULL, estado = NULL WHERE idtrabajo = ' . $db->var2str($work->idtrabajo) . ';'));

        // recargamos el trabajo y comprobamos que tiene los valores a null
        $this->assertTrue($work->load($work->id()));
        $this->assertNull($work->cantidad);
        $this->assertNull($work->estado);

        // cambiamos la cantidad y el estado y guardamos
        $work->cantidad = 3;
        $work->estado = TrabajoAT::STATUS_MAKE_INVOICE;
        $this->assertTrue($work->save(), 'Error saving TrabajoAT with null values');

        // comprobamos que solo se ha restado la nueva cantidad, sin devolver la que era null
        $stock->load($stock->id());
        $this->assertEquals(5, $stock->cantidad);

        // dejamos la cantidad vacía, como al borrarla en el formulario
        $work->cantidad = null;
        $this->assertTrue($work->save(), 'Error saving TrabajoAT with empty quantity');
        $this->assertEquals(0, $work->cantidad);

        // comprobamos que se ha devuelto al stock la cantidad anterior
        $stock->load($stock->id());
        $this->assertEquals(8, $stock->cantidad);

        // volvemos a cargar el trabajo, cambiamos la cantidad y guardamos sin error
        $this->assertTrue($work->load($work->id()));
        $work->cantidad = 1;
        $this->assertTrue($work->save(), 'Error saving TrabajoAT after empty quantity');

        // comprobamos que se ha restado la nueva cantidad
        $stock->load($stock->id());
        $this->assertEquals(7, $stock->cantidad);

        // eliminamos
        $this->assertTrue($service->delete());
        $this->assertTrue($customer->delete());
        $this->assertTrue($product->delete());
    }

    public function testWorkIdKeptOnDocumentConversion(): void
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

        // generamos el presupuesto desde el servicio
        ServiceToInvoice::clear();
        $this->assertTrue(ServiceToInvoice::estimation($service), 'Error generating estimation');
        $docs = ServiceToInvoice::generated();
        $this->assertCount(1, $docs, 'Error generating estimation');

        // convertimos el presupuesto en pedido, el pedido en albarán y el albarán en factura,
        // comprobando que todas las líneas conservan el idtrabajo
        foreach (['PedidoCliente', 'AlbaranCliente', 'FacturaCliente'] as $newClass) {
            $prototype = end($docs);
            $generator = new BusinessDocumentGenerator();
            $this->assertTrue($generator->generate($prototype, $newClass), 'Error generating ' . $newClass);

            $newDocs = $generator->getLastDocs();
            $this->assertCount(1, $newDocs, 'Error generating ' . $newClass);
            $docs[] = $newDocs[0];

            $lines = $newDocs[0]->getLines();
            $this->assertCount(1, $lines, 'Error generating lines of ' . $newClass);
            $this->assertEquals($work->idtrabajo, $lines[0]->idtrabajo, 'Error keeping idtrabajo in ' . $newClass);
        }

        // eliminamos los documentos en orden inverso
        foreach (array_reverse($docs) as $doc) {
            $this->assertTrue($doc->delete(), 'Error deleting ' . $doc->modelClassName());
        }
        $this->assertTrue($service->delete());
        $this->assertTrue($customer->delete());
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
