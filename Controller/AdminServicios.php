<?php
/**
 * This file is part of Servicios plugin for FacturaScripts
 * Copyright (C) 2021-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Dinamic\Lib\ExtendedController\PanelController;

/**
 * Description of AdminServicios
 *
 * @author Carlos Garcia Gomez              <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal    <yopli2000@gmail.com>
 */
class AdminServicios extends PanelController
{
    private const VIEW_CONFIG = 'ConfigServicios';
    private const VIEW_LIST_PRIORITIES = 'EditPrioridadAT';
    private const VIEW_LIST_TYPES = 'EditTipoAT';
    private const VIEW_LIST_STATUS = 'EditEstadoAT';

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'services';
        $data['icon'] = 'fa-solid fa-headset';
        return $data;
    }

    /**
     * Inserts the views or tabs to display.
     */
    protected function createViews()
    {
        $this->setTemplate('EditSettings');
        $this->createViewEditConfig();
        $this->createViewStatus();
        $this->createViewPriorities();
        $this->createViewTypes();
    }

    private function createViewEditConfig(string $viewName = self::VIEW_CONFIG): void
    {
        $this->addEditView($viewName, 'Settings', 'general')
            ->setSettings('btnDelete', false)
            ->setSettings('btnNew', false);
    }

    private function createViewPriorities(string $viewName = self::VIEW_LIST_PRIORITIES): void
    {
        $this->addEditListView($viewName, 'PrioridadAT', 'priority', 'fa-solid fa-list-ol')
            ->setInLine(true);
    }

    private function createViewTypes(string $viewName = self::VIEW_LIST_TYPES): void
    {
        $this->addEditListView($viewName, 'TipoAT', 'type', 'fa-solid fa-shapes')
            ->setInLine(true);
    }

    private function createViewStatus(string $viewName = self::VIEW_LIST_STATUS): void
    {
        $this->addEditListView($viewName, 'EstadoAT', 'states', 'fa-solid fa-tags')
            ->setInLine(true);
    }

    /**
     * Loads the data to display.
     *
     * @param string $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case self::VIEW_CONFIG:
                $view->loadData('servicios');
                $view->model->name = 'servicios';
                break;

            case self::VIEW_LIST_PRIORITIES:
            case self::VIEW_LIST_TYPES:
            case self::VIEW_LIST_STATUS:
                $view->loadData('', [], ['id' => 'DESC']);
                break;
        }
    }
}
