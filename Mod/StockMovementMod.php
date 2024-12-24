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

namespace FacturaScripts\Plugins\Servicios\Mod;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\TrabajoAT;
use FacturaScripts\Dinamic\Model\MovimientoStock;
use FacturaScripts\Plugins\StockAvanzado\Contract\StockMovementModInterface;

class StockMovementMod implements StockMovementModInterface
{
    public function run(): void
    {
        // generamos un movimiento para cada trabajo con referencia
        $model = new TrabajoAT();
        $where = [new DataBaseWhere('referencia', '', '!=')];
        $orderBy = ['idtrabajo' => 'ASC'];
        $limit = 1000;
        $offset = 0;

        do {
            $trabajos = $model->all($where, $orderBy, $limit, $offset);
            foreach ($trabajos as $trabajo) {
                $this->generateMovement($trabajo);
            }
            $offset += $limit;
        } while (count($trabajos) == $limit);
    }

    private function generateMovement(TrabajoAT $trabajo): void
    {
        $estados = [TrabajoAT::STATUS_NONE, TrabajoAT::STATUS_MAKE_INVOICE, TrabajoAT::STATUS_MAKE_DELIVERY_NOTE];
        if (empty($trabajo->referencia) || empty($trabajo->cantidad) || false === in_array($trabajo->estado, $estados)) {
            return;
        }

        // ¿El producto controla stock?
        $producto = $trabajo->getVariante()->getProducto();
        if ($producto->nostock) {
            return;
        }

        $movement = new MovimientoStock();
        $movement->codalmacen = $trabajo->getServicio()->codalmacen;
        $movement->referencia = $trabajo->referencia;
        $movement->cantidad = 0 - $trabajo->cantidad;
        $movement->docid = $trabajo->idtrabajo;
        $movement->docmodel = 'TrabajoAT';
        $movement->documento = Tools::lang()->trans('service') . ' #' . $trabajo->idservicio;
        $movement->idproducto = $trabajo->getVariante()->idproducto;
        $movement->fecha = $trabajo->fechainicio;
        $movement->hora = $trabajo->horainicio;
        $movement->save();
    }
}
