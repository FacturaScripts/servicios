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
namespace FacturaScripts\Plugins\Servicios\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\EditController;

/**
 * Description of EditMaquinaAT
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class EditMaquinaAT extends EditController
{

    /**
     * 
     * @return string
     */
    public function getModelClassName()
    {
        return 'MaquinaAT';
    }

    /**
     * 
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'sales';
        $data['title'] = 'machine';
        $data['icon'] = 'fas fa-laptop-medical';
        $data['showonmenu'] = false;

        return $data;
    }

    protected function createViews()
    {
        parent::createViews();
        $this->setTabsPosition('bottom');

        $this->createViewsServices();
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createViewsServices(string $viewName = 'ListServicioAT')
    {
        $this->addListView($viewName, 'ServicioAT', 'services', 'fas fa-headset');
        $this->views[$viewName]->addOrderBy(['fecha', 'hora'], 'date', 2);
        $this->views[$viewName]->addOrderBy(['prioridad'], 'priority');
        $this->views[$viewName]->addOrderBy(['idservicio'], 'code');
        $this->views[$viewName]->searchFields = ['descripcion', 'idservicio', 'observaciones'];

        /// disable customer column
        $this->views[$viewName]->disableColumn('machine');
    }

    /**
     * 
     * @param string   $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        $mainViewName = $this->getMainViewName();

        switch ($viewName) {
            case 'ListServicioAT':
                $idmaquina = $this->getViewModelValue($mainViewName, 'idmaquina');
                $where = [
                    new DataBaseWhere('idmaquina', $idmaquina),
                    new DataBaseWhere('idmaquina2', $idmaquina, '=', 'OR'),
                    new DataBaseWhere('idmaquina3', $idmaquina, '=', 'OR'),
                    new DataBaseWhere('idmaquina4', $idmaquina, '=', 'OR')
                ];
                $view->loadData('', $where);
                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }
}
