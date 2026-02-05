<?php
/**
 * This file is part of Servicios plugin for FacturaScripts
 * Copyright (C) 2021-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Plugins\Servicios\Lib\Export;

use FacturaScripts\Core\Lib\Export\PDFExport;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\ServicioAT;

/**
 * @author Carlos Garcia Gomez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class PDFserviciosExport extends PDFExport
{
    public function addModelPage($model, $columns, $title = ''): bool
    {
        $this->newPage();
        $this->insertHeader($model->idempresa);
        $this->pdf->ezText("\n" . $title . ': ' . ($model->codigo ?? $model->idservicio) . "\n", self::FONT_SIZE + 6);
        $this->newLine();

        $this->insertParallelTable($this->serviceData($model), '', $this->tableOptions());
        $this->pdf->ezText('');

        $machinesData = $this->machinesData($model);
        if ($machinesData && Tools::settings('servicios', 'print_pdf_machine_info', false)) {
            $this->printTableSection('machines', $machinesData);
        }

        $this->printTextSection('description', $model->descripcion);
        $this->printTextSection('material', $model->material);
        $this->printTextSection('solution', $model->solucion);

        if (Tools::settings('servicios', 'print_pdf_observations', false)) {
            $this->printTextSection('observations', $model->observaciones);
        }

        $worksData = $this->worksData($model);
        if ($worksData && Tools::settings('servicios', 'print_pdf_works', false)) {
            $this->printTableSection('work', $worksData);
        }

        $footer = Tools::settings('servicios', 'print_pdf_footer_text', '');
        $this->printTextSection("", $footer, false);

        return false;
    }

    protected function machinesData(&$model): array
    {
        $result = [];
        foreach ($model->getMachines() as $machine) {
            $result[] = [
                $this->i18n->trans('name') => $machine->nombre,
                $this->i18n->trans('serial-number') => $machine->numserie,
                $this->i18n->trans('description') => $machine->descripcion
            ];
        }
        return $result;
    }

    protected function printTableSection(string $title, array $data): void
    {
        $this->pdf->ezText("\n" . $this->i18n->trans($title) . "\n", self::FONT_SIZE + 4);
        $this->newLine();
        $this->pdf->ezTable($data, '', '', $this->tableOptions(1));
        $this->pdf->ezText('');
    }

    protected function printTextSection(string $title, string $text, bool $addLine = true): void
    {
        if (empty($text)) {
            return;
        }

        $this->pdf->ezText("\n" . $this->i18n->trans($title) . "\n", self::FONT_SIZE + 4);
        if ($addLine) {
            $this->newLine();
        }
        $this->pdf->ezText(nl2br($text) . "\n", self::FONT_SIZE + 2);
    }

    protected function tableOptions($headings = 0): array
    {
        return [
            'width' => $this->tableWidth,
            'showHeadings' => $headings,
            'shaded' => 0,
            'lineCol' => [1, 1, 1],
            'cols' => []
        ];
    }

    protected function serviceData(ServicioAT $model): array
    {
        $subject = $model->getSubject();
        $tipoidfiscal = empty($subject->tipoidfiscal) ? $this->i18n->trans('cifnif') : $subject->tipoidfiscal;

        $data = [
            ['key' => $this->i18n->trans('date'), 'value' => $model->fecha],
            ['key' => $this->i18n->trans('hour'), 'value' => $model->hora],
            ['key' => $this->i18n->trans('customer'), 'value' => Tools::fixHtml($subject->nombre)],
            ['key' => $tipoidfiscal, 'value' => $subject->cifnif],
            ['key' => $this->i18n->trans('address'), 'value' => $subject->getDefaultAddress()->direccion],
            ['key' => $this->i18n->trans('phone'), 'value' => ($model->telefono1 ?? $subject->telefono1)],
            ['key' => $this->i18n->trans('phone2'), 'value' => ($model->telefono2 ?? $subject->telefono2)],
        ];

        if (Tools::settings('servicios', 'print_pdf_agent', false)) {
            $agent = $model->getAgent();
            $data[] = ['key' => $this->i18n->trans('agent'), 'value' => $agent->nombre];
        }

        if (Tools::settings('servicios', 'print_pdf_assigned', false)) {
            $data[] = ['key' => $this->i18n->trans('assigned'), 'value' => $model->asignado];
        }

        return $data;
    }

    protected function worksData(ServicioAT &$model): array
    {
        $result = [];
        foreach ($model->getTrabajos() as $work) {
            $data = [
                $this->i18n->trans('from-date') => $work->fechainicio,
                $this->i18n->trans('from-hour') => $work->horainicio,
                $this->i18n->trans('until-date') => $work->fechafin,
                $this->i18n->trans('until-hour') => $work->horafin,
                $this->i18n->trans('observations') => $work->observaciones
            ];

            if (Tools::settings('servicios', 'print_pdf_work_reference', false)) {
                $data[$this->i18n->trans('reference') ] = $work->referencia;
            }

            if (Tools::settings('servicios', 'print_pdf_work_description', false)) {
                $data[$this->i18n->trans('description')] = $work->descripcion;
            }

            if (Tools::settings('servicios', 'print_pdf_work_quantity', false)) {
                $data[$this->i18n->trans('quantity')] = $work->cantidad;
            }

            if (Tools::settings('servicios', 'print_pdf_work_price', false)) {
                $data[$this->i18n->trans('price')] = Tools::money($work->precio);
            }

            $result[] = $data;
        }
        return $result;
    }
}
