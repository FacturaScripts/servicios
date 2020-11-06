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
use FacturaScripts\Dinamic\Model\CodeModel;
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
     * @var CodeModel
     */
    public $codeModel;

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
        $this->codeModel = new CodeModel();
        $this->loadCustomer();

        $action = $this->request->get('action');
        switch ($action) {
            case 'autocomplete-customer':
                return $this->autocompleteCustomerAction();

            case 'autocomplete-machine':
                return $this->autocompleteMachineAction();

            case 'machine':
                return $this->machineAction();

            case 'new-machine':
                return $this->newMachineAction();

            case 'no-machine':
                return $this->noMachineAction();

            default:
                return $this->defaultAction();
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

    protected function autocompleteMachineAction()
    {
        $this->setTemplate(false);

        $list = [];
        $machine = new MaquinaAT();
        $query = $this->request->get('query');
        $where = [new DataBaseWhere('descripcion|nombre|numserie|referencia', $query, 'XLIKE')];
        foreach ($machine->all($where) as $mac) {
            $list[] = [
                'key' => $this->toolBox()->utils()->fixHtml($mac->idmaquina),
                'value' => $this->toolBox()->utils()->fixHtml($mac->nombre)
            ];
        }

        if (empty($list)) {
            $list[] = ['key' => null, 'value' => $this->toolBox()->i18n()->trans('no-data')];
        }

        $this->response->setContent(\json_encode($list));
    }

    protected function defaultAction()
    {
        $id = $this->request->get('idmaquina');
        if (empty($id)) {
            return;
        }

        $maquina = new MaquinaAT();
        if (false === $maquina->loadFromCode($id)) {
            return;
        }

        if (empty($maquina->codcliente) || false === $this->cliente->loadFromCode($maquina->codcliente)) {
            return;
        }

        $newServicio = new ServicioAT();
        $newServicio->codalmacen = $this->user->codalmacen;
        $newServicio->codcliente = $this->cliente->codcliente;
        $newServicio->idempresa = $this->user->idempresa;
        $newServicio->idmaquina = $id;
        $newServicio->nick = $this->user->nick;
        if ($newServicio->save()) {
            $this->redirect($newServicio->url());
            return;
        }

        $this->toolBox()->i18nLog()->warning('record-save-error');
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
        $idmaquina = $this->request->request->get('idmaquina');
        if (empty($idmaquina)) {
            return;
        }

        $newServicio = new ServicioAT();
        $newServicio->codalmacen = $this->user->codalmacen;
        $newServicio->codcliente = $this->cliente->codcliente;
        $newServicio->idempresa = $this->user->idempresa;
        $newServicio->idmaquina = $idmaquina;
        $newServicio->nick = $this->user->nick;
        if ($newServicio->save()) {
            $this->redirect($newServicio->url());
            return;
        }

        $this->toolBox()->i18nLog()->warning('record-save-error');
    }

    protected function newMachineAction()
    {
        $codfabricante = $this->request->request->get('codfabricante');

        $newMachine = new MaquinaAT();
        $newMachine->codcliente = $this->cliente->codcliente;
        $newMachine->codfabricante = empty($codfabricante) ? null : $codfabricante;
        $newMachine->descripcion = $this->request->request->get('descripcion');
        $newMachine->nombre = $this->request->request->get('nombre');
        $newMachine->numserie = $this->request->request->get('numserie');
        $newMachine->referencia = $this->request->request->get('referencia');
        if (false === $newMachine->save()) {
            $this->toolBox()->i18nLog()->warning('record-save-error');
            return;
        }

        $newServicio = new ServicioAT();
        $newServicio->codalmacen = $this->user->codalmacen;
        $newServicio->codcliente = $this->cliente->codcliente;
        $newServicio->idempresa = $this->user->idempresa;
        $newServicio->idmaquina = $newMachine->idmaquina;
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
