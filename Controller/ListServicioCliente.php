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

use FacturaScripts\Core\Lib\ExtendedController\ListController;

/**
 * Description of ListServicioCliente
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class ListServicioCliente extends ListController
{

    /**
     * 
     * @return array
     */
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
        $this->createViewsStatus();
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createViewsServices(string $viewName = 'ListServicioCliente')
    {
        $this->addView($viewName, 'ServicioCliente', 'services', 'fas fa-headset');
        $this->addOrderBy($viewName, ['fecha', 'hora'], 'date', 2);
        $this->addOrderBy($viewName, ['prioridad'], 'priority');
        $this->addOrderBy($viewName, ['idservicio'], 'code');
        $this->addSearchFields($viewName, ['descripcion', 'idservicio', 'numserie', 'observaciones', 'referencia']);

        /// filters
        $this->addFilterPeriod($viewName, 'fecha', 'date', 'fecha');
        $this->addFilterAutocomplete($viewName, 'codcliente', 'customer', 'codcliente', 'clientes', 'codcliente', 'nomnbre');

        $status = $this->codeModel->all('nservicioscli_estados', 'id', 'nombre');
        $this->addFilterSelect($viewName, 'idestado', 'status', 'idestado', $status);

        $agents = $this->codeModel->all('agentes', 'codagente', 'nombre');
        $this->addFilterSelect($viewName, 'codagente', 'agent', 'codagente', $agents);
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createViewsStatus(string $viewName = 'ListEstadoServicioCliente')
    {
        $this->addView($viewName, 'EstadoServicioCliente', 'states', 'fas fa-tags');
        $this->addOrderBy($viewName, ['nombre'], 'name', 1);
    }
}
