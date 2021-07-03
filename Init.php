<?php
/**
 * This file is part of Servicios plugin for FacturaScripts
 * Copyright (C) 2020-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Plugins\Servicios;

use FacturaScripts\Core\Base\InitClass;
use FacturaScripts\Dinamic\Lib\ExportManager;
use FacturaScripts\Dinamic\Model\AlbaranCliente;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\PresupuestoCliente;

/**
 * Description of Init
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class Init extends InitClass
{

    public function init()
    {
        /// extensions
        $this->loadExtension(new Extension\Controller\EditCliente());

        /// export manager
        ExportManager::addOptionModel('PDFserviciosExport', 'PDF', 'ServicioAT');
    }

    public function update()
    {
        new Model\EstadoAT();
        new Model\MaquinaAT();
        new Model\PrioridadAT();
        new Model\ServicioAT();
        new AlbaranCliente();
        new FacturaCliente();
        new PresupuestoCliente();

        $this->setupSettings();
    }

    private function setupSettings()
    {
        $appsettings = $this->toolBox()->appSettings();
        $footerText = $appsettings->get('servicios', 'footertext', '');
        $appsettings->set('servicios', 'footertext', $footerText);
        $appsettings->save();
    }
}
