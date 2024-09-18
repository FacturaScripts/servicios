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

use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Tools;
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
    /** @var Cliente */
    public $cliente;

    /** @var CodeModel */
    public $codeModel;

    /** @var MaquinaAT[] */
    public $maquinas = [];

    public function getNewCustomerUrl(): string
    {
        $customer = new Cliente();
        return $customer->url('new') . '?return=' . $this->getClassName();
    }

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
                $this->autocompleteCustomerAction();
                break;

            case 'autocomplete-machine':
                $this->autocompleteMachineAction();
                break;

            case 'machine':
                $this->machineAction();
                break;

            case 'new-service-with-machine':
                $this->newServiceWithMachineAction();
                break;

            case 'new-service-without-machine':
                $this->newServiceWithoutMachineAction();
                break;

            case 'new-machine':
                $this->newMachineAction();
                break;
        }
    }

    protected function autocompleteCustomerAction(): void
    {
        $this->setTemplate(false);

        $list = [];
        $cliente = new Cliente();
        $query = $this->request->get('query');
        foreach ($cliente->codeModelSearch($query, 'codcliente') as $value) {
            $list[$value->code] = $value->code . ' | ' . Tools::fixHtml($value->description);
        }

        if (empty($list)) {
            $list[] = ['key' => null, 'value' => Tools::lang()->trans('no-data')];
        }

        $this->response->setContent(json_encode($list));
    }

    protected function autocompleteMachineAction(): void
    {
        $this->setTemplate(false);

        $list = [];
        $machine = new MaquinaAT();
        $query = $this->request->get('query');
        $where = [new DataBaseWhere('descripcion|nombre|numserie|referencia', $query, 'XLIKE')];
        foreach ($machine->all($where, [], 0, 0) as $mac) {
            $list[$mac->idmaquina] = $mac->idmaquina . ' | ' . Tools::fixHtml($mac->nombre);
        }

        if (empty($list)) {
            $list[] = ['key' => null, 'value' => Tools::lang()->trans('no-data')];
        }

        $this->response->setContent(json_encode($list));
    }

    protected function loadCustomer(): void
    {
        $this->cliente = new Cliente();
        $code = $this->request->get('codcliente');
        if (empty($code)) {
            return;
        }

        if (false === $this->cliente->loadFromCode($code)) {
            Tools::log()->warning('customer-not-found');
            return;
        }

        // load machines
        $machine = new MaquinaAT();
        $where = [new DataBaseWhere('codcliente', $this->cliente->codcliente)];
        $this->maquinas = $machine->all($where, [], 0, 0);
    }

    protected function machineAction(): void
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
        $newServicio->idproyecto = $this->request->get('idproyecto');
        $newServicio->nick = $this->user->nick;
        if ($newServicio->save()) {
            $this->redirect($newServicio->url());
            return;
        }

        Tools::log()->warning('record-save-error');
    }

    protected function newServiceWithMachineAction()
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
        $newServicio->idproyecto = $this->request->get('idproyecto');
        $newServicio->nick = $this->user->nick;
        if ($newServicio->save()) {
            $this->redirect($newServicio->url());
            return;
        }

        Tools::log()->warning('record-save-error');
    }

    protected function newServiceWithoutMachineAction()
    {
        $newServicio = new ServicioAT();
        $newServicio->codalmacen = $this->user->codalmacen;
        $newServicio->codcliente = $this->cliente->codcliente;
        $newServicio->idempresa = $this->user->idempresa;
        $newServicio->idproyecto = $this->request->get('idproyecto');
        $newServicio->nick = $this->user->nick;
        if ($newServicio->save()) {
            $this->redirect($newServicio->url());
            return;
        }

        Tools::log()->warning('record-save-error');
    }

    protected function newMachineAction(): void
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
            Tools::log()->warning('record-save-error');
            return;
        }

        $newServicio = new ServicioAT();
        $newServicio->codalmacen = $this->user->codalmacen;
        $newServicio->codcliente = $this->cliente->codcliente;
        $newServicio->idempresa = $this->user->idempresa;
        $newServicio->idmaquina = $newMachine->idmaquina;
        $newServicio->idproyecto = $this->request->get('idproyecto');
        $newServicio->nick = $this->user->nick;
        if ($newServicio->save()) {
            $this->redirect($newServicio->url());
            return;
        }

        Tools::log()->warning('record-save-error');
    }
}
