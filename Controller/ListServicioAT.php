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

namespace FacturaScripts\Plugins\Servicios\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController\ListController;
use FacturaScripts\Core\Tools;
use FacturaScripts\Plugins\Servicios\Model\EstadoAT;

/**
 * Description of ListServicioAT
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class ListServicioAT extends ListController
{
    /** @var EstadoAT[] */
    protected $serviceStatus;

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'sales';
        $data['title'] = 'services';
        $data['icon'] = 'fas fa-headset';
        return $data;
    }

    protected function createViews()
    {
        $this->createViewsServices();
        $this->createViewsServicesClosed();
        $this->createViewsWorks();
        $this->createViewsMachines();
    }

    protected function createViewsMachines(string $viewName = 'ListMaquinaAT'): void
    {
        $manufacturers = $this->codeModel->all('fabricantes', 'codfabricante', 'nombre');
        $agents = $this->codeModel->all('agentes', 'codagente', 'nombre');

        $this->addView($viewName, 'MaquinaAT', 'machines', 'fas fa-laptop-medical')
            ->addOrderBy(['idmaquina'], 'code', 2)
            ->addOrderBy(['fecha'], 'date')
            ->addOrderBy(['nombre'], 'name')
            ->addOrderBy(['referencia'], 'reference')
            ->addSearchFields(['descripcion', 'idmaquina', 'nombre', 'numserie', 'referencia'])
            ->addFilterPeriod('fecha', 'date', 'fecha')
            ->addFilterSelect('codfabricante', 'manufacturer', 'codfabricante', $manufacturers)
            ->addFilterAutocomplete('codcliente', 'customer', 'codcliente', 'clientes', 'codcliente', 'nombre')
            ->addFilterSelect('codagente', 'agent', 'codagente', $agents);
    }

    protected function createViewsServices(string $viewName = 'ListServicioAT'): void
    {
        $agents = $this->codeModel->all('agentes', 'codagente', 'nombre');
        $users = $this->codeModel->all('users', 'nick', 'nick');
        $type = $this->codeModel->all('serviciosat_tipos', 'id', 'tipo');
        $priority = $this->codeModel->all('serviciosat_prioridades', 'id', 'nombre');

        // obtenemos los estados editables
        $valuesWhere = [
            ['label' => Tools::lang()->trans('only-active'), 'where' => [new DataBaseWhere('editable', true)]],
            ['label' => '------', 'where' => [new DataBaseWhere('editable', true)]],
        ];
        foreach ($this->getServiceStatus() as $estado) {
            if ($estado->editable) {
                $valuesWhere[] = [
                    'label' => $estado->nombre,
                    'where' => [new DataBaseWhere('idestado', $estado->id)]
                ];
            }
        }

        $this->addView($viewName, 'ServicioAT', 'services', 'fas fa-headset')
            ->addOrderBy(['fecha', 'hora'], 'date', 1)
            ->addOrderBy(['idprioridad'], 'priority')
            ->addOrderBy(['idservicio'], 'code')
            ->addOrderBy(['neto'], 'net')
            ->addSearchFields(['codigo', 'descripcion', 'idservicio', 'material', 'observaciones', 'solucion', 'telefono1', 'telefono2'])
            ->addFilterPeriod('fecha', 'date', 'fecha')
            ->addFilterAutocomplete('codcliente', 'customer', 'codcliente', 'clientes', 'codcliente', 'nombre')
            ->addFilterSelect('idprioridad', 'priority', 'idprioridad', $priority)
            ->addFilterSelectWhere('status', $valuesWhere)
            ->addFilterSelect('nick', 'user', 'nick', $users)
            ->addFilterSelect('asignado', 'assigned', 'asignado', $users)
            ->addFilterSelect('idtipo', 'type', 'idtipo', $type)
            ->addFilterSelect('codagente', 'agent', 'codagente', $agents)
            ->addFilterNumber('netogt', 'net', 'neto', '>=')
            ->addFilterNumber('netolt', 'net', 'neto', '<=');

        $this->setServicesColors($viewName);
    }

    protected function createViewsServicesClosed(string $viewName = 'ListServicioAT-closed'): void
    {
        $agents = $this->codeModel->all('agentes', 'codagente', 'nombre');
        $users = $this->codeModel->all('users', 'nick', 'nick');
        $priority = $this->codeModel->all('serviciosat_prioridades', 'id', 'nombre');

        // obtenemos los estados no editables
        $valuesWhere = [
            ['label' => Tools::lang()->trans('only-closed'), 'where' => [new DataBaseWhere('editable', false)]],
            ['label' => '------', 'where' => [new DataBaseWhere('editable', false)]],
        ];
        foreach ($this->getServiceStatus() as $estado) {
            if (false === $estado->editable) {
                $valuesWhere[] = [
                    'label' => $estado->nombre,
                    'where' => [new DataBaseWhere('idestado', $estado->id)]
                ];
            }
        }

        $this->addView($viewName, 'ServicioAT', 'closed', 'fas fa-lock')
            ->addOrderBy(['fecha', 'hora'], 'date', 1)
            ->addOrderBy(['idprioridad'], 'priority')
            ->addOrderBy(['idservicio'], 'code')
            ->addOrderBy(['neto'], 'net')
            ->addSearchFields(['codigo', 'descripcion', 'idservicio', 'material', 'observaciones', 'solucion', 'telefono1', 'telefono2'])
            ->addFilterPeriod('fecha', 'date', 'fecha')
            ->addFilterAutocomplete('codcliente', 'customer', 'codcliente', 'clientes', 'codcliente', 'nombre')
            ->addFilterSelect('idprioridad', 'priority', 'idprioridad', $priority)
            ->addFilterSelectWhere('status', $valuesWhere)
            ->addFilterSelect('nick', 'user', 'nick', $users)
            ->addFilterSelect('asignado', 'assigned', 'asignado', $users)
            ->addFilterSelect('codagente', 'agent', 'codagente', $agents)
            ->addFilterNumber('netogt', 'net', 'neto', '>=')
            ->addFilterNumber('netolt', 'net', 'neto', '<=');

        $this->setServicesColors($viewName);
    }

    protected function createViewsWorks(string $viewName = 'ListTrabajoAT'): void
    {
        $agents = $this->codeModel->all('agentes', 'codagente', 'nombre');
        $users = $this->codeModel->all('users', 'nick', 'nick');

        $this->addView($viewName, 'TrabajoAT', 'work', 'fas fa-stethoscope')
            ->addOrderBy(['fechainicio', 'horainicio'], 'from-date')
            ->addOrderBy(['fechafin', 'horafin'], 'until-date', 2)
            ->addOrderBy(['idservicio', 'idtrabajo'], 'service')
            ->addSearchFields(['descripcion', 'observaciones', 'referencia'])
            ->addFilterSelect('nick', 'user', 'nick', $users)
            ->addFilterSelect('codagente', 'agent', 'codagente', $agents)
            ->addFilterPeriod('fechainicio', 'start-date', 'fechainicio')
            ->addFilterPeriod('fechafin', 'end-date', 'fechafin')
            ->setSettings('btnDelete', false)
            ->setSettings('btnNew', false);
    }

    protected function getServiceStatus(): array
    {
        if (empty($this->serviceStatus)) {
            $estadoModel = new EstadoAT();
            $this->serviceStatus = $estadoModel->all([], [], 0, 0);
        }

        return $this->serviceStatus;
    }

    protected function setServicesColors(string $viewName): void
    {
        // asignamos colores
        foreach ($this->getServiceStatus() as $estado) {
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
    }
}
