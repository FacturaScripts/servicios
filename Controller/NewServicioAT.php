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
use FacturaScripts\Core\Cache;
use FacturaScripts\Core\DataSrc\Empresas;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\CodeModel;
use FacturaScripts\Dinamic\Model\RoleAccess;
use FacturaScripts\Plugins\Servicios\Model\MaquinaAT;
use FacturaScripts\Plugins\Servicios\Model\ServicioAT;

/**
 * Description of NewServicioAT
 *
 * @author Carlos Garcia Gomez      <carlos@facturascripts.com>
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
class NewServicioAT extends Controller
{
    /** @var string */
    public $codalmacen;

    /** @var string */
    public $codcliente;

    /** @var CodeModel */
    public $codeModel;

    /** @var int */
    public $idmaquina;

    /** @var array */
    private $logLevels = ['critical', 'error', 'info', 'notice', 'warning'];

    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);

        $this->codeModel = new CodeModel();
        CodeModel::setLimit(10000);
        $this->codalmacen = $this->request->get('codalmacen', $this->user->codalmacen);
        $this->codcliente = $this->request->get('codcliente');
        $this->idmaquina = $this->request->get('idmaquina');

        $action = $this->request->get('action');
        $ajax = $this->request->get('ajax', false);

        if ($ajax) {
            $this->setTemplate(false);

            if (false === $this->user->can('NewServicioAT')
                || false === $this->checkMachine()) {
                $this->response->setContent(json_encode([
                    'redirect' => 'ListServicioAT'
                ]));
                return;
            }

            switch ($action) {
                case 'findCustomer':
                    $data = $this->findCustomerAction();
                    break;

                case 'renderCustomerMachines':
                    $data = $this->renderCustomerMachinesAction();
                    break;

                case 'saveNewCustomer':
                    $data = $this->saveNewCustomerAction();
                    break;

                case 'saveNewMachine':
                    $data = $this->saveNewMachineAction();
                    break;

                case 'saveNewService':
                    $data = $this->saveNewServiceAction();
                    break;
            }

            $content = array_merge(
                ['messages' => Tools::log()->read('master', $this->logLevels)],
                $data ?? []
            );
            $this->response->setContent(json_encode($content));
            return;
        }

        if (false === $this->user->can('NewServicioAT')
            || false === $this->checkMachine()) {
            $this->redirect('ListServicioAT');
        }
    }

    public function getCompanies(): array
    {
        return Empresas::all();
    }

    public function getModalCustomers(): array
    {
        // buscamos en caché
        $cacheKey = 'model-Cliente-sales-modal-' . $this->user->nick;
        $clientes = Cache::get($cacheKey);
        if (is_array($clientes)) {
            return $clientes;
        }

        // ¿El usuario tiene permiso para ver todos los clientes?
        $showAll = false;
        foreach (RoleAccess::allFromUser($this->user->nick, 'EditCliente') as $access) {
            if (false === $access->onlyownerdata) {
                $showAll = true;
            }
        }

        // consultamos la base de datos
        $cliente = new Cliente();
        $where = [new DataBaseWhere('fechabaja', null, 'IS')];
        if ($this->permissions->onlyOwnerData && !$showAll) {
            $where[] = new DataBaseWhere('codagente', $this->user->codagente);
            $where[] = new DataBaseWhere('codagente', null, 'IS NOT');
        }
        $clientes = $cliente->all($where, ['LOWER(nombre)' => 'ASC']);

        // guardamos en caché
        Cache::set($cacheKey, $clientes);

        return $clientes;
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'sales';
        $data['title'] = 'new-service';
        $data['showonmenu'] = false;
        return $data;
    }

    protected function checkMachine(): bool
    {
        if (empty($this->idmaquina)) {
            return true;
        }

        $machine = new MaquinaAT();
        if (false === $machine->loadFromCode($this->idmaquina)) {
            return false;
        }

        // si no hay cliente, usamos el cliente de la máquina
        if (empty($this->codcliente)) {
            $this->codcliente = $machine->codcliente;
        }

        // si el cliente es distinto al cliente de la máquina, no permitimos continuar
        if ($this->codcliente !== $machine->codcliente) {
            return false;
        }

        return true;
    }

    protected function findCustomerAction(): array
    {
        // ¿El usuario tiene permiso para ver todos los clientes?
        $showAll = false;
        foreach (RoleAccess::allFromUser($this->user->nick, 'EditCliente') as $access) {
            if (false === $access->onlyownerdata) {
                $showAll = true;
            }
        }
        $where = [];
        if ($this->permissions->onlyOwnerData && !$showAll) {
            $where[] = new DataBaseWhere('codagente', $this->user->codagente);
            $where[] = new DataBaseWhere('codagente', null, 'IS NOT');
        }

        $list = [];
        $customer = new Cliente();
        $term = $this->request->get('term');
        foreach ($customer->codeModelSearch($term, '', $where) as $item) {
            $list[$item->code] = $item->code . ' | ' . Tools::fixHtml($item->description);
        }

        return ['customers' => $list];
    }

    protected function renderCustomerMachinesAction(): array
    {
        $html = '';
        $orderBy = ['nombre' => 'ASC'];
        $where = [new DataBaseWhere('codcliente', $this->request->get('codcliente'))];
        foreach (MaquinaAT::all($where, $orderBy, 0, 0) as $machine) {
            $html .= '<tr class="clickableRow" data-idmaquina="' . $machine->idmaquina . '">'
                . '<td>' . $machine->nombre . '</td>'
                . '<td>' . $machine->numserie . '</td>'
                . '<td>' . $machine->descripcion . '</td>'
                . '</tr>';
        }

        if (empty($html)) {
            $html = '<tr class="table-warning"><td colspan="3">'
                . Tools::lang()->trans('no-data')
                . '</td></tr>';
        }

        return [
            'renderCustomerMachines' => true,
            'html' => $html
        ];
    }

    protected function saveNewCustomerAction(): array
    {
        if (false === $this->user->can('EditCliente', 'update')) {
            Tools::log()->warning('no-update-permission');
            return ['saveNewCustomer' => false];
        }

        // creamos el cliente
        $customer = new Cliente();
        $customer->nombre = $this->request->get('name');
        $customer->cifnif = $this->request->get('cifnif', '');
        $customer->email = $this->request->get('email');
        $customer->telefono1 = $this->request->get('phone1');
        $customer->telefono2 = $this->request->get('phone2');

        $resultExtension = $this->pipe('saveNewCustomer', $customer);
        if ($resultExtension instanceof Cliente) {
            $customer = $resultExtension;
        }

        if (false === $customer->save()) {
            Tools::log()->error('save-error');
            return ['saveNewCustomer' => false];
        }

        // modificamos la dirección
        foreach ($customer->getAddresses() as $address) {
            $address->direccion = $this->request->get('address');
            $address->codpostal = $this->request->get('zip');
            $address->ciudad = $this->request->get('city');
            $address->provincia = $this->request->get('province');
            $address->codpais = $this->request->get('country');
            $address->save();
            break;
        }

        return [
            'saveNewCustomer' => true,
            'codcliente' => $customer->codcliente,
        ];
    }

    protected function saveNewMachineAction(): array
    {
        if (false === $this->user->can('EditMaquinaAT', 'update')) {
            Tools::log()->warning('no-update-permission');
            return ['saveNewMachine' => false];
        }

        $machine = new MaquinaAT();
        $machine->codcliente = $this->request->get('codcliente');
        $machine->nombre = $this->request->get('name');
        $machine->numserie = $this->request->get('numserie');
        $machine->descripcion = $this->request->get('description');

        $resultExtension = $this->pipe('saveNewMachine', $machine);
        if ($resultExtension instanceof MaquinaAT) {
            $machine = $resultExtension;
        }

        if (false === $machine->save()) {
            Tools::log()->error('save-error');
            return ['saveNewMachine' => false];
        }

        return [
            'saveNewMachine' => true,
            'idmaquina' => $machine->idmaquina,
        ];
    }

    protected function saveNewServiceAction(): array
    {
        if (false === $this->user->can('EditServicioAT', 'update')) {
            Tools::log()->warning('no-update-permission');
            return ['saveNewService' => false];
        }

        $service = new ServicioAT();
        $service->codalmacen = $this->request->get('codalmacen');
        $service->codcliente = $this->request->get('codcliente');

        if ($this->request->get('idmaquina')) {
            $service->idmaquina = $this->request->get('idmaquina');
        }

        if (empty($service->codalmacen)) {
            $service->codalmacen = Tools::settings('default', 'codalmacen');
        }

        $resultExtension = $this->pipe('saveNewService', $service);
        if ($resultExtension instanceof ServicioAT) {
            $service = $resultExtension;
        }

        if (false === $service->save()) {
            Tools::log()->error('save-error');
            return ['saveNewService' => false];
        }

        return [
            'saveNewService' => true,
            'url' => $service->url('edit'),
        ];
    }
}