<?php
/**
 * This file is part of Servicios plugin for FacturaScripts
 * Copyright (C) 2020-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Plugins\Servicios\Extension\Model;

use Closure;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Tools;
use FacturaScripts\Plugins\StockAvanzado\Model\MovimientoStock;

class TrabajoAT
{
    protected function deleteStockMovement(): Closure
    {
        return function () {
            $movement = new MovimientoStock();
            $where = [
                new DataBaseWhere('docid', $this->idtrabajo),
                new DataBaseWhere('docmodel', 'TrabajoAT'),
            ];
            if ($movement->laodWhere($where)) {
                $movement->delete();
            }
        };
    }

    public function install(): Closure
    {
        return function () {
            new MovimientoStock();
        };
    }

    protected function onDelete(): Closure
    {
        return function () {
            $this->deleteStockMovement();
        };
    }

    protected function onInsert(): Closure
    {
        return function () {
            $this->setStockMovement();
        };
    }

    protected function onUpdate(): Closure
    {
        return function () {
            $this->setStockMovement();
        };
    }

    protected function setStockMovement(): Closure
    {
        return function () {
            // solamente algunos estados modifican el stock
            $estados = [
                \FacturaScripts\Plugins\Servicios\Model\TrabajoAT::STATUS_NONE,
                \FacturaScripts\Plugins\Servicios\Model\TrabajoAT::STATUS_MAKE_INVOICE,
                \FacturaScripts\Plugins\Servicios\Model\TrabajoAT::STATUS_MAKE_DELIVERY_NOTE
            ];
            if (empty($this->referencia) || empty($this->cantidad) || false === in_array($this->estado, $estados)) {
                $this->deleteStockMovement();
                return;
            }

            // ¿El producto controla stock?
            $producto = $this->getVariante()->getProducto();
            if ($producto->nostock) {
                $this->deleteStockMovement();
                return;
            }

            // buscamos el movimiento de stock
            $movement = new MovimientoStock();
            $where = [
                new DataBaseWhere('docid', $this->idtrabajo),
                new DataBaseWhere('docmodel', 'TrabajoAT'),
            ];
            if (false === $movement->loadWhere($where)) {
                // si no existe, lo creamos
                $movement->referencia = $this->referencia;
                $movement->docid = $this->idtrabajo;
                $movement->docmodel = 'TrabajoAT';
                $movement->documento = Tools::lang()->trans('service') . ' #' . $this->idservicio;
                $movement->idproducto = $this->getVariante()->idproducto;
            }

            $movement->cantidad = 0 - $this->cantidad;
            $movement->codalmacen = $this->getServicio()->codalmacen;
            $movement->fecha = $this->fechainicio;
            $movement->hora = $this->horainicio;
            $movement->save();
        };
    }
}
