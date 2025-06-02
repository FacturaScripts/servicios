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

use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Core\Model\Base\ModelTrait;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Tools;

/**
 * Description of ServicioATLog
 *
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
class ServicioATLog extends ModelClass
{
    use ModelTrait;

    /** @var string */
    public $context;

    /** @var string */
    public $creationdate;

    /** @var int */
    public $id;

    /** @var int */
    public $idservicio;

    /** @var string */
    public $ip;

    /** @var string */
    public $message;

    /** @var string */
    public $nick;

    public function clear()
    {
        parent::clear();
        $this->creationdate = Tools::dateTime();
        $this->ip = Session::getClientIp();
        $this->nick = Session::user()->nick;
    }

    public function getService(): ServicioAT
    {
        $service = new ServicioAT();
        $service->loadFromCode($this->idservicio);
        return $service;
    }

    public function install(): string
    {
        new ServicioAT();

        return parent::install();
    }

    public static function primaryColumn(): string
    {
        return 'id';
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
