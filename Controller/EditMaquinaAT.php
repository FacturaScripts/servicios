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

use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\EditController;
use FacturaScripts\Core\Where;


/**
 * Description of EditMaquinaAT
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class EditMaquinaAT extends EditController
{
    public function getModelClassName(): string
    {
        return 'MaquinaAT';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'sales';
        $data['title'] = 'machine';
        $data['icon'] = 'fa-solid fa-laptop-medical';
        $data['showonmenu'] = false;

        return $data;
    }

    protected function createViews()
    {
        parent::createViews();
        $this->setTabsPosition('bottom');

        $this->createViewsServices();
    }

    protected function createViewsServices(string $viewName = 'ListServicioAT'): void
    {
        $this->addListView($viewName, 'ServicioAT', 'services', 'fa-solid fa-headset')
            ->addSearchFields(['descripcion', 'idservicio', 'observaciones'])
            ->addOrderBy(['fecha', 'hora'], 'date', 2)
            ->addOrderBy(['prioridad'], 'priority')
            ->addOrderBy(['idservicio'], 'code')
            ->disableColumn('machine');
    }

    /**
     * @param string $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        $mainViewName = $this->getMainViewName();

        switch ($viewName) {
            case 'ListServicioAT':
                $idmaquina = $this->getViewModelValue($mainViewName, 'idmaquina');
                $where = [
                    Where::column('idmaquina', $idmaquina),
                    Where::or('idmaquina2', $idmaquina),
                    Where::or('idmaquina3', $idmaquina),
                    Where::or('idmaquina4', $idmaquina)
                ];
                $view->loadData('', $where);
                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }
}
