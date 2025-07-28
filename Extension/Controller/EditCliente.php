<?php
/**
 * This file is part of Servicios plugin for FacturaScripts
 * Copyright (C) 2020-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Plugins\Servicios\Model\EstadoAT;

/**
 * Description of EditCliente
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class EditCliente
{
    protected function createViews(): Closure
    {
        return function () {
            $this->createViewsMachines();
            $this->createViewsServices();
        };
    }

    protected function createViewsMachines(): Closure
    {
        return function ($viewName = 'ListMaquinaAT') {
            $manufacturers = $this->codeModel->all('fabricantes', 'codfabricante', 'nombre');
            $agents = $this->codeModel->all('agentes', 'codagente', 'nombre');

            $this->addListView($viewName, 'MaquinaAT', 'machines', 'fa-solid fa-laptop-medical')
                ->addOrderBy(['idmaquina'], 'code', 2)
                ->addOrderBy(['fecha'], 'date')
                ->addOrderBy(['nombre'], 'name')
                ->addOrderBy(['referencia'], 'reference')
                ->addSearchFields(['descripcion', 'idmaquina', 'nombre', 'numserie', 'referencia'])
                ->addFilterPeriod('fecha', 'date', 'fecha')
                ->addFilterSelect('codfabricante', 'manufacturer', 'codfabricante', $manufacturers)
                ->addFilterAutocomplete('codcliente', 'customer', 'codcliente', 'clientes', 'codcliente', 'nombre')
                ->addFilterSelect('codagente', 'agent', 'codagente', $agents)
                ->disableColumn('customer');
        };
    }

    protected function createViewsServices(): Closure
    {
        return function ($viewName = 'ListServicioAT') {
            $agents = $this->codeModel->all('agentes', 'codagente', 'nombre');
            $users = $this->codeModel->all('users', 'nick', 'nick');
            $priority = $this->codeModel->all('serviciosat_prioridades', 'id', 'nombre');
            $status = $this->codeModel->all('serviciosat_estados', 'id', 'nombre');

            $this->addListView($viewName, 'ServicioAT', 'services', 'fa-solid fa-headset')
                ->addOrderBy(['fecha', 'hora'], 'date', 2)
                ->addOrderBy(['idprioridad'], 'priority')
                ->addOrderBy(['idservicio'], 'code')
                ->addOrderBy(['neto'], 'net')
                ->addSearchFields(['codigo', 'descripcion', 'idservicio', 'material', 'observaciones', 'solucion', 'telefono1', 'telefono2'])
                ->addFilterPeriod('fecha', 'date', 'fecha')
                ->addFilterAutocomplete('codcliente', 'customer', 'codcliente', 'clientes', 'codcliente', 'nombre')
                ->addFilterSelect('idprioridad', 'priority', 'idprioridad', $priority)
                ->addFilterSelect('idestado', 'status', 'idestado', $status)
                ->addFilterSelect('nick', 'user', 'nick', $users)
                ->addFilterSelect('asignado', 'assigned', 'asignado', $users)
                ->addFilterSelect('codagente', 'agent', 'codagente', $agents)
                ->addFilterNumber('netogt', 'net', 'neto', '>=')
                ->addFilterNumber('netolt', 'net', 'neto', '<=')
                ->disableColumn('customer');

            $this->setServicesColors($viewName);
        };
    }

    protected function loadData(): Closure
    {
        return function ($viewName, $view) {
            switch ($viewName) {
                case 'ListMaquinaAT':
                case 'ListServicioAT':
                    $codcliente = $this->getViewModelValue($this->getMainViewName(), 'codcliente');
                    $where = [new DataBaseWhere('codcliente', $codcliente)];
                    $view->loadData('', $where);
                    break;
            }
        };
    }

    protected function setServicesColors(): Closure
    {
        return function (string $viewName) {
            // asignamos colores
            foreach (EstadoAT::all([], [], 0, 0) as $estado) {
                if (empty($estado->color)) {
                    continue;
                }

                $this->views[$viewName]->getRow('status')->options[] = [
                    'tag' => 'option',
                    'children' => [],
                    'color' => $estado->color,
                    'fieldname' => 'idestado',
                    'text' => $estado->id,
                    'title' => $estado->nombre
                ];
            }
        };
    }
}
