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

namespace FacturaScripts\Plugins\Servicios\Extension\Controller;

use Closure;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\CodeModel;

/**
 * Añade la pestaña de servicios al panel del portal cliente (PortalCliente), sin modificar
 * ese plugin: se apoya en los pipe('createViews') y pipe('loadData') que ya dispara
 * PortalPanelController::commonCore(), igual que las extensiones de los controladores del core.
 *
 * Cada fila del listado enlaza con la ficha propia del servicio (Controller\PortalServicio),
 * a través de ServicioAT::url('public').
 *
 * @author Daniel Fernandez Giménez <contacto@danielfg.es>
 */
class PortalCliente
{
    public function createViews(): Closure
    {
        return function () {
            $statuses = CodeModel::all('serviciosat_estados', 'id', 'nombre');

            $this->addListView('ListPortalServicio', 'ServicioAT', 'services', 'fa-solid fa-screwdriver-wrench')
                ->addOrderBy(['fecha', 'hora'], 'date', 2)
                ->addOrderBy(['codigo'], 'code')
                ->addOrderBy(['neto'], 'total')
                ->addSearchFields(['codigo', 'descripcion'])
                ->addFilterPeriod('date', 'period', 'fecha')
                ->addFilterSelect('status', 'status', 'idestado', $statuses);

            $this->setSettings('ListPortalServicio', 'btnNew', false);
            $this->setSettings('ListPortalServicio', 'btnDelete', false);
            $this->setSettings('ListPortalServicio', 'checkBoxes', false);
        };
    }

    public function loadData(): Closure
    {
        return function (string $viewName, $view) {
            if ($viewName !== 'ListPortalServicio') {
                return;
            }

            // sin cliente asociado, o sin permiso, no hay nada que mostrar
            if (empty($this->contact->codcliente) || false === (bool)$this->contact->pc_allow_show_service) {
                $view->count = 0;
                $this->setSettings($viewName, 'active', false);
                return;
            }

            $where = [Where::eq('codcliente', $this->contact->codcliente)];
            $view->loadData('', $where);
            $this->setSettings($viewName, 'active', $view->count > 0 || $view->showFilters);
        };
    }
}
