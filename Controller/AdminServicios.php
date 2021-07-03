<?php
/**
 * This file is part of Servicios plugin for FacturaScripts
 * Copyright (C) 2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Dinamic\Lib\ExtendedController\PanelController;

/**
 * Description of AdminServicios
 *
 * @author Carlos Garcia Gomez              <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal    <yopli2000@gmail.com>
 */
class AdminServicios extends PanelController
{

    private const VIEW_CONFIG_PROJECTS = 'ConfigServicios';
    private const VIEW_LIST_PRIORITIES = 'EditPrioridadAT';
    private const VIEW_LIST_STATUS = 'EditEstadoAT';

    /**
     * Return the basic data for this page.
     *
     * @return array
     */
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'services';
        $data['icon'] = 'fas fa-headset';
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
    }

    /**
     *
     * @param string $viewName
     */
    private function createViewEditConfig(string $viewName = self::VIEW_CONFIG_PROJECTS)
    {
        $this->addEditView($viewName, 'Settings', 'general');

        /// disable buttons
        $this->setSettings($viewName, 'btnDelete', false);
        $this->setSettings($viewName, 'btnNew', false);
    }

    /**
     *
     * @param string $viewName
     */
    private function createViewPriorities(string $viewName = self::VIEW_LIST_PRIORITIES)
    {
        $this->addEditListView($viewName, 'PrioridadAT', 'priority', 'fas fa-list-ol');
        $this->views[$viewName]->setInLine(true);
    }

    /**
     *
     * @param string $viewName
     */
    private function createViewStatus(string $viewName = self::VIEW_LIST_STATUS)
    {
        $this->addEditListView($viewName, 'EstadoAT', 'states', 'fas fa-tags');
        $this->views[$viewName]->setInLine(true);
    }

    /**
     * Loads the data to display.
     *
     * @param string   $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case self::VIEW_CONFIG_PROJECTS:
                $view->loadData('servicios');
                $view->model->name = 'servicios';
                break;

            case self::VIEW_LIST_PRIORITIES:
            case self::VIEW_LIST_STATUS:
                $view->loadData();
                break;
        }
    }
}
