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

use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Core\Model\Base\ModelTrait;
use FacturaScripts\Core\Tools;

/**
 * Model class for the checks neededs for compled one category.
 *
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class CheckAT extends ModelClass
{
    use ModelTrait;

    /**
     * Primary key of the table.
     *
     * @var int
     */
    public $id;

    /**
     * Link to the Category model.
     *
     * @var int
     */
    public $idcategory;

    /**
     * Task description.
     *
     * @var string
     */
    public $name;

    /**
     * Indicates the priority order of the check.
     *
     * @var int
     */
    public $priority;

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->priority = 0;
    }

    /**
     * This function is called when creating the model table. Returns the SQL
     * that will be executed after the creation of the table. Useful to insert values
     * default.
     *
     * @return string
     */
    public function install(): string
    {
        new CategoriaAT();
        return parent::install();
    }

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
        return 'serviciosat_checks';
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
        if (is_null($this->priority)) {
            $this->priority = 0;
        }
        return parent::test();
    }
}
