<?php
/**
 * This file is part of Servicios plugin for FacturaScripts
 * Copyright (C) 2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

/**
 * Description of EditCliente
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class EditCliente
{

    protected function createViews()
    {
        return function() {
            $this->createViewsEquipements();
            $this->createViewsServices();
        };
    }

    protected function createViewsEquipements()
    {
        return function($viewName = 'ListEquipoServicioAT') {
            $this->addListView($viewName, 'EquipoServicioAT', 'equipments', 'fas fa-laptop-medical');
            $this->views[$viewName]->addOrderBy(['idequipo'], 'code', 2);
            $this->views[$viewName]->addOrderBy(['fecha'], 'date');
            $this->views[$viewName]->addOrderBy(['nombre'], 'name');
            $this->views[$viewName]->addOrderBy(['referencia'], 'reference');
            $this->views[$viewName]->searchFields = ['idequipo', 'nombre', 'numserie', 'referencia'];

            /// disable customer column
            $this->views[$viewName]->disableColumn('customer');
        };
    }

    protected function createViewsServices()
    {
        return function($viewName = 'ListServicioAT') {
            $this->addListView($viewName, 'ServicioAT', 'services', 'fas fa-headset');
            $this->views[$viewName]->addOrderBy(['fecha', 'hora'], 'date', 2);
            $this->views[$viewName]->addOrderBy(['prioridad'], 'priority');
            $this->views[$viewName]->addOrderBy(['idservicio'], 'code');
            $this->views[$viewName]->searchFields = ['descripcion', 'idservicio', 'observaciones'];

            /// disable customer column
            $this->views[$viewName]->disableColumn('customer');
        };
    }

    protected function loadData()
    {
        return function($viewName, $view) {
            switch ($viewName) {
                case 'ListEquipoServicioAT':
                case 'ListServicioAT':
                    $codcliente = $this->getViewModelValue($this->getMainViewName(), 'codcliente');
                    $where = [new DataBaseWhere('codcliente', $codcliente)];
                    $view->loadData('', $where);
                    break;
            }
        };
    }
}
