<?php
/**
 * This file is part of Servicios plugin for FacturaScripts
 * Copyright (C) 2022-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Contract\SalesModInterface;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\ServicioAT;

class SalesHeaderHTMLMod implements SalesModInterface
{
    public function apply(SalesDocument &$model, array $formData): void
    {
    }

    public function applyBefore(SalesDocument &$model, array $formData): void
    {
    }

    public function assets(): void
    {
    }

    public function newBtnFields(): array
    {
        return ['servicio_btn'];
    }

    public function newFields(): array
    {
        return [];
    }

    public function newModalFields(): array
    {
        return ['servicio'];
    }

    public function renderField(SalesDocument $model, string $field): ?string
    {
        if ($field == 'servicio') {
            return $this->servicio($model);
        }

        if ($field == 'servicio_btn') {
            return $this->servicioBtn($model);
        }

        return null;
    }

    private static function servicioBtn(SalesDocument $model): string
    {
        if (false === $model->hasColumn('idservicio') || empty($model->{'idservicio'})) {
            return '';
        }

        $service = new ServicioAT();
        if (false === $service->load($model->{'idservicio'})) {
            return '';
        }

        return '<div class="col-sm-auto">'
            . '<div class="mb-2">'
            . '<a href="' . $service->url() . '" class="btn btn-warning text-black">'
            . '<i class="fa-solid fa-headset"></i> ' . Tools::trans('service')
            . '</a>'
            . '</div>'
            . '</div>';
    }

    private static function servicio(SalesDocument $model): string
    {
        if (false === $model->hasColumn('idservicio') || empty($model->{'idservicio'})) {
            return '';
        }

        $service = new ServicioAT();
        if (false === $service->load($model->{'idservicio'})) {
            return '';
        }

        return '<div class="col-sm-6">'
            . '<div class="mb-3">'
            . '<a href="' . $service->url() . '">' . Tools::trans('service') . '</a>'
            . '<input type="text" value="' . $service->codigo . '" class="form-control" disabled />'
            . '</div>'
            . '</div>';
    }
}