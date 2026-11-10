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

use FacturaScripts\Core\Plugins;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Page;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

/**
 * Tests del controlador condicional ReportServicioAT, que solo debe desplegarse
 * cuando el plugin Informes está activado.
 *
 * @author Esteban Sánchez Martínez <esteban@factura.city>
 */
final class ReportServicioAtTest extends TestCase
{
    use LogErrorsTrait;

    /**
     * Comprueba que el controlador vive en la carpeta condicional Controller/Informes
     * y ya no en la carpeta Controller del plugin.
     */
    public function testControllerInConditionalFolder(): void
    {
        $this->assertTrue(
            class_exists('FacturaScripts\\Plugins\\Servicios\\Controller\\Informes\\ReportServicioAT'),
            'Error: no se encuentra el controlador en Controller/Informes'
        );
        $this->assertFileDoesNotExist(
            Tools::folder('Plugins', 'Servicios', 'Controller', 'ReportServicioAT.php'),
            'Error: el controlador sigue en la carpeta Controller del plugin'
        );
    }

    /**
     * Comprueba que el controlador y su página solo se despliegan si el plugin Informes está activado.
     */
    public function testControllerDeployedOnlyWithInformes(): void
    {
        $informesEnabled = Plugins::isEnabled('Informes');

        // el controlador solo debe estar en Dinamic/Controller con Informes activado
        $this->assertSame(
            $informesEnabled,
            file_exists(Tools::folder('Dinamic', 'Controller', 'ReportServicioAT.php')),
            'Error: el despliegue del controlador no coincide con el estado del plugin Informes'
        );

        // y lo mismo con la página del menú
        $page = new Page();
        $this->assertSame(
            $informesEnabled,
            $page->load('ReportServicioAT'),
            'Error: la página del informe no coincide con el estado del plugin Informes'
        );
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
