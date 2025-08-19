<?php
/**
 * This file is part of Servicios plugin for FacturaScripts
 * Copyright (C) 2020-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Model\Base;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;

/**
 * Model class for the category of AT service.
 *
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class CategoriaAT extends ModelClass
{
    use ModelTrait;

    /**
     * Primary key of the table.
     *
     * @var int
     */
    public $id;

    /**
     * Category description.
     *
     * @var string
     */
    public $name;

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn(): string
    {
        return 'id';
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName(): string
    {
        return 'serviciosat_categorias';
    }

    /**
     * Returns true if there are no errors in the values of the model properties.
     * It runs inside the save method.
     *
     * @return bool
     */
    public function test(): bool
    {
        $this->name = Tools::noHtml($this->name);
        return parent::test();
    }

    /**
     * Returns the url where to see / modify the data.
     *
     * @param string $type
     * @param string $list
     * @return string
     */
    public function url(string $type = 'auto', string $list = 'AdminServicios?activetab=List'): string
    {
        return parent::url($type, $list);
    }
}
