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
use FacturaScripts\Plugins\Servicios\Model\TrabajoAT;

/**
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
class EditProducto
{
    public function createViews(): Closure
    {
        return function () {
            $this->createViewServiceWorks();
        };
    }

    public function loadData(): Closure
    {
        return function ($viewName, $view) {
            if ($viewName !== 'ListTrabajoAT') {
                return;
            }

            $variants = [];
            $references = [];
            $mvn = $this->getMainViewName();
            foreach ($this->views[$mvn]->model->getVariants() as $variant) {
                $references[] = $variant->referencia;
                $variants[] = [
                    'code' => $variant->referencia,
                    'description' => $variant->referencia,
                ];
            }

            $where = [new DataBaseWhere('serviciosat_trabajos.referencia', implode(',', $references), 'IN')];
            $view->loadData('', $where);
        };
    }

    protected function createViewServiceWorks(): Closure
    {
        return function (string $viewName = 'ListTrabajoAT') {
            $agents = $this->codeModel->all('agentes', 'codagente', 'nombre');
            $users = $this->codeModel->all('users', 'nick', 'nick');

            $variants = [];
            $product = $this->getModel();
            $product->loadFromCode($this->request->get('code'));
            foreach ($product->getVariants() as $variant) {
                $variants[] = [
                    'code' => $variant->referencia,
                    'description' => $variant->referencia,
                ];
            }

            $statuses = [];
            foreach (TrabajoAT::getAvailableStatus() as $key => $value) {
                $statuses[] = ['code' => $key, 'description' => $value];
            }

            $this->addListView($viewName, 'Join\TrabajoServicio', 'works', 'fa-solid fa-stethoscope')
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
                ->addFilterSelect('referencia', 'reference', 'serviciosat_trabajos.referencia', $variants)
                ->addFilterNumber('quantity-gt', 'quantity', 'serviciosat_trabajos.cantidad', '>=')
                ->addFilterNumber('quantity-lt', 'quantity', 'serviciosat_trabajos.cantidad', '<=')
                ->addFilterNumber('price-gt', 'price', 'serviciosat_trabajos.precio', '>=')
                ->addFilterNumber('price-lt', 'price', 'serviciosat_trabajos.precio', '<=')
                ->addFilterSelect('status', 'action', 'serviciosat_trabajos.estado', $statuses)
                ->setSettings('btnDelete', false)
                ->setSettings('btnNew', false)
                ->setSettings('checkBoxes', false);
        };
    }
}