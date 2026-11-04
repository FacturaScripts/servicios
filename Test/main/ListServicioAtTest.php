<?php
/**
 * This file is part of Servicios plugin for FacturaScripts
 * Copyright (C) 2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Lib\ExtendedController\ListView;
use FacturaScripts\Dinamic\Model\EstadoAT;
use FacturaScripts\Dinamic\Model\ServicioAT;
use FacturaScripts\Plugins\Servicios\Controller\ListServicioAT;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Tests del controlador ListServicioAT.
 *
 * @author Daniel Fernández Giménez <contacto@danielfg.es>
 */
final class ListServicioAtTest extends TestCase
{
    use LogErrorsTrait;

    /**
     * Comprueba que asignar los colores de estado no falla cuando la vista no tiene fila 'status',
     * como ocurre cuando otro plugin cambia o reemplaza el XMLView del listado.
     */
    public function testServicesColorsWithoutStatusRow(): void
    {
        // creamos un estado con color
        $status = $this->createStatusWithColor();

        // creamos una vista sin cargar su XMLView, así que no tiene fila 'status'
        $view = new ListView('ListServicioAT', 'services', ServicioAT::class, 'fa-solid fa-headset');
        $this->assertNull($view->getRow('status'));

        // asignar los colores no debe lanzar ningún error
        $this->callSetServicesColors($view);
        $this->assertNull($view->getRow('status'));

        // eliminamos
        $this->assertTrue($status->delete());
    }

    /**
     * Comprueba que, con la vista normal del listado, se añade una opción de color por el estado con color.
     */
    public function testServicesColorsWithStatusRow(): void
    {
        // creamos un estado con color
        $status = $this->createStatusWithColor();

        // creamos la vista y cargamos su XMLView, que tiene fila 'status'
        $view = new ListView('ListServicioAT', 'services', ServicioAT::class, 'fa-solid fa-headset');
        $view->loadPageOptions();
        $row = $view->getRow('status');
        $this->assertNotNull($row);
        $before = count($row->options);

        // asignamos los colores y comprobamos que está la opción del estado
        $this->callSetServicesColors($view);
        $this->assertGreaterThan($before, count($row->options));

        $found = array_filter($row->options, function ($option) use ($status) {
            return $option['text'] == $status->id && $option['color'] === $status->color;
        });
        $this->assertCount(1, $found, 'Error: no se ha añadido el color del estado');

        // eliminamos
        $this->assertTrue($status->delete());
    }

    /**
     * Ejecuta el método protegido setServicesColors() de ListServicioAT sobre la vista indicada.
     *
     * @param ListView $view
     */
    private function callSetServicesColors(ListView $view): void
    {
        // instanciamos el controlador sin constructor para no ejecutar el ciclo completo de la petición
        $class = new ReflectionClass(ListServicioAT::class);
        $controller = $class->newInstanceWithoutConstructor();
        $controller->views[$view->getViewName()] = $view;

        $method = $class->getMethod('setServicesColors');
        $method->setAccessible(true);
        $method->invoke($controller, $view->getViewName());
    }

    /**
     * Crea y guarda un estado de servicio con color.
     *
     * @return EstadoAT
     */
    private function createStatusWithColor(): EstadoAT
    {
        $status = new EstadoAT();
        $status->nombre = 'Test state color';
        $status->color = 'danger';
        $this->assertTrue($status->save());

        return $status;
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
