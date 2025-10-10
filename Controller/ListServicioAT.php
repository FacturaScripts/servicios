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

use FacturaScripts\Core\DataSrc\Almacenes;
use FacturaScripts\Core\Lib\ExtendedController\ListController;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\EstadoAT;
use FacturaScripts\Dinamic\Model\TrabajoAT;

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
        $data['icon'] = 'fa-solid fa-headset';
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

        $this->addView($viewName, 'MaquinaAT', 'machines', 'fa-solid fa-laptop-medical')
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
        $types = $this->codeModel->all('serviciosat_tipos', 'id', 'name');
        $priority = $this->codeModel->all('serviciosat_prioridades', 'id', 'nombre');
        $warehouses = Almacenes::codeModel();

        // obtenemos los estados editables
        $valuesWhere = [
            ['label' => Tools::trans('only-active'), 'where' => [Where::column('editable', true)]],
            ['label' => '------', 'where' => [Where::column('editable', true)]],
        ];
        foreach ($this->getServiceStatus() as $estado) {
            if ($estado->editable) {
                $valuesWhere[] = [
                    'label' => $estado->nombre,
                    'where' => [Where::column('idestado', $estado->id)]
                ];
            }
        }

        $this->addView($viewName, 'ServicioAT', 'services', 'fa-solid fa-headset')
            ->addOrderBy(['fecha', 'hora'], 'date', 2)
            ->addOrderBy(['idprioridad'], 'priority')
            ->addOrderBy(['idservicio'], 'code')
            ->addOrderBy(['neto'], 'net')
            ->addSearchFields(['codigo', 'descripcion', 'idservicio', 'material', 'observaciones', 'solucion', 'telefono1', 'telefono2'])
            ->addFilterPeriod('fecha', 'date', 'fecha')
            ->addFilterSelect('codalmacen', 'warehouse', 'codalmacen', $warehouses)
            ->addFilterSelect('idtipo', 'type', 'idtipo', $types)
            ->addFilterSelectWhere('status', $valuesWhere)
            ->addFilterAutocomplete('codcliente', 'customer', 'codcliente', 'clientes', 'codcliente', 'nombre')
            ->addFilterSelect('nick', 'user', 'nick', $users)
            ->addFilterSelect('asignado', 'assigned', 'asignado', $users)
            ->addFilterSelect('codagente', 'agent', 'codagente', $agents)
            ->addFilterNumber('netogt', 'net', 'neto', '>=')
            ->addFilterNumber('netolt', 'net', 'neto', '<=')
            ->addFilterSelect('idprioridad', 'priority', 'idprioridad', $priority);

        $this->setServicesColors($viewName);
    }

    protected function createViewsServicesClosed(string $viewName = 'ListServicioAT-closed'): void
    {
        $agents = $this->codeModel->all('agentes', 'codagente', 'nombre');
        $users = $this->codeModel->all('users', 'nick', 'nick');
        $priority = $this->codeModel->all('serviciosat_prioridades', 'id', 'nombre');
        $types = $this->codeModel->all('serviciosat_tipos', 'id', 'name');
        $warehouses = Almacenes::codeModel();

        // obtenemos los estados no editables
        $valuesWhere = [
            ['label' => Tools::trans('only-closed'), 'where' => [Where::column('editable', false)]],
            ['label' => '------', 'where' => [Where::column('editable', false)]],
        ];
        foreach ($this->getServiceStatus() as $estado) {
            if (false === $estado->editable) {
                $valuesWhere[] = [
                    'label' => $estado->nombre,
                    'where' => [Where::column('idestado', $estado->id)]
                ];
            }
        }

        $this->addView($viewName, 'ServicioAT', 'closed', 'fa-solid fa-lock')
            ->addOrderBy(['fecha', 'hora'], 'date', 2)
            ->addOrderBy(['idprioridad'], 'priority')
            ->addOrderBy(['idservicio'], 'code')
            ->addOrderBy(['neto'], 'net')
            ->addSearchFields(['codigo', 'descripcion', 'idservicio', 'material', 'observaciones', 'solucion', 'telefono1', 'telefono2'])
            ->addFilterPeriod('fecha', 'date', 'fecha')
            ->addFilterSelect('codalmacen', 'warehouse', 'codalmacen', $warehouses)
            ->addFilterSelect('idtipo', 'type', 'idtipo', $types)
            ->addFilterSelectWhere('status', $valuesWhere)
            ->addFilterAutocomplete('codcliente', 'customer', 'codcliente', 'clientes', 'codcliente', 'nombre')
            ->addFilterSelect('nick', 'user', 'nick', $users)
            ->addFilterSelect('asignado', 'assigned', 'asignado', $users)
            ->addFilterSelect('codagente', 'agent', 'codagente', $agents)
            ->addFilterNumber('netogt', 'net', 'neto', '>=')
            ->addFilterNumber('netolt', 'net', 'neto', '<=')
            ->addFilterSelect('idprioridad', 'priority', 'idprioridad', $priority);

        $this->setServicesColors($viewName);
    }

    protected function createViewsWorks(string $viewName = 'ListTrabajoAT'): void
    {
        $agents = $this->codeModel->all('agentes', 'codagente', 'nombre');
        $users = $this->codeModel->all('users', 'nick', 'nick');

        $statuses = [];
        foreach (TrabajoAT::getAvailableStatus() as $key => $value) {
            $statuses[] = ['code' => $key, 'description' => $value];
        }

        $this->addView($viewName, 'Join\TrabajoServicio', 'work', 'fa-solid fa-stethoscope')
            ->addOrderBy(['serviciosat_trabajos.fechainicio', 'serviciosat_trabajos.horainicio'], 'from-date')
            ->addOrderBy(['serviciosat_trabajos.fechafin', 'serviciosat_trabajos.horafin'], 'until-date')
            ->addOrderBy(['serviciosat_trabajos.idservicio', 'serviciosat_trabajos.idtrabajo'], 'service', 2)
            ->addOrderBy(['serviciosat_trabajos.cantidad'], 'quantity')
            ->addOrderBy(['serviciosat_trabajos.precio'], 'price')
            ->addSearchFields(['serviciosat.codigo', 'serviciosat_trabajos.descripcion', 'serviciosat_trabajos.observaciones', 'serviciosat_trabajos.referencia'])
            ->addFilterDatePicker('fechainicio', 'from-date', 'serviciosat_trabajos.fechainicio', '>=')
            ->addFilterDatePicker('fechafin', 'until-date', 'serviciosat_trabajos.fechafin', '<=')
            ->addFilterSelect('nick', 'user', 'serviciosat_trabajos.nick', $users)
            ->addFilterSelect('codagente', 'agent', 'serviciosat_trabajos.codagente', $agents)
            ->addFilterAutocomplete('codcliente', 'customer', 'serviciosat.codcliente', 'clientes', 'codcliente', 'nombre')
            ->addFilterAutocomplete('referencia', 'reference', 'serviciosat_trabajos.referencia', 'variantes', 'referencia', 'referencia')
            ->addFilterNumber('quantity-gt', 'quantity', 'serviciosat_trabajos.cantidad', '>=')
            ->addFilterNumber('quantity-lt', 'quantity', 'serviciosat_trabajos.cantidad', '<=')
            ->addFilterNumber('price-gt', 'price', 'serviciosat_trabajos.precio', '>=')
            ->addFilterNumber('price-lt', 'price', 'serviciosat_trabajos.precio', '<=')
            ->addFilterSelect('status', 'action', 'serviciosat_trabajos.estado', $statuses)
            ->setSettings('btnDelete', false)
            ->setSettings('btnNew', false)
            ->setSettings('checkBoxes', false);
    }

    protected function getServiceStatus(): array
    {
        if (empty($this->serviceStatus)) {
            $this->serviceStatus = EstadoAT::all();
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
