<?php
/**
 * Copyright (C) 2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 */

namespace FacturaScripts\Test\Plugins;

use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\MovimientoStock;
use FacturaScripts\Dinamic\Model\Producto;
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

    public function testUpdateStockWhenStockManagementEnabled(): void
    {
        Tools::settingsSet('servicios', 'disablestockmanagement', false);

        [$customer, $service, $product, $stock] = $this->createServiceWithStock();

        // creamos un trabajo
        $work = new TrabajoAT();
        $work->idservicio = $service->idservicio;
        $work->referencia = $product->referencia;
        $work->cantidad = 3;
        $work->estado = TrabajoAT::STATUS_MAKE_INVOICE;
        $this->assertTrue($work->save(), 'Error creating TrabajoAT with stock');

        // comprobamos que se ha restado el stock
        $stock->load($stock->id());
        $this->assertEquals(7, $stock->cantidad);

        // comprobamos que hay un movimiento del trabajo
        $movement = new MovimientoStock();
        $where = [
            Where::eq('referencia', $product->referencia),
            Where::eq('codalmacen', $service->codalmacen),
            Where::eq('docmodel', $work->modelClassName()),
            Where::eq('docid', $work->id())
        ];
        $this->assertTrue($movement->loadWhere($where), 'No stock movement found for TrabajoAT');

        // eliminamos el trabajo
        $this->assertTrue($work->delete(), 'Error deleting TrabajoAT with stock');

        // comprobamos que ya no existe el movimiento del trabajo
        $this->assertFalse($movement->loadWhere($where), 'Stock movement for TrabajoAT still exists after deletion');

        // comprobamos que se ha devuelto el stock
        $stock->load($stock->id());
        $this->assertEquals(10, $stock->cantidad);

        $this->cleanUp($customer, $service, $product, $stock);
    }

    public function testUpdateStockWhenStockManagementDisabled(): void
    {
        Tools::settingsSet('servicios', 'disablestockmanagement', true);

        [$customer, $service, $product, $stock] = $this->createServiceWithStock();

        // creamos un trabajo
        $work = new TrabajoAT();
        $work->idservicio = $service->idservicio;
        $work->referencia = $product->referencia;
        $work->cantidad = 2;
        $work->estado = TrabajoAT::STATUS_MAKE_INVOICE;
        $this->assertTrue($work->save(), 'Error creating TrabajoAT with stock');

        // comprobamos que NO se ha restado el stock
        $stock->load($stock->id());
        $this->assertEquals(10, $stock->cantidad);

        // comprobamos que NO hay movimiento del trabajo, porque la gestión de stock está desactivada
        $movement = new MovimientoStock();
        $where = [
            Where::eq('referencia', $product->referencia),
            Where::eq('codalmacen', $service->codalmacen),
            Where::eq('docmodel', $work->modelClassName()),
            Where::eq('docid', $work->id())
        ];
        $this->assertFalse($movement->loadWhere($where), 'Stock movement found for TrabajoAT with stock management disabled');

        // eliminamos el trabajo
        $this->assertTrue($work->delete(), 'Error deleting TrabajoAT with stock');

        // seguimos sin encontrar movimiento del trabajo (nunca se llegó a crear)
        $this->assertFalse($movement->loadWhere($where), 'Stock movement for TrabajoAT still exists after deletion');

        // comprobamos que el stock sigue igual
        $stock->load($stock->id());
        $this->assertEquals(10, $stock->cantidad);

        $this->cleanUp($customer, $service, $product, $stock);
    }

    protected function createServiceWithStock(): array
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
        $product->nostock = false;
        $product->ventasinstock = false;
        $this->assertTrue($product->save(), 'Error creating Producto');

        // añadimos stock
        $stock = new Stock();
        $stock->referencia = $product->referencia;
        $stock->codalmacen = Tools::settings('default', 'codalmacen');
        $stock->cantidad = 10;
        $this->assertTrue($stock->save(), 'Error creating Stock');

        return [$customer, $service, $product, $stock];
    }

    protected function cleanUp(Cliente $customer, ServicioAT $service, Producto $product, Stock $stock): void
    {
        $this->assertTrue($service->delete());
        $this->assertTrue($customer->delete());

        $stock->reload();
        if ($stock->exists()) {
            $this->assertTrue($stock->delete());
        }

        $this->assertTrue($product->delete());
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
