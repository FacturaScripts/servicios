<?php
/**
 * This file is part of Servicios plugin for FacturaScripts
 * Copyright (C) 2020-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Lib\ExtendedController\EditController;

/**
 * Controler to edit a service category.
 *
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class EditCategoriaAT extends EditController
{
    private const VIEW_CHECKS = 'EditCheckAT';

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData(): array
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'category';
        $pagedata['icon'] = 'fa-solid fa-tags';
        $pagedata['menu'] = 'sales';
        $pagedata['showonmenu'] = false;
        return $pagedata;
    }

    /**
     * Returns the class name of the model to use in the editView.
     */
    public function getModelClassName(): string
    {
        return 'CategoriaAT';
    }

    /**
     * Create the view to display.
     */
    protected function createViews()
    {
        parent::createViews();
        $this->createViewsChecks();
        $this->setTabsPosition('bottom');
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
            case self::VIEW_CHECKS:
                $mnv = $this->getMainViewName();
                $idcategory = $this->getViewModelValue($mnv, 'id');
                $where = [ new DataBaseWhere('idcategory', $idcategory) ];
                $view->loadData('', $where, ['priority' => 'DESC']);
                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }

    /**
     * Add the checks view to the edit view.
     *
     * @param string $viewName
     * @return void
     */
    private function createViewsChecks(string $viewName = self::VIEW_CHECKS): void
    {
        $this->addEditListView($viewName, 'CheckAT', 'checks', 'fa-solid fa-check-double')
            ->setInLine(true);
    }
}
