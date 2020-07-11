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
namespace FacturaScripts\Plugins\Servicios\Controller;

use FacturaScripts\Core\Lib\ExtendedController\EditController;

/**
 * Description of EditServicioCliente
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class EditServicioCliente extends EditController
{

    /**
     * 
     * @return string
     */
    public function getModelClassName()
    {
        return 'ServicioCliente';
    }

    /**
     * 
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'sales';
        $data['title'] = 'service';
        $data['icon'] = 'fas fa-edit';
        $data['showonmenu'] = false;

        return $data;
    }

    protected function createViews()
    {
        parent::createViews();
        $this->setTabsPosition('top');
    }
}
