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

namespace FacturaScripts\Plugins\Servicios\Model;

use FacturaScripts\Core\DataSrc\Agentes;
use FacturaScripts\Core\Model\Base\CompanyRelationTrait;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Lib\CodePatterns;
use FacturaScripts\Dinamic\Lib\Email\MailNotifier;
use FacturaScripts\Dinamic\Model\Agente;
use FacturaScripts\Dinamic\Model\Almacen;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\Empresa;
use FacturaScripts\Dinamic\Model\PedidoCliente;
use FacturaScripts\Dinamic\Model\TrabajoAT as DinTrabajoAT;
use FacturaScripts\Dinamic\Model\User;

/**
 * Description of ServicioAT
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class ServicioAT extends ModelClass
{
    use ModelTrait;
    use CompanyRelationTrait;

    /** @var string */
    public $asignado;

    /** @var string */
    public $codagente;

    /** @var string */
    public $codalmacen;

    /** @var string */
    public $codcliente;

    /** @var string */
    public $codigo;

    /** @var string */
    public $descripcion;

    /** @var bool */
    public $editable;

    /** @var string */
    public $fecha;

    /** @var string */
    public $hora;

    /** @var int */
    public $idestado;

    /** @var int */
    public $idmaquina;

    /** @var int */
    public $idmaquina2;

    /** @var int */
    public $idmaquina3;

    /** @var int */
    public $idmaquina4;

    /** @var int */
    public $idtipo;

    /** @var int */
    public $idprioridad;

    /** @var int */
    public $idservicio;

    /** @var string */
    public $material;

    /** @var string */
    public $nick;

    /** @var double */
    public $neto;

    /** @var string */
    public $observaciones;

    /** @var string */
    public $solucion;

    /** @var string */
    public $telefono1;

    /** @var string */
    public $telefono2;

    public function calculatePriceNet(): void
    {
        $this->neto = 0.0;
        foreach ($this->getTrabajos() as $trabajo) {
            $this->neto += $trabajo->precio * $trabajo->cantidad;
        }
        $this->save();
    }

    public function clear(): void
    {
        parent::clear();

        $this->fecha = Tools::date();
        $this->hora = Tools::hour();
        $this->neto = 0.0;
        $this->nick = Session::user()->nick;

        // set default status
        foreach ($this->getAvailableStatus() as $status) {
            if ($status->predeterminado) {
                $this->idestado = $status->id;
                $this->editable = $status->editable;
                if ($status->asignado) {
                    $this->asignado = $status->asignado;
                }
                break;
            }
        }

        // set default priority
        foreach ($this->getAvailablePriority() as $priority) {
            if ($priority->predeterminado) {
                $this->idprioridad = $priority->id;
                break;
            }
        }

        // set default type
        foreach ($this->getAvailableTypes() as $type) {
            if ($type->default) {
                $this->idtipo = $type->id;
                break;
            }
        }
    }

    public function delete(): bool
    {
        foreach ($this->getTrabajos() as $trabajo) {
            if (false === $trabajo->delete()) {
                return false;
            }
        }

        if (false === parent::delete()) {
            return false;
        }

        // añadimos el cambio al log
        $messageLog = Tools::trans('deleted-service');
        $this->log($messageLog);

        return true;
    }

    public function getAgent(?string $codagente = null): Agente
    {
        $codagente = is_null($codagente) ? $this->codagente : $codagente;
        return Agentes::get($codagente);
    }

    public function getAsignado(?string $asignado = null): User
    {
        $asignado = is_null($asignado) ? $this->asignado : $asignado;
        $user = new User();
        $user->load($asignado);
        return $user;
    }

    /**
     * @return PrioridadAT[]
     */
    public function getAvailablePriority(): array
    {
        return PrioridadAT::all();
    }

    /**
     * @return TipoAT[]
     */
    public function getAvailableTypes(): array
    {
        return TipoAT::all();
    }

    /**
     * @return EstadoAT[]
     */
    public function getAvailableStatus(): array
    {
        return EstadoAT::all();
    }

    public function getCustomer(?string $codcliente = null): Cliente
    {
        $codcliente = is_null($codcliente) ? $this->codcliente : $codcliente;
        $customer = new Cliente();
        $customer->load($codcliente);
        return $customer;
    }

    /**
     * @return MaquinaAT[]
     */
    public function getMachines(): array
    {
        $result = [];
        $machines = [$this->idmaquina, $this->idmaquina2, $this->idmaquina3, $this->idmaquina4];
        foreach ($machines as $code) {
            if (empty($code)) {
                continue;
            }

            $machine = new MaquinaAT();
            $machine->load($code);
            $result[] = $machine;
        }

        return $result;
    }

    public function getStatus(?int $idestado = null): EstadoAT
    {
        $idestado = $idestado ?? $this->idestado;
        $status = new EstadoAT();
        $status->load($idestado);
        return $status;
    }

    public function getPriority(): PrioridadAT
    {
        $priority = new PrioridadAT();
        $priority->load($this->idprioridad);
        return $priority;
    }

    public function getType(): TipoAT
    {
        $type = new TipoAT();
        $type->load($this->idtipo);
        return $type;
    }

    public function getSubject(): Cliente
    {
        $cliente = new Cliente();
        $cliente->load($this->codcliente);
        return $cliente;
    }

    /**
     * @return TrabajoAT[]
     */
    public function getTrabajos(): array
    {
        $where = [Where::column('idservicio', $this->idservicio)];
        $order = ['fechainicio' => 'ASC', 'horainicio' => 'ASC'];
        return DinTrabajoAT::all($where, $order);
    }

    public function getUser(?string $nick = null): User
    {
        $nick = is_null($nick) ? $this->nick : $nick;
        $user = new User();
        $user->load($nick);
        return $user;
    }

    public function install(): string
    {
        new Agente();
        new Almacen();
        new Cliente();
        new MaquinaAT();
        new Empresa();
        new EstadoAT();
        new User();
        new PrioridadAT();
        new TipoAT();
        new PedidoCliente();

        return parent::install();
    }

    public function log(string $message): bool
    {
        $log = new ServicioATLog();
        $log->idservicio = $this->idservicio;
        $log->message = $message;
        $log->context = $this;
        return $log->save();
    }

    public static function primaryColumn(): string
    {
        return 'idservicio';
    }

    public function primaryDescriptionColumn(): string
    {
        return 'codigo';
    }

    public static function tableName(): string
    {
        return 'serviciosat';
    }

    public function test(): bool
    {
        if (empty($this->codigo)) {
            // obtenemos el patrón de la configuración
            $pattern = Tools::settings('servicios', 'patron', 'SER-{NUM}');

            // si no tenemos id, asignamos uno nuevo
            if (empty($this->idservicio)) {
                $this->idservicio = $this->newCode();
            }

            // generamos el código
            $this->codigo = CodePatterns::trans($pattern, $this, [
                'numero' => 'idservicio',
                'long' => Tools::settings('servicios', 'longnumero', 6)
            ]);
        }

        // si los teléfonos están vacíos, los rellenamos con los del cliente
        if ($this->editable && empty($this->telefono1) && empty($this->telefono2)) {
            $customer = $this->getSubject();
            $this->telefono1 = $customer->telefono1;
            $this->telefono2 = $customer->telefono2;
        }

        $fields = ['codigo', 'descripcion', 'material', 'observaciones', 'solucion', 'telefono1', 'telefono2'];
        foreach ($fields as $key) {
            $this->{$key} = Tools::noHtml($this->{$key});
        }

        // comprobamos que editable y asignado se corresponda con el estado
        $status = $this->getStatus();
        if ($this->editable != $status->editable) {
            $this->editable = $status->editable;
        }
        if (!empty($status->asignado) && $this->asignado != $status->asignado) {
            $this->asignado = $status->asignado;
        }

        // si tenemos almacén, pero no empresa, obtenemos la empresa del almacén
        if (false === empty($this->codalmacen) && empty($this->codempresa)) {
            $warehouse = new Almacen();
            if ($warehouse->load($this->codalmacen)) {
                $this->idempresa = $warehouse->idempresa;
            }
        }

        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'List'): string
    {
        return $type === 'new' ? 'NewServicioAT' : parent::url($type, $list);
    }

    protected function onChange(string $field): bool
    {
        if ($field == 'idestado') {
            $newStatus = $this->getStatus();

            // asignamos el valor de editable
            $this->editable = $newStatus->editable;

            // si el estado tiene un asignado, lo asignamos
            if ($newStatus->asignado) {
                $this->asignado = $newStatus->asignado;
            }

            // añadimos el cambio al log
            $messageLog = Tools::trans('changed-status-to', [
                '%oldStatus%' => $this->getStatus($this->getOriginal('idestado'))->nombre,
                '%newStatus%' => $newStatus->nombre
            ]);
            $this->log($messageLog);
        }

        return parent::onChange($field);
    }

    protected function onInsert(): void
    {
        // enviamos notificaciones
        if ($this->asignado) {
            $this->notifyAssignedUser('new-service-assignee');
        }
        if ($this->codagente) {
            $this->notifyAgent('new-service-agent');
        }
        if ($this->codcliente) {
            $this->notifyCustomer('new-service-customer');
        }

        $message = Tools::trans('new-service-created', ['%number%' => $this->id()]);
        $this->log($message);

        parent::onInsert();
    }

    protected function onUpdate(): void
    {
        if ($this->asignado != $this->getOriginal('asignado')) {
            $this->onUpdateAsignado();
        }

        if ($this->codagente != $this->getOriginal('codagente')) {
            $this->onUpdateCodagente();
        }

        if ($this->codcliente != $this->getOriginal('codcliente')) {
            $this->onUpdateCodcliente();
        }

        if ($this->nick != $this->getOriginal('nick')) {
            $this->onUpdateUser();
        }

        if ($this->idestado != $this->getOriginal('idestado')) {
            $this->onUpdateStatus();
        }

        parent::onUpdate();
    }

    protected function onUpdateAsignado(): void
    {
        $newAssigned = $this->getAsignado();
        $oldAssigned = $this->getAsignado($this->getOriginal('asignado') ?? '');

        // añadimos el cambio al log
        $messageLog = Tools::trans('changed-assigned-to', [
            '%oldAssigned%' => $oldAssigned->nick ?? '-',
            '%newAssigned%' => $newAssigned->nick ?? '-'
        ]);
        $this->log($messageLog);

        // enviamos las notificaciones
        if ($this->asignado) {
            $this->notifyAssignedUser('new-service-assignee');
        }
    }

    protected function onUpdateCodagente(): void
    {
        $newAgent = $this->getAgent();
        $oldAgent = $this->getAgent($this->getOriginal('codagente') ?? '');

        // añadimos el cambio al log
        $messageLog = Tools::trans('changed-agent-to', [
            '%oldAgent%' => $oldAgent->nombre ?? '-',
            '%newAgent%' => $newAgent->nombre ?? '-'
        ]);
        $this->log($messageLog);

        // enviamos las notificaciones
        if ($this->codagente) {
            $this->notifyAgent('new-service-agent');
        }
    }

    protected function onUpdateCodcliente(): void
    {
        $newCustomer = $this->getCustomer();
        $oldCustomer = $this->getCustomer($this->getOriginal('codcliente') ?? '');

        // añadimos el cambio al log
        $messageLog = Tools::trans('changed-customer-to', [
            '%oldCustomer%' => $oldCustomer->nombre ?? '-',
            '%newCustomer%' => $newCustomer->nombre ?? '-'
        ]);
        $this->log($messageLog);

        // enviamos las notificaciones
        if ($this->codcliente) {
            $this->notifyCustomer('new-service-customer');
        }
    }

    protected function onUpdateStatus(): void
    {
        $notification = 'new-service-status';

        // obtenemos el estado
        $newStatus = $this->getStatus();

        // notificamos al agente
        if ($newStatus->notificaragente) {
            $this->notifyAgent($notification);
        }

        // notificamos al asignado
        if ($newStatus->notificarasignado) {
            $this->notifyAssignedUser($notification);
        }

        // notificamos al cliente
        if ($newStatus->notificarcliente) {
            $this->notifyCustomer($notification);
        }

        // notificamos al usuario
        if ($newStatus->notificarusuario) {
            $this->notifyUser($notification);
        }
    }

    protected function onUpdateUser(): void
    {
        $newUser = $this->getUser();
        $oldUser = $this->getUser($this->getOriginal('nick') ?? '');

        // añadimos el cambio al log
        $messageLog = Tools::trans('changed-user-to', [
            '%oldUser%' => $oldUser->nick ?? '-',
            '%newUser%' => $newUser->nick ?? '-'
        ]);
        $this->log($messageLog);

        // enviamos las notificaciones
        if ($this->nick) {
            $this->notifyUser('new-service-user');
        }
    }

    protected function notifyAgent(string $notification): void
    {
        $agent = new Agente();
        if (false === $agent->load($this->codagente) || empty($agent->email)) {
            return;
        }

        MailNotifier::send($notification, $agent->email, $agent->nombre, [
            'number' => $this->idservicio,
            'code' => $this->codigo,
            'date' => $this->fecha,
            'customer' => $this->getSubject()->nombre,
            'author' => $this->nick,
            'status' => $this->getStatus()->nombre,
            'url' => Tools::siteUrl() . '/EditServicioAT?code=' . $this->idservicio
        ]);
    }

    protected function notifyAssignedUser(string $notification): void
    {
        $assigned = new User();
        if (false === $assigned->load($this->asignado)) {
            return;
        }

        MailNotifier::send($notification, $assigned->email, $assigned->nick, [
            'number' => $this->idservicio,
            'code' => $this->codigo,
            'date' => $this->fecha,
            'customer' => $this->getSubject()->nombre,
            'author' => $this->nick,
            'status' => $this->getStatus()->nombre,
            'url' => Tools::siteUrl() . '/EditServicioAT?code=' . $this->idservicio
        ]);
    }

    protected function notifyCustomer(string $notification): void
    {
        $customer = $this->getSubject();
        if (empty($customer) || empty($customer->email)) {
            return;
        }

        MailNotifier::send($notification, $customer->email, $customer->nombre, [
            'number' => $this->idservicio,
            'code' => $this->codigo,
            'date' => $this->fecha,
            'customer' => $customer->nombre,
            'author' => $this->nick,
            'status' => $this->getStatus()->nombre,
            'url' => Tools::siteUrl() . '/EditServicioAT?code=' . $this->idservicio
        ]);
    }

    protected function notifyUser(string $notification): void
    {
        $user = new User();
        if (false === $user->load($this->nick)) {
            return;
        }

        MailNotifier::send($notification, $user->email, $user->nick, [
            'number' => $this->idservicio,
            'code' => $this->codigo,
            'date' => $this->fecha,
            'customer' => $this->getSubject()->nombre,
            'author' => $this->nick,
            'status' => $this->getStatus()->nombre,
            'url' => Tools::siteUrl() . '/EditServicioAT?code=' . $this->idservicio
        ]);
    }
}
