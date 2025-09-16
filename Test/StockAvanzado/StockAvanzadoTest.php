<?php
/**
 * Copyright (C) 2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 */

namespace FacturaScripts\Test\Plugins;

use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\MovimientoStock;
use FacturaScripts\Dinamic\Model\ServicioAT;
use FacturaScripts\Dinamic\Model\Stock;
use FacturaScripts\Dinamic\Model\TrabajoAT;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class StockAvanzadoTest extends TestCase
{
    use LogErrorsTrait;
    use RandomDataTrait;

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

        // comprobamos que hay un movimiento del trabajo
        $movements1 = new MovimientoStock();
        $where1 = [
            Where::column('referencia', $product->referencia),
            Where::column('codalmacen', $service->codalmacen),
            Where::column('docmodel', $work1->modelClassName()),
            Where::column('docid', $work1->id())
        ];
        $this->assertTrue($movements1->loadWhere($where1), 'No stock movement found for TrabajoAT');

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

        // comprobamos que hay un movimiento del trabajo
        $movements2 = new MovimientoStock();
        $where2 = [
            Where::column('referencia', $product->referencia),
            Where::column('codalmacen', $service->codalmacen),
            Where::column('docmodel', $work2->modelClassName()),
            Where::column('docid', $work2->id())
        ];
        $this->assertTrue($movements2->loadWhere($where2), 'No stock movement found for TrabajoAT');

        // eliminamos el trabajo 2
        $this->assertTrue($work2->delete(), 'Error deleting TrabajoAT with stock');

        // comprobamos que no existe el movimiento del trabajo 2
        $movements2->reload();
        $this->assertFalse($movements2->loadWhere($where2), 'Stock movement for TrabajoAT still exists after deletion');

        // comprobamos que se ha sumado el stock
        $stock->load($stock->id());
        $this->assertEquals(10, $stock->cantidad);

        // desactivamos la opción de restar stock
        Tools::settingsSet('servicios', 'disablestockmanagement', true);

        //eliminamos el trabajo 1
        $this->assertTrue($work1->delete(), 'Error deleting TrabajoAT with stock');

        // comprobamos que no existe el movimiento del trabajo 1
        $movements1->reload();
        $this->assertFalse($movements1->loadWhere($where1), 'Stock movement for TrabajoAT still exists after deletion');

        // comprobamos que no se ha restado el stock
        $stock->load($stock->id());
        $this->assertEquals(10, $stock->cantidad);

        // eliminamos
        $this->assertTrue($service->delete());
        $this->assertTrue($customer->delete());
        $this->assertTrue($product->delete());
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}