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

use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Plugins\Servicios\Model\MaquinaAT;
use FacturaScripts\Plugins\Servicios\Model\ServicioAT;

/**
 * Description of NewServicioAT
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class NewServicioAT extends Controller
{

    /**
     *
     * @var Cliente
     */
    public $cliente;

    /**
     *
     * @var MaquinaAT[]
     */
    public $maquinas = [];

    /**
     * 
     * @return string
     */
    public function getNewCustomerUrl()
    {
        $customer = new Cliente();
        return $customer->url('new') . '?return=' . $this->getClassName();
    }

    /**
     * 
     * @return array
     */
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'sales';
        $data['title'] = 'new-service';
        $data['showonmenu'] = false;
        return $data;
    }

    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);
        $this->loadCustomer();

        $action = $this->request->get('action');
        switch ($action) {
            case 'autocomplete-customer':
                return $this->autocompleteCustomerAction();

            case 'machine':
                return $this->machineAction();

            case 'no-machine':
                return $this->noMachineAction();
        }
    }

    protected function autocompleteCustomerAction()
    {
        $this->setTemplate(false);

        $list = [];
        $cliente = new Cliente();
        $query = $this->request->get('query');
        foreach ($cliente->codeModelSearch($query, 'codcliente') as $value) {
            $list[] = [
                'key' => $this->toolBox()->utils()->fixHtml($value->code),
                'value' => $this->toolBox()->utils()->fixHtml($value->description)
            ];
        }

        if (empty($list)) {
            $list[] = ['key' => null, 'value' => $this->toolBox()->i18n()->trans('no-data')];
        }

        $this->response->setContent(\json_encode($list));
    }

    protected function loadCustomer()
    {
        $this->cliente = new Cliente();
        $code = $this->request->get('codcliente');
        if (empty($code)) {
            return;
        }

        if (false === $this->cliente->loadFromCode($code)) {
            $this->toolBox()->i18nLog()->warning('customer-not-found');
            return;
        }

        /// load machines
        $machine = new MaquinaAT();
        $where = [new DataBaseWhere('codcliente', $this->cliente->codcliente)];
        $this->maquinas = $machine->all($where, [], 0, 0);
    }

    protected function machineAction()
    {
        $newServicio = new ServicioAT();
        $newServicio->codalmacen = $this->user->codalmacen;
        $newServicio->codcliente = $this->cliente->codcliente;
        $newServicio->idempresa = $this->user->idempresa;
        $newServicio->idmaquina = $this->request->request->get('idmaquina');
        $newServicio->nick = $this->user->nick;
        if ($newServicio->save()) {
            $this->redirect($newServicio->url());
            return;
        }

        $this->toolBox()->i18nLog()->warning('record-save-error');
    }

    protected function noMachineAction()
    {
        $newServicio = new ServicioAT();
        $newServicio->codalmacen = $this->user->codalmacen;
        $newServicio->codcliente = $this->cliente->codcliente;
        $newServicio->idempresa = $this->user->idempresa;
        $newServicio->nick = $this->user->nick;
        if ($newServicio->save()) {
            $this->redirect($newServicio->url());
            return;
        }

        $this->toolBox()->i18nLog()->warning('record-save-error');
    }
}
