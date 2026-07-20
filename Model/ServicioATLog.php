<?php
/**
 * This file is part of Servicios plugin for FacturaScripts
 * Copyright (C) 2022-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\User;

/**
 * Description of ServicioATLog
 *
 * @author Daniel Fernández Giménez <contacto@danielfg.es>
 */
class ServicioATLog extends ModelClass
{
    use ModelTrait;

    /** @var string Copia del servicio en formato JSON en el momento del cambio. */
    public $context;

    /** @var string Fecha y hora de creación del registro. */
    public $creationdate;

    /** @var int Clave primaria. Identificador del registro. */
    public $id;

    /** @var int Identificador del servicio al que pertenece el registro. */
    public $idservicio;

    /** @var string Dirección IP desde la que se realizó el cambio. */
    public $ip;

    /** @var string Mensaje que describe el cambio realizado. */
    public $message;

    /** @var string Nick del usuario que realizó el cambio. */
    public $nick;

    public function clear(): void
    {
        parent::clear();
        $this->creationdate = Tools::dateTime();
        $this->ip = Session::getClientIp();
        $this->nick = Session::user()->nick;
    }

    public function getService(): ServicioAT
    {
        $service = new ServicioAT();
        $service->load($this->idservicio);
        return $service;
    }

    public function install(): string
    {
        new User();

        return parent::install();
    }

    public static function tableName(): string
    {
        return 'serviciosat_logs';
    }

    public function test(): bool
    {
        $this->context = json_encode($this->context);
        $this->message = Tools::noHtml($this->message);

        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'EditServicioAT?activetab=List'): string
    {
        if ('list' === $type && !empty($this->idservicio)) {
            return $this->getService()->url() . '&activetab=List' . $this->modelClassName();
        }

        return parent::url($type, $list);
    }
}
