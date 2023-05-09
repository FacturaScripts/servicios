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

use FacturaScripts\Core\Base\Contract\SalesModInterface;
use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Model\User;

class SalesHeaderHTMLMod implements SalesModInterface
{
    public function apply(SalesDocument &$model, array $formData, User $user)
    {
    }

    public function applyBefore(SalesDocument &$model, array $formData, User $user)
    {
    }

    public function assets(): void
    {
    }

    public function newFields(): array
    {
        return ['servicio'];
    }

    public function renderField(Translator $i18n, SalesDocument $model, string $field): ?string
    {
        if ($field == 'servicio') {
            return $this->servicio($i18n, $model);
        }

        return null;
    }

    private static function servicio(Translator $i18n, SalesDocument $model): string
    {
        return property_exists($model, 'idservicio') && false === empty($model->{'idservicio'}) ? '<div class="col-sm-6">'
            . '<div class="form-group">'
            . '<a href="EditServicioAT?code=' . $model->{'idservicio'} . '">' . $i18n->trans('service') . '</a>'
            . '<input type="text" value="' . $model->{'idservicio'} . '" class="form-control" disabled />'
            . '</div>'
            . '</div>' : '';
    }
}